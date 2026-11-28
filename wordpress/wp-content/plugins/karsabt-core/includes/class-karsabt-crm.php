<?php
/**
 * CRM: تماس‌ها، یادداشت‌ها و پیگیری‌های مشتریان.
 */
if (!defined('ABSPATH')) {
    exit;
}

function karsabt_crm_activity_table() {
    global $wpdb;
    return $wpdb->prefix . 'karsabt_customer_activities';
}

function karsabt_crm_install() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $table = karsabt_crm_activity_table();
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) unsigned NOT NULL,
        created_by bigint(20) unsigned NOT NULL,
        activity_type varchar(20) NOT NULL,
        outcome varchar(30) NOT NULL DEFAULT '',
        details text NOT NULL,
        due_at datetime NULL,
        completed_at datetime NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY customer_id (customer_id),
        KEY due_at (due_at),
        KEY completed_at (completed_at),
        KEY created_at (created_at)
    ) {$charset} ENGINE=InnoDB;";
    dbDelta($sql);
    update_option('karsabt_crm_db_version', '1', false);
}
function karsabt_crm_maybe_install() {
    if ('1' !== get_option('karsabt_crm_db_version')) {
        karsabt_crm_install();
    }
}
add_action('admin_init', 'karsabt_crm_maybe_install');

function karsabt_crm_register_menu() {
    add_submenu_page('karsabt-customers', 'تماس‌ها و پیگیری‌ها', 'تماس‌ها و پیگیری‌ها', 'manage_options', 'karsabt-crm-activities', 'karsabt_crm_render_page');
}
add_action('admin_menu', 'karsabt_crm_register_menu', 30);

function karsabt_crm_valid_customer($customer_id) {
    $customer = get_user_by('id', absint($customer_id));
    return $customer && in_array('subscriber', (array) $customer->roles, true);
}

