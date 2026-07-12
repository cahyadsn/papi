<?php
/*
================================================================================
 *  BISMILLAAHIRRAHMAANIRRAHIIM - In the Name of Allah, Most Gracious, Most Merciful
================================================================================
FILENAME     : inc/db.php
AUTHOR       : CAHYA DSN
CREATED DATE : 2017-04-09
UPDATED DATE : 2026-07-12
DEMO SITE    : http://psycho.cahyadsn.com/papi
SOURCE CODE  : https://github.com/cahyadsn/papi
================================================================================
*/

require_once __DIR__ . '/env.php';

// Load .env from the project root (one level up from /inc)
load_env(__DIR__ . '/../.env');

// Read credentials via env() helper
$dbhost    = env('DB_HOST',    'localhost');
$dbuser    = env('DB_USER',    'root');
$dbpass    = env('DB_PASS',    '');
$dbname    = env('DB_NAME',    'psycho');
$dbport    = (int) env('DB_PORT', 3306);
$dbcharset = env('DB_CHARSET', 'utf8mb4');

// Connect
$db = new mysqli($dbhost, $dbuser, $dbpass, $dbname, $dbport);

if ($db->connect_errno) {
    $debug = env('APP_DEBUG', false);
    $msg   = $debug
        ? 'Database connection failed: ' . $db->connect_error
        : 'Database connection failed. Please try again later.';
    die($msg);
}

$db->set_charset($dbcharset);
