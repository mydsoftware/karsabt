<?php
// تنظیمات پایه کارثبت. اطلاعات تماس واقعی را قبل از انتشار جایگزین کنید.
return [
  'brand' => 'کارثبت',
  'domain' => 'https://karsabt.ir',
  // اطلاعات دیتابیس ساخته‌شده در cPanel (MySQL Database Wizard)
  'database' => [
    'host' => 'localhost',
    'name' => 'CPANELPREFIX_karsabt',
    'username' => 'CPANELPREFIX_user',
    'password' => 'CHANGE_THIS_PASSWORD',
    'charset' => 'utf8mb4',
  ],
  // مسیر خصوصی بکاپ‌ها خارج از public_html
  'storage_dir' => dirname(__DIR__) . '/karsabt-private',
  'phone' => '09000000000',
  'whatsapp' => '989000000000',
  'telegram' => '',
  'rubika' => '',
  'eitaa' => '',
];
