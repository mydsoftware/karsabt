<?php
/**
 * آزمون اجرایی پایه کارثبت روی وردپرس و MariaDB واقعی.
 * این آزمون جایگزین آزمون هم‌زمانی درخواست‌های HTTP نیست.
 */
declare(strict_types=1);

$wpPath = getenv('KARSABT_WP_PATH');
if (!$wpPath || !is_file($wpPath . '/wp-load.php')) {
    fwrite(STDERR, "مسیر نصب وردپرس معتبر نیست.\n");
    exit(1);
}
require $wpPath . '/wp-load.php';

$checks = array();
$failures = array();
$assert = static function ($condition, string $label) use (&$checks, &$failures): void {
    $checks[] = $label;
    if (!$condition) {
        $failures[] = $label;
        fwrite(STDERR, "[ناموفق] {$label}\n");
        return;
    }
    fwrite(STDOUT, "[موفق] {$label}\n");
};

$assert(is_plugin_active('karsabt-core/karsabt-core.php'), 'افزونه هسته کارثبت فعال است');
$assert(wp_get_theme()->get_stylesheet() === 'karsabt', 'قالب کارثبت فعال است');
$assert(post_type_exists('karsabt_order'), 'نوع محتوای درخواست ثبت شده است');
$assert(get_role('karsabt_case_worker') instanceof WP_Role, 'نقش کارشناس ایجاد شده است');
$assert(function_exists('karsabt_finance_table'), 'ماژول مالی بارگذاری شده است');
$assert(function_exists('karsabt_messages_lock'), 'قفل پیام‌ها در دسترس است');
$assert(function_exists('karsabt_docs_lock'), 'قفل مدارک در دسترس است');

global $wpdb;
$tables = array(
    $wpdb->posts,
    $wpdb->postmeta,
    karsabt_finance_table(),
);
foreach ($tables as $table) {
    $engine = $wpdb->get_var($wpdb->prepare(
        'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
        $table
    ));
    $assert(strtolower((string) $engine) === 'innodb', "جدول {$table} از InnoDB استفاده می‌کند");
}

$staffId = wp_create_user('karsabt-ci-staff', 'ci-staff-password', 'staff@example.test');
$customerId = wp_create_user('karsabt-ci-customer', 'ci-customer-password', 'customer@example.test');
$assert(!is_wp_error($staffId) && !is_wp_error($customerId), 'کاربران آزمایشی ایجاد شدند');

if (!is_wp_error($staffId) && !is_wp_error($customerId)) {
    $staff = new WP_User($staffId);
    $staff->set_role('karsabt_case_worker');

    $orderA = wp_insert_post(array(
        'post_type' => 'karsabt_order',
        'post_status' => 'private',
        'post_title' => 'آزمون پرونده تخصیص‌یافته',
    ), true);
    $orderB = wp_insert_post(array(
        'post_type' => 'karsabt_order',
        'post_status' => 'private',
        'post_title' => 'آزمون پرونده تخصیص‌نیافته',
    ), true);

    if (!is_wp_error($orderA) && !is_wp_error($orderB)) {
        update_post_meta($orderA, '_karsabt_assigned_user_id', $staffId);
        update_post_meta($orderB, '_karsabt_assigned_user_id', $customerId);
        $assert(user_can($staffId, 'edit_post', $orderA), 'کارشناس می‌تواند پرونده تخصیص‌یافته را ویرایش کند');
        $assert(!user_can($staffId, 'edit_post', $orderB), 'کارشناس به پرونده تخصیص‌نیافته دسترسی ویرایش ندارد');
        $assert(!user_can($staffId, 'delete_post', $orderA), 'کارشناس اجازه حذف پرونده را ندارد');
    } else {
        $assert(false, 'ایجاد پرونده‌های آزمایشی موفق بود');
    }
}

if ($failures) {
    fwrite(STDERR, sprintf("\n%d آزمون از %d آزمون ناموفق بود.\n", count($failures), count($checks)));
    exit(1);
}
fwrite(STDOUT, sprintf("\nهمه %d آزمون اجرایی موفق بودند.\n", count($checks)));
