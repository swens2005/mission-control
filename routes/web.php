<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ChecklistItemController;
use App\Http\Controllers\Admin\CheckRunController;
use App\Http\Controllers\Admin\CheckWaiverController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\CommentResolutionController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DesignController;
use App\Http\Controllers\Admin\KitSharingController;
use App\Http\Controllers\Admin\LaunchController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PaletteController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProofmarkController;
use App\Http\Controllers\Admin\SignoffController;
use App\Http\Controllers\Admin\TokenExportController;
use App\Http\Controllers\Admin\TypeController;
use App\Http\Controllers\Client\LaunchController as ClientLaunchController;
use App\Http\Controllers\Client\PaletteController as ClientPaletteController;
use App\Http\Controllers\Client\ProjectController as ClientProjectController;
use App\Http\Controllers\Client\ProofmarkController as ClientProofmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\DesignImageController;
use App\Http\Controllers\PublicStyleGuideController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Signed-in users go to their own portal; guests to the login page.
Route::get('/', function (Request $request) {
    $user = $request->user();

    return redirect()->to($user instanceof User ? $user->portalHomeUrl() : route('login'));
})->name('home');

// The public demo: a sandbox per visitor (ADR 0004, story 08).
Route::post('demo', [DemoController::class, 'store'])->middleware(['guest', 'throttle:demo'])->name('demo.store');
Route::post('demo/switch', [DemoController::class, 'switch'])->middleware('auth')->name('demo.switch');

// Design images for both portals, through their policy (ADR 0009).
Route::get('proofmark/designs/{design}/image', DesignImageController::class)->middleware('auth')->name('designs.image');

// Pinned comments, from either portal (story 17).
Route::post('proofmark/designs/{design}/comments', [CommentController::class, 'store'])->middleware(['auth', 'throttle:comments'])->name('comments.store');

// A brand kit's read-only style guide, shared by link without login
// (story 24). The random token is the key; the studio can turn it off.
Route::get('style-guide/{token}', PublicStyleGuideController::class)->middleware('throttle:60,1')->name('style-guide.show');

