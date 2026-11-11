<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 120);

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Auto-clear stale caches on deployment
if (isset($_GET['clear_cache']) || isset($_GET['reset_cache'])) {
    @unlink(__DIR__ . '/../bootstrap/cache/routes-v7.php');
    @unlink(__DIR__ . '/../bootstrap/cache/config.php');
    @unlink(__DIR__ . '/../bootstrap/cache/events.php');
    $views = glob(__DIR__ . '/../storage/framework/views/*.php');
    if ($views) {
        foreach ($views as $view) {
            @unlink($view);
        }
    }
    die('Cache cleared successfully on ' . date('Y-m-d H:i:s'));
}

// Ensure stale route cache doesn't prevent closures or updated controllers
if (file_exists($routeCache = __DIR__ . '/../bootstrap/cache/routes-v7.php')) {
    @unlink($routeCache);
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
