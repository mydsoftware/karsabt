<?php
/**
 * گفت‌وگوی خصوصی مشتری و مدیر، متصل به درخواست.
 */
if (!defined('ABSPATH')) { exit; }

/**
 * قفل نوشتن هر گفت‌وگو برای جلوگیری از بازنویسی پیام‌های هم‌زمان.
 * قفل به اتصال فعلی دیتابیس وابسته است و با پایان اتصال آزاد می‌شود.
 */
function karsabt_messages_lock($order_id) {
    global $wpdb;
    $name = 'karsabt_msg_' . substr(hash('sha256', (string) absint($order_id)), 0, 40);
    return 1 === (int) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $name)) ? $name : false;
}

function karsabt_messages_unlock($lock_name) {
    global $wpdb;
    if (is_string($lock_name) && '' !== $lock_name) {
        $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
    }
}

function karsabt_messages_get($order_id) {
    $items = get_post_meta($order_id, '_karsabt_messages', true);
    return is_array($items) ? $items : array();
}

function karsabt_messages_customer_portal() {
    if (!is_user_logged_in()) {
        return '<p class="karsabt-form-notice">برای مشاهده پیام‌ها ابتدا وارد حساب خود شوید.</p>';
    }
    $user_id = get_current_user_id();
    $orders = get_posts(array(
        'post_type' => 'karsabt_order',
        'post_status' => 'private',
        'posts_per_page' => 50,
        'meta_key' => '_karsabt_customer_user_id',
        'meta_value' => (string) $user_id,
        'orderby' => 'date',
        'order' => 'DESC',
    ));
    $html = '<section class="karsabt-customer-messages"><h3>پیام‌های درخواست‌های من</h3>';
    if (!$orders) {
        return $html . '<p>درخواستی به حساب شما متصل نیست.</p></section>';
    }
    foreach ($orders as $order) {
        if ((int) get_post_meta($order->ID, '_karsabt_customer_user_id', true) !== $user_id) { continue; }
        $tracking = get_post_meta($order->ID, '_karsabt_tracking_code', true);
        $messages = karsabt_messages_get($order->ID);
        $html .= '<article class="karsabt-message-thread"><h4>درخواست ' . esc_html($tracking ?: ('#' . $order->ID)) . '</h4>';
        if ($messages) {
            $html .= '<ol class="karsabt-message-list">';
            foreach ($messages as $message) {
                $sender = ('admin' === ($message['sender'] ?? '')) ? 'پشتیبانی کارثبت' : 'شما';
                $html .= '<li><strong>' . esc_html($sender) . '</strong> <small>' . esc_html($message['time'] ?? '') . '</small><p>' . nl2br(esc_html($message['text'] ?? '')) . '</p></li>';
            }
            $html .= '</ol>';
        } else {
            $html .= '<p>هنوز پیامی ثبت نشده است.</p>';
        }
        $html .= '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        $html .= '<input type="hidden" name="action" value="karsabt_customer_message">';
        $html .= '<input type="hidden" name="order_id" value="' . esc_attr($order->ID) . '">';
        $html .= wp_nonce_field('karsabt_customer_message_' . $order->ID, 'karsabt_message_nonce', true, false);
        $html .= '<p><label>پیام شما</label><textarea name="message" rows="3" maxlength="2000" required></textarea></p>';
        $html .= '<button type="submit">ارسال پیام</button></form></article>';
    }
    return $html . '</section>';
}
add_shortcode('karsabt_customer_messages', 'karsabt_messages_customer_portal');

function karsabt_messages_customer_submit() {
    if (!is_user_logged_in()) { wp_die('برای ارسال پیام وارد حساب خود شوید.', '', array('response' => 403)); }
    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
    $nonce = sanitize_text_field(wp_unslash($_POST['karsabt_message_nonce'] ?? ''));
    if (!$order_id || !wp_verify_nonce($nonce, 'karsabt_customer_message_' . $order_id)) {
        wp_die('درخواست معتبر نیست.', '', array('response' => 403));
    }
    $order = get_post($order_id);
    if (!$order || 'karsabt_order' !== $order->post_type || 'private' !== $order->post_status ||
        (int) get_post_meta($order_id, '_karsabt_customer_user_id', true) !== get_current_user_id()) {
        wp_die('دسترسی به این درخواست مجاز نیست.', '', array('response' => 403));
    }
    $rate_key = 'karsabt_msg_rate_' . get_current_user_id();
    $sent_count = (int) get_transient($rate_key);
    if ($sent_count >= 20) {
        wp_die('سقف ارسال پیام در یک ساعت تکمیل شده است.', '', array('response' => 429));
    }
    $text = isset($_POST['message']) ? trim(sanitize_textarea_field(wp_unslash($_POST['message']))) : '';
    if ('' === $text || strlen($text) > 8000) {
        wp_die('متن پیام خالی یا بیش از حد طولانی است.', '', array('response' => 400));
    }
    $lock_name = karsabt_messages_lock($order_id);
    if (!$lock_name) { wp_die('گفت‌وگو در حال به‌روزرسانی است؛ چند لحظه دیگر دوباره تلاش کنید.', '', array('response' => 503)); }
    // هر worker PHP ممکن است cache محلی قدیمی داشته باشد؛ پس از قفل، متادیتا را از DB تازه بخوان.
    wp_cache_delete($order_id, 'post_meta');
    $messages = karsabt_messages_get($order_id);
    if (count($messages) >= 200) {
        karsabt_messages_unlock($lock_name);
        wp_die('ظرفیت گفت‌وگوی این درخواست تکمیل شده است.', '', array('response' => 429));
    }
    $messages[] = array(
        'id' => bin2hex(random_bytes(12)),
        'sender' => 'customer',
        'user_id' => get_current_user_id(),
        'text' => $text,
        'time' => current_time('mysql'),
    );
    $saved = update_post_meta($order_id, '_karsabt_messages', $messages);
    karsabt_messages_unlock($lock_name);
    if (false === $saved) { wp_die('ذخیره پیام انجام نشد؛ دوباره تلاش کنید.', '', array('response' => 500)); }
    do_action('karsabt_customer_message_created', $order_id);
    set_transient($rate_key, $sent_count + 1, HOUR_IN_SECONDS);
    wp_safe_redirect(wp_get_referer() ?: home_url('/'));
    exit;
}
add_action('admin_post_karsabt_customer_message', 'karsabt_messages_customer_submit');

