<?php
/**
 * Karsabt PHP config (MySQL edition)
 * Copy values from your hosting panel.
 */
return [
    'db' => [
        'host' => getenv('KARSABT_DB_HOST') ?: 'localhost',
        'name' => getenv('KARSABT_DB_NAME') ?: 'karsabt',
        'user' => getenv('KARSABT_DB_USER') ?: 'karsabt',
        'pass' => getenv('KARSABT_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'site' => [
        'name' => 'کارثبت',
        'url' => getenv('KARSABT_SITE_URL') ?: 'https://karsabt.com',
        'phone' => '02191000000',
    ],
];
