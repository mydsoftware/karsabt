<?php
/**
 * آزمون مستقل جست‌وجوی امن مدارک در متادیتای سفارش.
 * اجرا: php tests/php/documents-find-test.php
 */
define('ABSPATH', __DIR__ . '/');
$GLOBALS['karsabt_test_post_meta'] = array();

function add_shortcode($tag, $callback) {}
function add_action($hook, $callback) {}
function get_post_meta($post_id, $key, $single = false) {
    return $GLOBALS['karsabt_test_post_meta'][$post_id][$key] ?? '';
}

require dirname(__DIR__, 2) . '/wordpress/wp-content/plugins/karsabt-core/includes/class-karsabt-documents.php';

function docs_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

$order_id = 42;
$valid_id = '0123456789abcdef0123456789abcdef';
$valid_doc = array('id' => $valid_id, 'file' => $valid_id . '.pdf', 'status' => 'pending');
$GLOBALS['karsabt_test_post_meta'][$order_id]['_karsabt_documents'] = array(
    'رکورد خراب',
    array('id' => array('not-a-string')),
    array('id' => 'شناسه-نامعتبر'),
    $valid_doc,
);

list($found, $all) = karsabt_docs_find($order_id, $valid_id);
docs_assert(is_array($found) && $found['id'] === $valid_id, 'یافتن مدرک معتبر میان رکوردهای خراب');
docs_assert(4 === count($all), 'حفظ آرایه اصلی مدارک هنگام جست‌وجو');
list($missing,) = karsabt_docs_find($order_id, str_repeat('f', 32));
docs_assert(null === $missing, 'عدم تطبیق شناسه ناشناخته');
list($invalid,) = karsabt_docs_find($order_id, '../' . $valid_id);
docs_assert(null === $invalid, 'رد شناسه دارای مسیر یا قالب نامعتبر');
$GLOBALS['karsabt_test_post_meta'][$order_id]['_karsabt_documents'] = 'metadata-corrupted';
list($corrupt,) = karsabt_docs_find($order_id, $valid_id);
docs_assert(null === $corrupt, 'مدیریت متادیتای غیرآرایه بدون خطا');

echo "تمام آزمون‌های جست‌وجوی مدارک با موفقیت اجرا شدند.\n";
