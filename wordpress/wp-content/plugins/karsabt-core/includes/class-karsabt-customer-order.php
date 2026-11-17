<?php
/**
 * جزئیات خصوصی هر درخواست در پنل مشتری.
 */
if (!defined('ABSPATH')) { exit; }

function karsabt_customer_order_detail_portal() {
    if (!is_user_logged_in()) {
        return '<p class="karsabt-form-notice">برای مشاهده جزئیات درخواست وارد حساب خود شوید.</p>';
    }

    $order_id = isset($_GET['karsabt_order_id']) ? absint($_GET['karsabt_order_id']) : 0;
    if (!$order_id) {
        return '<p class="karsabt-form-notice">برای مشاهده جزئیات، از فهرست درخواست‌های من یک درخواست را انتخاب کنید.</p>';
    }

    $order = get_post($order_id);
    $user_id = get_current_user_id();
    if (!$order || 'karsabt_order' !== $order->post_type || 'private' !== $order->post_status ||
        (int) get_post_meta($order_id, '_karsabt_customer_user_id', true) !== $user_id) {
        return '<p class="karsabt-form-notice karsabt-form-error">درخواست پیدا نشد یا دسترسی به آن مجاز نیست.</p>';
    }

    $tracking = get_post_meta($order_id, '_karsabt_tracking_code', true);
    $service_key = get_post_meta($order_id, '_karsabt_service', true);
    $status_key = get_post_meta($order_id, '_karsabt_order_status', true);
    $services = karsabt_core_services();
    $statuses = karsabt_core_statuses();
    $history = get_post_meta($order_id, '_karsabt_status_history', true);
    $history = is_array($history) ? $history : array();
    $documents = get_post_meta($order_id, '_karsabt_documents', true);
    $documents = is_array($documents) ? $documents : array();
    $messages = function_exists('karsabt_messages_get') ? karsabt_messages_get($order_id) : array();

    ob_start();
    echo '<section class="karsabt-customer-order-detail">';
    echo '<p><a href="' . esc_url(remove_query_arg('karsabt_order_id')) . '">بازگشت به پنل درخواست‌ها</a></p>';
    echo '<h3>جزئیات درخواست <bdi>' . esc_html($tracking ?: ('#' . $order_id)) . '</bdi></h3>';
    echo '<dl>';
    echo '<dt>خدمت</dt><dd>' . esc_html($services[$service_key] ?? '—') . '</dd>';
    echo '<dt>وضعیت فعلی</dt><dd>' . esc_html($statuses[$status_key] ?? 'جدید') . '</dd>';
    echo '<dt>تاریخ ثبت</dt><dd>' . esc_html(mysql2date('Y/m/d H:i', $order->post_date)) . '</dd>';
    echo '</dl>';

    echo '<h4>تاریخچه وضعیت</h4>';
    if ($history) {
        echo '<ol class="karsabt-order-history">';
        foreach ($history as $event) {
            $label = $event['label'] ?? ($statuses[$event['status'] ?? ''] ?? 'به‌روزرسانی');
            echo '<li><strong>' . esc_html($label) . '</strong> — ' . esc_html($event['time'] ?? '') . '</li>';
        }
        echo '</ol>';
    } else {
        echo '<p>تاریخچه‌ای ثبت نشده است.</p>';
    }

    echo '<h4>مدارک این درخواست</h4>';
    if ($documents) {
        echo '<ul>';
        foreach ($documents as $doc) {
            $id = (string) ($doc['id'] ?? '');
            if (!preg_match('/^[a-f0-9]{32}$/', $id)) { continue; }
            $categories = function_exists('karsabt_docs_categories') ? karsabt_docs_categories() : array();
            $states = function_exists('karsabt_docs_review_states') ? karsabt_docs_review_states() : array();
            $url = add_query_arg(array(
                'action' => 'karsabt_document_download',
                'order_id' => $order_id,
                'document_id' => $id,
                '_wpnonce' => wp_create_nonce('karsabt_document_download_' . $order_id . '_' . $id),
            ), admin_url('admin-post.php'));
            echo '<li>' . esc_html($doc['name'] ?? 'مدرک') . ' — ' .
                esc_html($categories[$doc['category'] ?? ''] ?? 'سایر مدارک') . ' — ' .
                esc_html($states[$doc['status'] ?? ''] ?? 'در انتظار بررسی');
            if (!empty($doc['admin_note'])) {
                echo '<p>توضیح مدیر: ' . esc_html($doc['admin_note']) . '</p>';
            }
            echo ' <a href="' . esc_url($url) . '">دریافت فایل</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>برای این درخواست هنوز مدرکی ثبت نشده است.</p>';
    }
    echo '<p>برای بارگذاری مدارک، از برگه «مدارک من» استفاده کنید.</p>';

    echo '<h4>گفت‌وگو با پشتیبانی</h4>';
    if ($messages) {
        echo '<ol class="karsabt-message-list">';
        foreach ($messages as $message) {
            $sender = ('admin' === ($message['sender'] ?? '')) ? 'پشتیبانی کارثبت' : 'شما';
            echo '<li><strong>' . esc_html($sender) . '</strong> — ' . esc_html($message['time'] ?? '') .
                '<p>' . nl2br(esc_html($message['text'] ?? '')) . '</p></li>';
        }
        echo '</ol>';
    } else {
        echo '<p>هنوز پیامی برای این درخواست ثبت نشده است.</p>';
    }
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="karsabt_customer_message">';
    echo '<input type="hidden" name="order_id" value="' . esc_attr($order_id) . '">';
    echo wp_nonce_field('karsabt_customer_message_' . $order_id, 'karsabt_message_nonce', true, false);
    echo '<p><label for="karsabt-detail-message">پیام به پشتیبانی</label><textarea id="karsabt-detail-message" name="message" rows="3" maxlength="2000" required></textarea></p>';
    echo '<button type="submit">ارسال پیام</button></form>';
    echo '</section>';
    return ob_get_clean();
}
add_shortcode('karsabt_customer_order_detail', 'karsabt_customer_order_detail_portal');
