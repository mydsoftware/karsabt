<?php
declare(strict_types=1);
$config=require dirname(__DIR__).'/config.php';
if (($config['database']['charset'] ?? '') !== 'utf8mb4') { fwrite(STDERR,"FAIL MySQL charset must be utf8mb4\n"); exit(1); }
if (!isset($config['database']['host'],$config['database']['name'],$config['database']['username'],$config['database']['password'])) { fwrite(STDERR,"FAIL MySQL connection keys are incomplete\n"); exit(1); }
require dirname(__DIR__).'/includes/storage.php';
if (!function_exists('karsabt_database_connection')) { fwrite(STDERR,"FAIL MySQL connection factory missing\n"); exit(1); }
echo "PASS MySQL configuration and PDO connection factory are present (live credentials not tested).\n";
