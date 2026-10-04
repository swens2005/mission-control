<?php

use App\Http\Controllers\DeployController;
use App\Http\Controllers\OutboundCheckController;
use Illuminate\Support\Facades\Route;

// Loaded outside the "web" group: no session, cookies or CSRF. The bearer
// token is the only credential. See docs/decisions/0003-migrations-without-ssh.md.
Route::post('_deploy/migrate', DeployController::class)
    ->middleware('throttle:deploy')
    ->name('deploy.migrate');

// TEMPORARY (Phase 2): does the host allow outbound HTTP(S)? Removed after
// its first production run.
Route::post('_deploy/outbound-check', OutboundCheckController::class)
    ->middleware('throttle:deploy')
    ->name('deploy.outbound-check');
