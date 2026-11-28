<?php
if (!defined('ABSPATH')) { exit; }

function karsabt_audit_table() {
    global $wpdb;
    return $wpdb->prefix . 'karsabt_audit_log';
}

function karsabt_audit_install() {
    global $wpdb;
    if (get_option('karsabt_audit_schema') === '1') { return; }
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = karsabt_audit_table();
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
        event_type varchar(64) NOT NULL,
        object_type varchar(32) NOT NULL DEFAULT '',
        object_id bigint(20) unsigned NOT NULL DEFAULT 0,
        detail varchar(190) NOT NULL DEFAULT '',
        ip_hash char(64) NOT NULL DEFAULT '',
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY event_type (event_type),
        KEY object_lookup (object_type,object_id),
        KEY created_at (created_at)
    ) {$charset};";
    dbDelta($sql);
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($exists === $table) { update_option('karsabt_audit_schema', '1', false); }
}
add_action('init', 'karsabt_audit_install', 5);

function karsabt_audit_log($event, $object_type = '', $object_id = 0, $detail = '') {
    global $wpdb;
    $allowed = array('order_status_changed','order_phase_changed','order_assigned','order_deleted','customer_deleted','mobile_changed','document_uploaded','document_downloaded','document_reviewed','payment_created','audit_retention_purged');
    $event = sanitize_key($event);
    if (!in_array($event, $allowed, true)) { return false; }
    $table = karsabt_audit_table();
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) { return false; }
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    $ip_hash = '' !== $ip ? hash_hmac('sha256', $ip, wp_salt('auth')) : '';
    return 1 === $wpdb->insert($table, array(
        'actor_user_id'=>get_current_user_id(),'event_type'=>$event,
        'object_type'=>sanitize_key($object_type),'object_id'=>absint($object_id),
        'detail'=>sanitize_text_field($detail),'ip_hash'=>$ip_hash,
        'created_at'=>current_time('mysql', true),
    ), array('%d','%s','%s','%d','%s','%s','%s'));
}

function karsabt_audit_watch_order_meta($meta_id, $object_id, $meta_key, $meta_value) {
    if ('karsabt_order' !== get_post_type($object_id)) { return; }
    $events = array('_karsabt_order_status'=>'order_status_changed','_karsabt_workflow_phase'=>'order_phase_changed','_karsabt_assigned_user_id'=>'order_assigned');
    if (isset($events[$meta_key])) { karsabt_audit_log($events[$meta_key], 'order', $object_id, 'تغییر اطلاعات گردش کار'); }
}
add_action('added_post_meta', 'karsabt_audit_watch_order_meta', 20, 4);
add_action('updated_post_meta', 'karsabt_audit_watch_order_meta', 20, 4);

function karsabt_audit_before_delete_post($post_id, $post) {
    if ($post instanceof WP_Post && 'karsabt_order' === $post->post_type) { karsabt_audit_log('order_deleted', 'order', $post_id, 'حذف پرونده'); }
}
add_action('before_delete_post', 'karsabt_audit_before_delete_post', 10, 2);

function karsabt_audit_deleted_user($user_id, $reassign, $user) {
    karsabt_audit_log('customer_deleted', 'user', $user_id, 'حذف حساب کاربری');
}
add_action('delete_user', 'karsabt_audit_deleted_user', 10, 3);

function karsabt_audit_retention_days() {
    $days = defined('KARSABT_AUDIT_RETENTION_DAYS') ? absint(KARSABT_AUDIT_RETENTION_DAYS) : 365;
    return max(30, min(3650, (int) apply_filters('karsabt_audit_retention_days', $days)));
}
function karsabt_audit_schedule_cleanup() {
    if (!wp_next_scheduled('karsabt_audit_daily_cleanup')) { wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'karsabt_audit_daily_cleanup'); }
}
add_action('init', 'karsabt_audit_schedule_cleanup', 30);