function karsabt_messages_admin_page() {
    if (!current_user_can('manage_options')) { wp_die('دسترسی مجاز نیست.'); }
    $notice = '';
    if ('POST' === strtoupper($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['karsabt_admin_message_submit'])) {
        check_admin_referer('karsabt_admin_message');
        $order_id = absint($_POST['order_id'] ?? 0);
        $text = trim(sanitize_textarea_field(wp_unslash($_POST['message'] ?? '')));
        $order = get_post($order_id);
        if ($order && 'karsabt_order' === $order->post_type && 'private' === $order->post_status &&
            '' !== $text && strlen($text) <= 8000) {
            $lock_name = karsabt_messages_lock($order_id);
            if (!$lock_name) {
                $notice = '<div class="notice notice-error"><p>گفت‌وگو در حال به‌روزرسانی است؛ دوباره تلاش کنید.</p></div>';
            } else {
                // هر worker PHP ممکن است cache محلی قدیمی داشته باشد؛ پس از قفل، متادیتا را از DB تازه بخوان.
                wp_cache_delete($order_id, 'post_meta');
                $messages = karsabt_messages_get($order_id);
                if (count($messages) < 200) {
                $messages[] = array(
                    'id' => bin2hex(random_bytes(12)),
                    'sender' => 'admin',
                    'user_id' => get_current_user_id(),
                    'text' => $text,
                    'time' => current_time('mysql'),
                );
                $saved = update_post_meta($order_id, '_karsabt_messages', $messages);
                $notice = false === $saved ? '<div class="notice notice-error"><p>ذخیره پاسخ انجام نشد.</p></div>' : '<div class="notice notice-success"><p>پاسخ ثبت شد.</p></div>';
                } else {
                    $notice = '<div class="notice notice-error"><p>ظرفیت گفت‌وگو تکمیل شده است.</p></div>';
                }
                karsabt_messages_unlock($lock_name);
                if (false !== $saved && function_exists('karsabt_notify_sms')) {
                    $mobile = get_post_meta($order_id, '_karsabt_customer_phone', true);
                    $tracking = get_post_meta($order_id, '_karsabt_tracking_code', true);
                    karsabt_notify_sms($mobile, 'پاسخ جدیدی برای درخواست ' . $tracking . ' در پنل کارثبت ثبت شد.', 'admin_replied', array('order_id' => $order_id));
                }
            }
        } else {
            $notice = '<div class="notice notice-error"><p>درخواست یا متن پیام معتبر نیست.</p></div>';
        }
    }

    $orders = get_posts(array(
        'post_type' => 'karsabt_order',
        'post_status' => 'private',
        'posts_per_page' => 100,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(array('key' => '_karsabt_messages', 'compare' => 'EXISTS')),
    ));
    echo '<div class="wrap"><h1>پیام‌های مشتریان</h1>' . $notice;
    if (!$orders) { echo '<p>هنوز گفت‌وگویی ثبت نشده است.</p></div>'; return; }
    foreach ($orders as $order) {
        $messages = karsabt_messages_get($order->ID);
        $tracking = get_post_meta($order->ID, '_karsabt_tracking_code', true);
        $name = get_post_meta($order->ID, '_karsabt_customer_name', true);
        echo '<div class="card" style="max-width:900px;padding:14px;margin:14px 0">';
        echo '<h2>' . esc_html($tracking ?: ('درخواست #' . $order->ID)) . '</h2><p>مشتری: ' . esc_html($name) . '</p>';
        if ($messages) {
            echo '<ol>';
            foreach ($messages as $message) {
                $sender = ('admin' === ($message['sender'] ?? '')) ? 'مدیر' : 'مشتری';
                echo '<li><strong>' . esc_html($sender) . '</strong> — ' . esc_html($message['time'] ?? '') . '<p>' . nl2br(esc_html($message['text'] ?? '')) . '</p></li>';
            }
            echo '</ol>';
        }
        echo '<form method="post">';
        wp_nonce_field('karsabt_admin_message');
        echo '<input type="hidden" name="order_id" value="' . esc_attr($order->ID) . '">';
        echo '<textarea class="large-text" name="message" rows="3" maxlength="2000" required placeholder="پاسخ برای مشتری"></textarea>';
        echo '<p><button class="button button-primary" type="submit" name="karsabt_admin_message_submit" value="1">ارسال پاسخ</button></p></form></div>';
    }
    echo '</div>';
}

function karsabt_messages_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=karsabt_order',
        'پیام‌های مشتریان',
        'پیام‌های مشتریان',
        'manage_options',
        'karsabt-messages',
        'karsabt_messages_admin_page'
    );
}
add_action('admin_menu', 'karsabt_messages_admin_menu');
