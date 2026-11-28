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
require_once ABSPATH . 'wp-admin/includes/plugin.php';

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
$assert(function_exists('karsabt_core_rate_limit_hit'), 'محدودکننده اتمیک درخواست‌ها بارگذاری شده است');

if (function_exists('karsabt_core_rate_limit_hit')) {
    $rateKey = 'karsabt_ci_rate_' . wp_generate_uuid4();
    delete_transient($rateKey);
    $assert(false === karsabt_core_rate_limit_hit($rateKey, 2, 60), 'درخواست اول در محدوده مجاز پذیرفته می‌شود');
    $assert(false === karsabt_core_rate_limit_hit($rateKey, 2, 60), 'درخواست دوم تا سقف مجاز پذیرفته می‌شود');
    $assert(true === karsabt_core_rate_limit_hit($rateKey, 2, 60), 'درخواست سوم پس از رسیدن به سقف رد می‌شود');
    delete_transient($rateKey);
}

global $wpdb;
$tables = array(
    $wpdb->posts,
    $wpdb->postmeta,
    karsabt_finance_table(),
    karsabt_core_mobile_index_table(),
);
foreach ($tables as $table) {
    $engine = $wpdb->get_var($wpdb->prepare(
        'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
        $table
    ));
    $assert(strtolower((string) $engine) === 'innodb', "جدول {$table} از InnoDB استفاده می‌کند");
}

$mobileIndex = karsabt_core_mobile_index_table();
$mobileIndexEngine = $wpdb->get_var($wpdb->prepare(
    'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
    $mobileIndex
));
$assert('innodb' === strtolower((string) $mobileIndexEngine), 'ایندکس شماره موبایل روی InnoDB قرار دارد');
$mobileIndexes = $wpdb->get_results("SHOW INDEX FROM {$mobileIndex}", ARRAY_A);
$mobilePrimary = array_filter((array) $mobileIndexes, static function ($row) { return 'PRIMARY' === ($row['Key_name'] ?? ''); });
$userUnique = array_filter((array) $mobileIndexes, static function ($row) { return 'user_id' === ($row['Key_name'] ?? '') && '0' === (string) ($row['Non_unique'] ?? '1'); });
$assert((bool) $mobilePrimary && (bool) $userUnique, 'شماره موبایل و شناسه کاربر در ایندکس دیتابیس یکتا هستند');

$staffId = wp_create_user('karsabt-ci-staff', 'ci-staff-password', 'staff@example.test');
$customerId = wp_create_user('karsabt-ci-customer', 'ci-customer-password', 'customer@example.test');
$assert(!is_wp_error($staffId) && !is_wp_error($customerId), 'کاربران آزمایشی ایجاد شدند');

if (!is_wp_error($staffId) && !is_wp_error($customerId)) {
    $testMobile = '0912' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
    $claimFirst = karsabt_core_claim_mobile($testMobile, $customerId);
    $claimDuplicate = karsabt_core_claim_mobile($testMobile, $staffId);
    if (true !== $claimFirst) {
        fwrite(STDERR, '[جزئیات ایندکس موبایل] error=' . $wpdb->last_error . ' query=' . $wpdb->last_query . ' mobile=' . $testMobile . ' customer=' . $customerId . "\\n");
    }
    $assert(true === $claimFirst, 'شماره موبایل برای حساب اول رزرو می‌شود');
    $assert(false === $claimDuplicate, 'شماره موبایل تکراری برای حساب دوم در سطح دیتابیس رد می‌شود');
    $assert($customerId === karsabt_core_find_user_by_mobile($testMobile), 'جستجوی شماره موبایل به مالک یکتا می‌رسد');
    $wpdb->delete($mobileIndex, array('mobile'=>$testMobile), array('%s'));
    delete_user_meta($customerId, '_karsabt_mobile');
    delete_user_meta($customerId, '_karsabt_mobile_verified');

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
        $assert(function_exists('karsabt_docs_user_can_access_order'), 'کنترل دسترسی دریافت مدارک بارگذاری شده است');
        if (function_exists('karsabt_docs_user_can_access_order')) {
            $assert(true === karsabt_docs_user_can_access_order($orderA, $staffId), 'کارشناس مسئول به مدارک پرونده تخصیص‌یافته دسترسی دارد');
            $assert(false === karsabt_docs_user_can_access_order($orderB, $staffId), 'کارشناس به مدارک پرونده تخصیص‌نیافته دسترسی ندارد');
            $assert(false === karsabt_docs_user_can_access_order($orderA, $customerId), 'مشتری دیگر از کنترل دسترسی مدارک عبور نمی‌کند');
        }
        if (!user_can($staffId, 'edit_post', $orderA)) {
            $debugUser = get_user_by('id', $staffId);
            fwrite(STDERR, '[جزئیات] نقش=' . implode(',', (array) $debugUser->roles)
                . ' مجوز=' . (user_can($staffId, 'edit_karsabt_orders') ? 'yes' : 'no')
                . ' تخصیص=' . get_post_meta($orderA, '_karsabt_assigned_user_id', true)
                . ' map=' . implode(',', map_meta_cap('edit_post', $staffId, $orderA)) . "\\n");
        }
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
