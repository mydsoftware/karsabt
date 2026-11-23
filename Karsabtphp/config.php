<?php
/**
 * تنظیمات پایه کارثبت (نسخه ۱۷ — MySQL)
 *
 * قبل از انتشار حتماً این مقادیر را تغییر دهید:
 * - اطلاعات دیتابیس cPanel
 * - security_salt (یک رشته تصادفی بلند)
 * - شماره تماس و دامنه واقعی
 */
return [
  'brand' => 'کارثبت',
  'domain' => 'https://karsabt.ir',

  // اطلاعات دیتابیس ساخته‌شده در cPanel (MySQL Database Wizard)
  // نام دیتابیس و کاربر معمولاً با پیشوند حساب cPanel ساخته می‌شوند.
  'database' => [
    'host' => 'localhost',
    'name' => 'CPANELPREFIX_karsabt',
    'username' => 'CPANELPREFIX_user',
    'password' => 'CHANGE_THIS_PASSWORD',
    'charset' => 'utf8mb4',
  ],

  // مسیر خصوصی بکاپ‌ها و داده‌های حساس — باید خارج از public_html باشد
  'storage_dir' => dirname(__DIR__) . '/karsabt-private',

  /**
   * Salt امنیتی برای هش IP و رمزنگاری سبک کلید API.
   * این مقدار را با یک رشته تصادفی یکتا عوض کنید و هرگز در مخزن عمومی قرار ندهید.
   * تولید نمونه: openssl rand -hex 32
   */
  'security_salt' => 'be68d2b511226f988b7ee1aa6eaee9f99044173a15697ec822ab4457e2a5acdf',

  // اطلاعات تماس — قبل از انتشار با مقادیر واقعی جایگزین کنید
  'phone' => '09000000000',
  'whatsapp' => '989000000000',
  'telegram' => '',
  'rubika' => '',
  'eitaa' => '',
];
