<?php
/**
 * Bootstrap عمومی صفحات فرانت — یک نقطه ورود برای config، storage و helpers.
 */
declare(strict_types=1);

$config = require dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/storage.php';
$GLOBALS['config'] = $config;

require_once __DIR__ . '/functions.php';
