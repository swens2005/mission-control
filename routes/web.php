<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ChecklistItemController;
use App\Http\Controllers\Admin\CheckRunController;
use App\Http\Controllers\Admin\CheckWaiverController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DesignController;
use App\Http\Controllers\Admin\LaunchController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProofmarkController;
use App\Http\Controllers\Admin\SignoffController;
use App\Http\Controllers\Client\LaunchController as ClientLaunchController;
use App\Http\Controllers\Client\ProjectController as ClientProjectController;
use App\Http\Controllers\Client\ProofmarkController as ClientProofmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\DesignImageController;
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
});

// Launchpad: the client's portal.
Route::middleware(['auth', 'portal:client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/', [ClientProjectController::class, 'index'])->name('home');
    Route::get('projects/{project}', [ClientProjectController::class, 'show'])->name('projects.show');
    Route::get('projects/{project}/launch', [ClientLaunchController::class, 'show'])->name('launch.show');
    Route::post('projects/{project}/launch/signoff', [ClientLaunchController::class, 'signoff'])->name('signoffs.store');
    Route::get('projects/{project}/proofmark', [ClientProofmarkController::class, 'show'])->name('proofmark.show');
    Route::put('checklist-items/{item}/check', [ClientLaunchController::class, 'check'])->name('checklist.check');
    Route::delete('checklist-items/{item}/check', [ClientLaunchController::class, 'uncheck'])->name('checklist.uncheck');
});

require __DIR__.'/settings.php';
