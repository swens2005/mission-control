<?php

use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\ProjectController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Signed-in users go to their own portal; guests to the login page.
Route::get('/', function (Request $request) {
    $user = $request->user();

    return redirect()->to($user instanceof User ? $user->portalHomeUrl() : route('login'));
})->name('home');

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
});

// Launchpad: the client's portal.
Route::middleware(['auth', 'portal:client'])->prefix('client')->name('client.')->group(function () {
    Route::inertia('/', 'client/home')->name('home');
});

require __DIR__.'/settings.php';
