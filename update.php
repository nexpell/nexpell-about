<?php

if (!function_exists('safe_query')) {
    die('Access denied');
}

global $_database, $modulname, $version, $plugin;

$modulname = 'about';
$version = isset($plugin['version']) ? (string)$plugin['version'] : ($version ?? '0.0.0');
$str = 'About';

require __DIR__ . '/install.php';

