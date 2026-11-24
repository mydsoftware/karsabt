<?php
/**
 * مدیریت تعرفه‌ها، فاکتورها و پرداخت‌های کارثبت.
 */
if (!defined('ABSPATH')) {
    exit;
}

function karsabt_finance_table() {
    global $wpdb;
    return $wpdb->prefix . 'karsabt_payments';
}

function karsabt_finance_install() {
    global $wpdb;
    $table = karsabt_finance_table();
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        invoice_id bigint(20) unsigned NOT NULL,
        amount bigint(20) unsigned NOT NULL,
        method varchar(30) NOT NULL DEFAULT 'cash',
        reference varchar(190) NOT NULL DEFAULT '',
        note text NOT NULL,
        created_by bigint(20) unsigned NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY invoice_id (invoice_id),
        KEY created_at (created_at)
    ) {$charset} ENGINE=InnoDB;";
    dbDelta($sql);
    update_option('karsabt_finance_db_version', '1');
}
add_action('admin_init', 'karsabt_finance_maybe_install');
function karsabt_finance_maybe_install() {
    if ('1' !== get_option('karsabt_finance_db_version')) {
        karsabt_finance_install();
    }
}

function karsabt_finance_register_invoice_type() {
    register_post_type('karsabt_invoice', array(
        'labels' => array('name' => 'فاکتورها', 'singular_name' => 'فاکتور'),
        'public' => false,
        'show_ui' => false,
        'show_in_rest' => false,
        'supports' => array('title'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'capabilities' => array(
            'edit_post' => 'manage_options', 'read_post' => 'manage_options',
            'delete_post' => 'manage_options', 'edit_posts' => 'manage_options',
            'edit_others_posts' => 'manage_options', 'publish_posts' => 'manage_options',
            'read_private_posts' => 'manage_options', 'delete_posts' => 'manage_options',
            'create_posts' => 'manage_options',
        ),
    ));
}
add_action('init', 'karsabt_finance_register_invoice_type');

function karsabt_finance_menu() {
    add_menu_page('امور مالی کارثبت', 'امور مالی', 'manage_karsabt_finance', 'karsabt-finance', 'karsabt_finance_render_invoices', 'dashicons-money-alt', 27);
    add_submenu_page('karsabt-finance', 'فاکتورها', 'فاکتورها', 'manage_karsabt_finance', 'karsabt-finance', 'karsabt_finance_render_invoices');
    add_submenu_page('karsabt-finance', 'تعرفه خدمات', 'تعرفه خدمات', 'manage_karsabt_finance', 'karsabt-tariffs', 'karsabt_finance_render_tariffs');
    add_submenu_page('karsabt-finance', 'ثبت پرداخت', 'ثبت پرداخت', 'manage_karsabt_finance', 'karsabt-payments', 'karsabt_finance_render_payments');
}
add_action('admin_menu', 'karsabt_finance_menu');

function karsabt_finance_money($amount) {
    return number_format_i18n((int) $amount) . ' ریال';
}

function karsabt_finance_invoice_paid($invoice_id) {
    global $wpdb;
    $table = karsabt_finance_table();
    return (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$table} WHERE invoice_id = %d", $invoice_id));
}

function karsabt_finance_render_tariffs() {
    if (!current_user_can('manage_karsabt_finance')) { wp_die('دسترسی مجاز نیست.'); }
    $services = karsabt_core_services();
    $tariffs = get_option('karsabt_service_tariffs', array());
    if (!is_array($tariffs)) { $tariffs = array(); }
    if (isset($_POST['karsabt_save_tariffs'])) {
        check_admin_referer('karsabt_save_tariffs');
        $new = array();
        foreach ($services as $key => $label) {
            $raw = isset($_POST['tariffs'][$key]) ? wp_unslash($_POST['tariffs'][$key]) : '';
            $raw = preg_replace('/[^0-9]/', '', (string) $raw);
            $new[$key] = '' === $raw ? 0 : min((int) $raw, 2000000000000);
        }
        update_option('karsabt_service_tariffs', $new, false);
        $tariffs = $new;
        echo '<div class="notice notice-success"><p>تعرفه‌ها ذخیره شد.</p></div>';
    }
    echo '<div class="wrap"><h1>تعرفه خدمات</h1><p>مبالغ این نسخه بر حسب ریال ثبت می‌شوند. تعرفه فقط پیشنهاد قیمت است و هنگام صدور فاکتور به‌صورت ثابت در فاکتور ذخیره می‌شود.</p><form method="post">';
    wp_nonce_field('karsabt_save_tariffs');
    echo '<table class="form-table">';
    foreach ($services as $key => $label) {
        $value = isset($tariffs[$key]) ? (int) $tariffs[$key] : 0;
        echo '<tr><th><label for="tariff-' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td><input type="number" min="0" step="1" class="regular-text" id="tariff-' . esc_attr($key) . '" name="tariffs[' . esc_attr($key) . ']" value="' . esc_attr((string) $value) . '"> ریال</td></tr>';
    }
    echo '</table><p><button class="button button-primary" name="karsabt_save_tariffs" value="1">ذخیره تعرفه‌ها</button></p></form></div>';
}

function karsabt_finance_render_invoices() {
    if (!current_user_can('manage_karsabt_finance')) { wp_die('دسترسی مجاز نیست.'); }
    $notice = '';
    if (isset($_POST['karsabt_create_invoice'])) {
        check_admin_referer('karsabt_create_invoice');
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $amount_raw = isset($_POST['amount']) ? preg_replace('/[^0-9]/', '', (string) wp_unslash($_POST['amount'])) : '';
        $amount = '' === $amount_raw ? 0 : (int) $amount_raw;
        $description = isset($_POST['description']) ? sanitize_text_field(wp_unslash($_POST['description'])) : '';
        $order = $order_id ? get_post($order_id) : null;
        if (!$order || 'karsabt_order' !== $order->post_type || 'private' !== $order->post_status || $amount < 1 || $amount > 2000000000000 || '' === $description) {
            $notice = '<div class="notice notice-error"><p>اطلاعات فاکتور معتبر نیست.</p></div>';
        } else {
            $invoice_id = wp_insert_post(array('post_type' => 'karsabt_invoice', 'post_status' => 'private', 'post_title' => 'فاکتور درخواست ' . $order_id), true);
            if (is_wp_error($invoice_id) || !$invoice_id) {
                $notice = '<div class="notice notice-error"><p>صدور فاکتور انجام نشد.</p></div>';
            } else {
                $number = 'KRF-' . gmdate('ymd') . '-' . str_pad((string) $invoice_id, 6, '0', STR_PAD_LEFT);
                update_post_meta($invoice_id, '_karsabt_invoice_number', $number);
                update_post_meta($invoice_id, '_karsabt_invoice_order_id', $order_id);
                update_post_meta($invoice_id, '_karsabt_invoice_amount', $amount);
                update_post_meta($invoice_id, '_karsabt_invoice_description', $description);
                update_post_meta($invoice_id, '_karsabt_invoice_created_at', current_time('mysql'));
                update_post_meta($invoice_id, '_karsabt_invoice_customer_name', get_post_meta($order_id, '_karsabt_customer_name', true));
                update_post_meta($invoice_id, '_karsabt_invoice_customer_phone', get_post_meta($order_id, '_karsabt_customer_phone', true));
                update_post_meta($invoice_id, '_karsabt_invoice_customer_user_id', absint(get_post_meta($order_id, '_karsabt_customer_user_id', true)));
                $notice = '<div class="notice notice-success"><p>فاکتور ' . esc_html($number) . ' صادر شد.</p></div>';
            }
        }
    }
    $orders = get_posts(array('post_type' => 'karsabt_order', 'post_status' => 'private', 'posts_per_page' => 200, 'orderby' => 'date', 'order' => 'DESC'));
    $invoices = get_posts(array('post_type' => 'karsabt_invoice', 'post_status' => 'private', 'posts_per_page' => 100, 'orderby' => 'date', 'order' => 'DESC'));
    echo '<div class="wrap"><h1>فاکتورها</h1>' . $notice . '<h2>صدور فاکتور</h2><form method="post">';
    wp_nonce_field('karsabt_create_invoice');
    echo '<table class="form-table"><tr><th><label for="karsabt-invoice-order">درخواست</label></th><td><select id="karsabt-invoice-order" name="order_id" required><option value="">انتخاب درخواست</option>';
    foreach ($orders as $order) {
        $code = get_post_meta($order->ID, '_karsabt_tracking_code', true);
        $customer = get_post_meta($order->ID, '_karsabt_customer_name', true);
        echo '<option value="' . esc_attr($order->ID) . '">' . esc_html(($code ? $code : '#' . $order->ID) . ' — ' . $customer) . '</option>';
    }
    echo '</select></td></tr><tr><th><label for="karsabt-invoice-amount">مبلغ کل (ریال)</label></th><td><input id="karsabt-invoice-amount" name="amount" type="number" min="1" max="2000000000000" step="1" required></td></tr><tr><th><label for="karsabt-invoice-description">شرح فاکتور</label></th><td><input class="regular-text" id="karsabt-invoice-description" name="description" maxlength="250" required></td></tr></table><p><button class="button button-primary" name="karsabt_create_invoice" value="1">صدور فاکتور</button></p></form>';
    echo '<h2>فهرست فاکتورها</h2><table class="widefat striped"><thead><tr><th>شماره</th><th>مشتری</th><th>مبلغ</th><th>پرداخت‌شده</th><th>مانده</th><th>وضعیت</th></tr></thead><tbody>';
    foreach ($invoices as $invoice) {
        $total = (int) get_post_meta($invoice->ID, '_karsabt_invoice_amount', true);
        $paid = karsabt_finance_invoice_paid($invoice->ID);
        $remaining = max(0, $total - $paid);
        $state = $paid >= $total ? 'تسویه‌شده' : ($paid > 0 ? 'پرداخت بخشی' : 'پرداخت‌نشده');
        echo '<tr><td><bdi>' . esc_html(get_post_meta($invoice->ID, '_karsabt_invoice_number', true)) . '</bdi></td><td>' . esc_html(get_post_meta($invoice->ID, '_karsabt_invoice_customer_name', true)) . '</td><td>' . esc_html(karsabt_finance_money($total)) . '</td><td>' . esc_html(karsabt_finance_money($paid)) . '</td><td>' . esc_html(karsabt_finance_money($remaining)) . '</td><td>' . esc_html($state) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

function karsabt_finance_render_payments() {
    if (!current_user_can('manage_karsabt_finance')) { wp_die('دسترسی مجاز نیست.'); }
    global $wpdb;
    $table = karsabt_finance_table();
    $notice = '';
    if (isset($_POST['karsabt_record_payment'])) {
        check_admin_referer('karsabt_record_payment');
        $invoice_id = isset($_POST['invoice_id']) ? absint($_POST['invoice_id']) : 0;
        $raw = isset($_POST['amount']) ? preg_replace('/[^0-9]/', '', (string) wp_unslash($_POST['amount'])) : '';
        $amount = '' === $raw ? 0 : (int) $raw;
        $method = isset($_POST['method']) ? sanitize_key(wp_unslash($_POST['method'])) : '';
        $reference = isset($_POST['reference']) ? sanitize_text_field(wp_unslash($_POST['reference'])) : '';
        $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';
        $methods = array('cash' => 'نقدی', 'card' => 'کارت‌به‌کارت/کارت‌خوان', 'transfer' => 'واریز بانکی', 'other' => 'سایر');
        $invoice = $invoice_id ? get_post($invoice_id) : null;
        $total = $invoice ? (int) get_post_meta($invoice_id, '_karsabt_invoice_amount', true) : 0;
        $paid = $invoice ? karsabt_finance_invoice_paid($invoice_id) : 0;
        if (!$invoice || 'karsabt_invoice' !== $invoice->post_type || 'private' !== $invoice->post_status || $amount < 1 || $amount > 2000000000000 || !isset($methods[$method]) || $amount > ($total - $paid)) {
            $notice = '<div class="notice notice-error"><p>اطلاعات پرداخت معتبر نیست یا مبلغ از مانده فاکتور بیشتر است.</p></div>';
        } else {
            // تراکنش فقط وقتی معتبر است که جدول پرداخت‌ها InnoDB باشد.
            $started = $wpdb->query('START TRANSACTION');
            if (false === $started) {
                $notice = '<div class="notice notice-error"><p>شروع تراکنش پایگاه داده ناموفق بود؛ پرداخت ثبت نشد.</p></div>';
            } else {
                // قفل ردیف فاکتور و بازخوانی مبلغ/پرداخت‌ها داخل تراکنش.
                $locked_invoice = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE ID = %d FOR UPDATE", $invoice_id));
                if (!$locked_invoice) {
                    $wpdb->query('ROLLBACK');
                    $notice = '<div class="notice notice-error"><p>قفل فاکتور برقرار نشد؛ پرداخت ثبت نشد.</p></div>';
                } else {
                    $total = (int) get_post_meta($invoice_id, '_karsabt_invoice_amount', true);
                    $fresh_paid = karsabt_finance_invoice_paid($invoice_id);
                    if ($amount > max(0, $total - $fresh_paid)) {
                        $wpdb->query('ROLLBACK');
                        $notice = '<div class="notice notice-error"><p>مانده فاکتور تغییر کرده است؛ صفحه را تازه‌سازی و مبلغ را دوباره بررسی کنید.</p></div>';
                    } else {
                $inserted = $wpdb->insert($table, array(
                    'invoice_id' => $invoice_id, 'amount' => $amount, 'method' => $method,
                    'reference' => $reference, 'note' => $note, 'created_by' => get_current_user_id(),
                    'created_at' => current_time('mysql'),
                ), array('%d','%d','%s','%s','%s','%d','%s'));
                if (false !== $inserted) {
                    $wpdb->query('COMMIT');
                    $notice = '<div class="notice notice-success"><p>پرداخت ثبت شد.</p></div>';
                } else {
                    $wpdb->query('ROLLBACK');
                    $notice = '<div class="notice notice-error"><p>ثبت پرداخت در پایگاه داده انجام نشد.</p></div>';
                    }
                }
            }
        }
    }
        }
    $invoices = get_posts(array('post_type' => 'karsabt_invoice', 'post_status' => 'private', 'posts_per_page' => 200, 'orderby' => 'date', 'order' => 'DESC'));
    echo '<div class="wrap"><h1>ثبت پرداخت</h1>' . $notice . '<form method="post">';
    wp_nonce_field('karsabt_record_payment');
    echo '<table class="form-table"><tr><th><label for="karsabt-payment-invoice">فاکتور</label></th><td><select id="karsabt-payment-invoice" name="invoice_id" required><option value="">انتخاب فاکتور دارای مانده</option>';
    foreach ($invoices as $invoice) {
        $total = (int) get_post_meta($invoice->ID, '_karsabt_invoice_amount', true);
        $remaining = $total - karsabt_finance_invoice_paid($invoice->ID);
        if ($remaining > 0) {
            echo '<option value="' . esc_attr($invoice->ID) . '">' . esc_html(get_post_meta($invoice->ID, '_karsabt_invoice_number', true) . ' — مانده: ' . karsabt_finance_money($remaining)) . '</option>';
        }
    }
    echo '</select></td></tr><tr><th><label for="karsabt-payment-amount">مبلغ (ریال)</label></th><td><input id="karsabt-payment-amount" name="amount" type="number" min="1" max="2000000000000" step="1" required></td></tr><tr><th>روش پرداخت</th><td><select name="method"><option value="cash">نقدی</option><option value="card">کارت‌به‌کارت/کارت‌خوان</option><option value="transfer">واریز بانکی</option><option value="other">سایر</option></select></td></tr><tr><th><label for="karsabt-payment-reference">شماره پیگیری (اختیاری)</label></th><td><input class="regular-text" id="karsabt-payment-reference" name="reference" maxlength="190"></td></tr><tr><th><label for="karsabt-payment-note">یادداشت (اختیاری)</label></th><td><textarea id="karsabt-payment-note" name="note" rows="3" class="large-text" maxlength="2000"></textarea></td></tr></table><p><button class="button button-primary" name="karsabt_record_payment" value="1">ثبت قطعی پرداخت</button></p></form>';
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC LIMIT 100");
    echo '<h2>آخرین پرداخت‌ها</h2><table class="widefat striped"><thead><tr><th>تاریخ</th><th>فاکتور</th><th>مبلغ</th><th>روش</th><th>پیگیری</th><th>ثبت‌کننده</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $methods = array('cash' => 'نقدی', 'card' => 'کارت‌به‌کارت/کارت‌خوان', 'transfer' => 'واریز بانکی', 'other' => 'سایر');
        echo '<tr><td>' . esc_html($row->created_at) . '</td><td>' . esc_html(get_post_meta($row->invoice_id, '_karsabt_invoice_number', true)) . '</td><td>' . esc_html(karsabt_finance_money($row->amount)) . '</td><td>' . esc_html(isset($methods[$row->method]) ? $methods[$row->method] : 'سایر') . '</td><td>' . esc_html($row->reference) . '</td><td>' . esc_html((string) $row->created_by) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}


/**
 * فاکتورها و گردش پرداخت فقط برای حساب مشتری واردشده.
 * فاکتورهای مهمان یا فاکتورهای متصل به حساب دیگران هرگز نمایش داده نمی‌شوند.
 */
function karsabt_finance_customer_account() {
    if (!is_user_logged_in()) {
        return '<div class="karsabt-form-notice">برای مشاهده فاکتورها و مانده حساب، ابتدا وارد حساب کاربری شوید.</div>';
    }

    $user_id = get_current_user_id();
    $invoices = get_posts(array(
        'post_type' => 'karsabt_invoice',
        'post_status' => 'private',
        'posts_per_page' => 100,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_key' => '_karsabt_invoice_customer_user_id',
        'meta_value' => (string) $user_id,
    ));

    if (!$invoices) {
        return '<div class="karsabt-form-notice">هنوز فاکتوری به حساب شما متصل نشده است.</div>';
    }

    global $wpdb;
    $table = karsabt_finance_table();
    $total_all = 0;
    $paid_all = 0;
    ob_start();
    echo '<section class="karsabt-customer-finance"><h3>فاکتورها و مانده حساب</h3>';
    echo '<div class="karsabt-dashboard-table-wrap"><table class="karsabt-dashboard-table"><thead><tr><th>شماره فاکتور</th><th>شرح</th><th>مبلغ</th><th>پرداخت‌شده</th><th>مانده</th><th>وضعیت</th></tr></thead><tbody>';

    foreach ($invoices as $invoice) {
        // کنترل مالکیت دوباره در زمان نمایش هر ردیف.
        if ((int) get_post_meta($invoice->ID, '_karsabt_invoice_customer_user_id', true) !== $user_id) {
            continue;
        }
        $total = (int) get_post_meta($invoice->ID, '_karsabt_invoice_amount', true);
        $paid = karsabt_finance_invoice_paid($invoice->ID);
        $remaining = max(0, $total - $paid);
        $total_all += $total;
        $paid_all += $paid;
        $status = $paid >= $total ? 'تسویه‌شده' : ($paid > 0 ? 'پرداخت بخشی' : 'پرداخت‌نشده');

        echo '<tr><td><bdi>' . esc_html(get_post_meta($invoice->ID, '_karsabt_invoice_number', true)) . '</bdi></td>';
        echo '<td>' . esc_html(get_post_meta($invoice->ID, '_karsabt_invoice_description', true)) . '</td>';
        echo '<td>' . esc_html(karsabt_finance_money($total)) . '</td>';
        echo '<td>' . esc_html(karsabt_finance_money($paid)) . '</td>';
        echo '<td>' . esc_html(karsabt_finance_money($remaining)) . '</td>';
        echo '<td>' . esc_html($status) . '</td></tr>';

        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT amount, method, created_at FROM {$table} WHERE invoice_id = %d ORDER BY id DESC",
            $invoice->ID
        ));
        if ($payments) {
            $method_labels = array('cash' => 'نقدی', 'card' => 'کارت‌به‌کارت/کارت‌خوان', 'transfer' => 'واریز بانکی', 'other' => 'سایر');
            echo '<tr><td colspan="6"><details><summary>مشاهده سوابق پرداخت</summary><ul>';
            foreach ($payments as $payment) {
                $method = isset($method_labels[$payment->method]) ? $method_labels[$payment->method] : 'سایر';
                echo '<li>' . esc_html(mysql2date('Y/m/d H:i', $payment->created_at)) . ' — ' . esc_html(karsabt_finance_money($payment->amount)) . ' — ' . esc_html($method) . '</li>';
            }
            echo '</ul></details></td></tr>';
        }
    }

    $remaining_all = max(0, $total_all - $paid_all);
    echo '</tbody></table></div>';
    echo '<p><strong>جمع فاکتورها:</strong> ' . esc_html(karsabt_finance_money($total_all)) . '</p>';
    echo '<p><strong>جمع پرداخت‌ها:</strong> ' . esc_html(karsabt_finance_money($paid_all)) . '</p>';
    echo '<p><strong>مانده کل:</strong> ' . esc_html(karsabt_finance_money($remaining_all)) . '</p>';
    echo '<p class="karsabt-form-privacy">مبالغ بر حسب ریال هستند. پرداخت‌های ثبت‌شده در این صفحه صرفاً سوابق ثبت‌شده توسط کارثبت را نشان می‌دهند.</p></section>';
    return ob_get_clean();
}
add_shortcode('karsabt_customer_finance', 'karsabt_finance_customer_account');
