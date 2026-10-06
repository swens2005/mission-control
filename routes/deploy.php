<?php

use App\Http\Controllers\DeployController;
use Illuminate\Support\Facades\Route;

// Loaded outside the "web" group: no session, cookies or CSRF. The bearer
// token is the only credential. See docs/decisions/0003-migrations-without-ssh.md.
Route::post('_deploy/migrate', DeployController::class)
    ->middleware('throttle:deploy')
    ->name('deploy.migrate');
