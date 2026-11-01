<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Instalasi pertama (mis. upload ke cPanel): buat .env dari .env.example beserta APP_KEY acak,
// supaya wizard /install bisa dibuka tanpa akses terminal.
if (! file_exists(__DIR__.'/../.env') && file_exists(__DIR__.'/../.env.example')) {
    $env = file_get_contents(__DIR__.'/../.env.example');
    $env = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=base64:'.base64_encode(random_bytes(32)), $env);
    @file_put_contents(__DIR__.'/../.env', $env);
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