function karsabt_crm_render_page() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی مجاز نیست.');
    }
    global $wpdb;
    $table = karsabt_crm_activity_table();
    $notice = '';

    if ('POST' === strtoupper(isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '') && isset($_POST['karsabt_crm_add_activity'])) {
        check_admin_referer('karsabt_crm_add_activity');
        $customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
        $type = isset($_POST['activity_type']) ? sanitize_key(wp_unslash($_POST['activity_type'])) : '';
        $outcome = isset($_POST['outcome']) ? sanitize_key(wp_unslash($_POST['outcome'])) : '';
        $details = isset($_POST['details']) ? sanitize_textarea_field(wp_unslash($_POST['details'])) : '';
        $due_raw = isset($_POST['due_at']) ? sanitize_text_field(wp_unslash($_POST['due_at'])) : '';
        $types = array('call', 'note', 'followup');
        $outcomes = array('', 'answered', 'no_answer', 'interested', 'not_interested', 'pending');
        $due_at = null;
        if ('' !== $due_raw) {
            $due_date = DateTime::createFromFormat('Y-m-d\\TH:i', $due_raw, wp_timezone());
            if ($due_date && $due_date->format('Y-m-d\\TH:i') === $due_raw) {
                $due_at = $due_date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            }
        }
        if (!karsabt_crm_valid_customer($customer_id) || !in_array($type, $types, true) ||
            !in_array($outcome, $outcomes, true) || '' === $details || strlen($details) > 4000 ||
            ('' !== $due_raw && null === $due_at) || ('followup' === $type && null === $due_at)) {
            $notice = '<div class="notice notice-error"><p>اطلاعات فعالیت معتبر نیست. برای پیگیری، زمان یادآوری الزامی است.</p></div>';
        } else {
            $inserted = $wpdb->insert($table, array(
                'customer_id' => $customer_id,
                'created_by' => get_current_user_id(),
                'activity_type' => $type,
                'outcome' => $outcome,
                'details' => $details,
                'due_at' => $due_at,
                'completed_at' => null,
                'created_at' => current_time('mysql', true),
            ), array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s'));
            if (false === $inserted) {
                $notice = '<div class="notice notice-error"><p>ثبت فعالیت در پایگاه داده ناموفق بود.</p></div>';
            } else {
                if (function_exists('karsabt_audit_log')) {
                    karsabt_audit_log('customer_activity_created', 'user', $customer_id, 'CRM activity');
                }
                $notice = '<div class="notice notice-success"><p>فعالیت مشتری ثبت شد.</p></div>';
            }
        }
    }

    if (isset($_POST['karsabt_crm_complete'])) {
        check_admin_referer('karsabt_crm_complete');
        $activity_id = isset($_POST['activity_id']) ? absint($_POST['activity_id']) : 0;
        $updated = $activity_id ? $wpdb->update($table, array('completed_at' => current_time('mysql', true)), array('id' => $activity_id, 'activity_type' => 'followup', 'completed_at' => null), array('%s'), array('%d', '%s', '%s')) : false;
        $notice = false !== $updated && $updated > 0
            ? '<div class="notice notice-success"><p>پیگیری انجام‌شده علامت‌گذاری شد.</p></div>'
            : '<div class="notice notice-error"><p>پیگیری پیدا نشد یا قبلاً بسته شده است.</p></div>';
    }

    $customer_filter = isset($_GET['customer_id']) ? absint($_GET['customer_id']) : 0;
    $where = $customer_filter ? $wpdb->prepare('WHERE a.customer_id = %d', $customer_filter) : '';
    $rows = $wpdb->get_results("SELECT a.*, u.display_name FROM {$table} a LEFT JOIN {$wpdb->users} u ON u.ID = a.customer_id {$where} ORDER BY (a.completed_at IS NULL AND a.due_at IS NOT NULL) DESC, a.due_at ASC, a.created_at DESC LIMIT 100");
    $customers = get_users(array('role' => 'subscriber', 'number' => 500, 'orderby' => 'display_name', 'order' => 'ASC', 'fields' => array('ID', 'display_name')));
    $labels = array('call' => 'تماس', 'note' => 'یادداشت', 'followup' => 'پیگیری');
    $outcome_labels = array('answered' => 'پاسخ داد', 'no_answer' => 'پاسخ نداد', 'interested' => 'علاقه‌مند', 'not_interested' => 'فعلاً تمایلی ندارد', 'pending' => 'نیازمند بررسی');
    echo '<div class="wrap"><h1>تماس‌ها و پیگیری‌های مشتریان</h1>' . $notice;
    echo '<p>یادداشت‌ها و زمان‌های پیگیری فقط برای مدیران دارای دسترسی مدیریت مشتریان نمایش داده می‌شود.</p>';
    echo '<h2>ثبت فعالیت جدید</h2><form method="post">';
    wp_nonce_field('karsabt_crm_add_activity');
    echo '<table class="form-table"><tr><th><label for="crm-customer">مشتری</label></th><td><select id="crm-customer" name="customer_id" required><option value="">انتخاب مشتری</option>';
    foreach ($customers as $customer) {
        echo '<option value="' . esc_attr($customer->ID) . '"' . selected($customer_filter, $customer->ID, false) . '>' . esc_html($customer->display_name) . ' (#' . esc_html($customer->ID) . ')</option>';
    }
    echo '</select></td></tr><tr><th><label for="crm-type">نوع فعالیت</label></th><td><select id="crm-type" name="activity_type"><option value="call">تماس</option><option value="note">یادداشت</option><option value="followup">پیگیری زمان‌دار</option></select></td></tr><tr><th><label for="crm-outcome">نتیجه تماس</label></th><td><select id="crm-outcome" name="outcome"><option value="">ثبت نشده</option><option value="answered">پاسخ داد</option><option value="no_answer">پاسخ نداد</option><option value="interested">علاقه‌مند</option><option value="not_interested">فعلاً تمایلی ندارد</option><option value="pending">نیازمند بررسی</option></select></td></tr><tr><th><label for="crm-due">زمان یادآوری</label></th><td><input id="crm-due" name="due_at" type="datetime-local"> <p class="description">برای نوع «پیگیری زمان‌دار» الزامی است.</p></td></tr><tr><th><label for="crm-details">شرح فعالیت</label></th><td><textarea id="crm-details" name="details" rows="4" class="large-text" maxlength="4000" required></textarea></td></tr></table><p><button class="button button-primary" name="karsabt_crm_add_activity" value="1">ثبت فعالیت</button></p></form>';
    echo '<h2>سوابق و پیگیری‌ها</h2><table class="widefat striped"><thead><tr><th>مشتری</th><th>نوع</th><th>نتیجه</th><th>شرح</th><th>زمان پیگیری</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>';
    if (!$rows) {
        echo '<tr><td colspan="7">فعالیتی ثبت نشده است.</td></tr>';
    }
    foreach ((array) $rows as $row) {
        $due = $row->due_at ? get_date_from_gmt($row->due_at, 'Y/m/d H:i') : '—';
        $is_open = 'followup' === $row->activity_type && empty($row->completed_at);
        $state = $is_open ? (strtotime($row->due_at . ' UTC') < time() ? 'عقب‌افتاده' : 'باز') : ('followup' === $row->activity_type ? 'انجام‌شده' : 'ثبت‌شده');
        $profile = add_query_arg(array('page' => 'karsabt-customers', 'customer_id' => $row->customer_id), admin_url('admin.php'));
        echo '<tr><td><a href="' . esc_url($profile) . '">' . esc_html($row->display_name ? $row->display_name : 'مشتری حذف‌شده') . '</a></td><td>' . esc_html($labels[$row->activity_type] ?? '—') . '</td><td>' . esc_html($outcome_labels[$row->outcome] ?? '—') . '</td><td>' . esc_html($row->details) . '</td><td>' . esc_html($due) . '</td><td>' . esc_html($state) . '</td><td>';
        if ($is_open) {
            echo '<form method="post">';
            wp_nonce_field('karsabt_crm_complete');
            echo '<input type="hidden" name="activity_id" value="' . esc_attr($row->id) . '"><button class="button" name="karsabt_crm_complete" value="1">بستن پیگیری</button></form>';
        } else {
            echo '—';
        }
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
}
