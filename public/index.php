<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Locally and in CI the app sits one folder up. In production this folder is
// deployed to ~/public_html/mission-control and the rest of the app to
// ~/mission-control-app, outside the web root.
// See docs/decisions/0002-hosting-layout.md.
$basePath = is_file(__DIR__.'/../bootstrap/app.php')
    ? dirname(__DIR__)
    : dirname(__DIR__, 2).'/mission-control-app';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
