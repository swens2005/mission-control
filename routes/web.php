<?php

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
});

// Launchpad: the client's portal.
Route::middleware(['auth', 'portal:client'])->prefix('client')->name('client.')->group(function () {
    Route::inertia('/', 'client/home')->name('home');
});

require __DIR__.'/settings.php';