function karsabt_audit_cleanup() {
    global $wpdb;
    $table = karsabt_audit_table();
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) { return; }
    $cutoff = gmdate('Y-m-d H:i:s', time() - (karsabt_audit_retention_days() * DAY_IN_SECONDS));
    $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $cutoff));
    if (false !== $deleted && $deleted > 0) { karsabt_audit_log('audit_retention_purged', 'audit', 0, 'تعداد رویدادهای منقضی: ' . absint($deleted)); }
}
add_action('karsabt_audit_daily_cleanup', 'karsabt_audit_cleanup');

function karsabt_audit_admin_menu() {
    add_submenu_page('edit.php?post_type=karsabt_order', 'گزارش ممیزی امنیتی', 'گزارش ممیزی', 'manage_options', 'karsabt-audit-log', 'karsabt_audit_admin_page');
}
add_action('admin_menu', 'karsabt_audit_admin_menu');

function karsabt_audit_admin_page() {
    if (!current_user_can('manage_options')) { wp_die('دسترسی مجاز نیست.', '', array('response'=>403)); }
    global $wpdb;
    $table = karsabt_audit_table();
    $page = max(1, absint($_GET['paged'] ?? 1));
    $per_page = 50;
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $rows = $wpdb->get_results($wpdb->prepare("SELECT id,actor_user_id,event_type,object_type,object_id,detail,created_at FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, ($page - 1) * $per_page));
    $labels = array('order_status_changed'=>'تغییر وضعیت پرونده','order_phase_changed'=>'تغییر مرحله پرونده','order_assigned'=>'تغییر مسئول پرونده','order_deleted'=>'حذف پرونده','customer_deleted'=>'حذف حساب مشتری','mobile_changed'=>'تغییر شماره موبایل','document_uploaded'=>'بارگذاری مدرک','document_downloaded'=>'دریافت مدرک','document_reviewed'=>'بررسی مدرک','payment_created'=>'ثبت پرداخت','audit_retention_purged'=>'پاک‌سازی لاگ منقضی');
    echo '<div class="wrap"><h1>گزارش ممیزی امنیتی</h1>';
    echo '<p>این گزارش فقط برای مدیر کل قابل مشاهده است. اطلاعات تماس، متن پیام‌ها، یادداشت‌ها و محتوای مدارک ذخیره نمی‌شود. مدت نگهداری: ' . esc_html(number_format_i18n(karsabt_audit_retention_days())) . ' روز.</p>';
    echo '<table class="widefat striped"><thead><tr><th>زمان (UTC)</th><th>کاربر</th><th>رویداد</th><th>شناسه مرتبط</th><th>جزئیات</th></tr></thead><tbody>';
    if ($rows) {
        foreach ($rows as $row) {
            $actor = $row->actor_user_id ? get_user_by('id', $row->actor_user_id) : false;
            $object = $row->object_type ? $row->object_type . ' #' . $row->object_id : '—';
            echo '<tr><td>' . esc_html($row->created_at) . '</td><td>' . esc_html($actor ? $actor->display_name : ('کاربر #' . $row->actor_user_id)) . '</td><td>' . esc_html($labels[$row->event_type] ?? $row->event_type) . '</td><td>' . esc_html($object) . '</td><td>' . esc_html($row->detail) . '</td></tr>';
        }
    } else { echo '<tr><td colspan="5">هنوز رویدادی ثبت نشده است.</td></tr>'; }
    echo '</tbody></table>';
    $pages = (int) ceil($total / $per_page);
    if ($pages > 1) {
        echo '<p>';
        for ($i=1; $i<=$pages; $i++) {
            $url = add_query_arg(array('post_type'=>'karsabt_order','page'=>'karsabt-audit-log','paged'=>$i), admin_url('edit.php'));
            echo '<a class="button ' . ($i === $page ? 'button-primary' : '') . '" href="' . esc_url($url) . '">' . esc_html(number_format_i18n($i)) . '</a> ';
        }
        echo '</p>';
    }
    echo '</div>';
}
