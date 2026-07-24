<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Laravel 11's bundled config/database.php (merged in for the mysql/mariadb
// connections we don't use) references a PDO constant PHP 8.5 deprecated —
// silence that specific noise without hiding real errors. See CLAUDE.md.
error_reporting(E_ALL & ~E_DEPRECATED);

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