// Mission Control: the studio's portal.
Route::middleware(['auth', 'portal:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::inertia('/', 'admin/dashboard')->name('dashboard');

    Route::resource('organizations', OrganizationController::class)->except('destroy');
    Route::post('organizations/{organization}/archive', [OrganizationController::class, 'archive'])->name('organizations.archive');
    Route::delete('organizations/{organization}/archive', [OrganizationController::class, 'unarchive'])->name('organizations.unarchive');
    Route::post('organizations/{organization}/contacts', [ContactController::class, 'store'])->name('organizations.contacts.store');

    Route::resource('projects', ProjectController::class)->except('destroy');
    Route::post('projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::delete('projects/{project}/archive', [ProjectController::class, 'unarchive'])->name('projects.unarchive');

    Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');

    // Launch Control (story 09).
    Route::get('projects/{project}/launch', [LaunchController::class, 'show'])->name('launch.show');
    Route::post('projects/{project}/launch', [LaunchController::class, 'store'])->name('launch.store');
    Route::patch('projects/{project}/launch', [LaunchController::class, 'update'])->name('launch.update');
    Route::post('projects/{project}/launch/items', [ChecklistItemController::class, 'store'])->name('checklist.store');
    Route::delete('checklist-items/{item}', [ChecklistItemController::class, 'destroy'])->name('checklist.destroy');
    Route::put('checklist-items/{item}/check', [ChecklistItemController::class, 'check'])->name('checklist.check');
    Route::delete('checklist-items/{item}/check', [ChecklistItemController::class, 'uncheck'])->name('checklist.uncheck');

    // Automated checks (story 11).
    Route::post('projects/{project}/launch/runs', [CheckRunController::class, 'store'])->name('checks.store');
    Route::post('projects/{project}/launch/waivers/{check}', [CheckWaiverController::class, 'store'])->name('waivers.store');
    Route::delete('projects/{project}/launch/waivers/{check}', [CheckWaiverController::class, 'destroy'])->name('waivers.destroy');

    // Go/no-go (story 12).
    Route::post('projects/{project}/launch/signoff', [SignoffController::class, 'store'])->name('signoffs.store');
    Route::post('projects/{project}/launch/launched', [LaunchController::class, 'launched'])->name('launch.launched');

    // Proofmark (story 15).
    Route::get('projects/{project}/proofmark', [ProofmarkController::class, 'show'])->name('proofmark.show');
    Route::post('projects/{project}/proofmark/rounds', [ProofmarkController::class, 'storeRound'])->name('rounds.store');
    Route::post('rounds/{round}/send', [ProofmarkController::class, 'send'])->name('rounds.send');
    Route::post('rounds/{round}/designs', [DesignController::class, 'store'])->middleware('throttle:uploads')->name('designs.store');
    Route::patch('designs/{design}', [DesignController::class, 'update'])->name('designs.update');
    Route::post('designs/{design}/move', [DesignController::class, 'move'])->name('designs.move');
    Route::delete('designs/{design}', [DesignController::class, 'destroy'])->name('designs.destroy');

    // Resolving comments and moving on (story 18).
    Route::put('comments/{comment}/resolved', [CommentResolutionController::class, 'store'])->name('comments.resolve');
    Route::delete('comments/{comment}/resolved', [CommentResolutionController::class, 'destroy'])->name('comments.reopen');
    Route::post('projects/{project}/proofmark/to-launch', [ProofmarkController::class, 'toLaunch'])->name('proofmark.to-launch');

    // Palette Lab (story 20).
    Route::get('projects/{project}/palette', [PaletteController::class, 'show'])->name('palette.show');
    Route::post('projects/{project}/palette', [PaletteController::class, 'store'])->name('palette.store');
    Route::post('kits/{kit}/colors', [ColorController::class, 'store'])->name('colors.store');
    Route::patch('colors/{color}', [ColorController::class, 'update'])->name('colors.update');
    Route::post('colors/{color}/move', [ColorController::class, 'move'])->name('colors.move');
    Route::post('colors/{color}/fix', [ColorController::class, 'fix'])->name('colors.fix');
    Route::put('kits/{kit}/type', [TypeController::class, 'update'])->name('type.update');
    Route::get('kits/{kit}/export/{format}', TokenExportController::class)->name('tokens.export');

    // Sharing, the public link and revisions (story 24).
    Route::post('kits/{kit}/share', [KitSharingController::class, 'share'])->name('kits.share');
    Route::post('kits/{kit}/public-link', [KitSharingController::class, 'enablePublicLink'])->name('kits.public-link.store');
    Route::delete('kits/{kit}/public-link', [KitSharingController::class, 'disablePublicLink'])->name('kits.public-link.destroy');
    Route::post('kits/{kit}/revision', [KitSharingController::class, 'revise'])->name('kits.revise');
    Route::delete('colors/{color}', [ColorController::class, 'destroy'])->name('colors.destroy');
});

// Launchpad: the client's portal.
Route::middleware(['auth', 'portal:client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/', [ClientProjectController::class, 'index'])->name('home');
    Route::get('projects/{project}', [ClientProjectController::class, 'show'])->name('projects.show');
    Route::get('projects/{project}/launch', [ClientLaunchController::class, 'show'])->name('launch.show');
    Route::post('projects/{project}/launch/signoff', [ClientLaunchController::class, 'signoff'])->name('signoffs.store');
    Route::get('projects/{project}/proofmark', [ClientProofmarkController::class, 'show'])->name('proofmark.show');
    Route::post('rounds/{round}/approve', [ClientProofmarkController::class, 'approve'])->name('rounds.approve');
    Route::get('projects/{project}/palette', [ClientPaletteController::class, 'show'])->name('palette.show');
    Route::post('kits/{kit}/approve', [ClientPaletteController::class, 'approve'])->name('kits.approve');
    Route::put('checklist-items/{item}/check', [ClientLaunchController::class, 'check'])->name('checklist.check');
    Route::delete('checklist-items/{item}/check', [ClientLaunchController::class, 'uncheck'])->name('checklist.uncheck');
});

require __DIR__.'/settings.php';
