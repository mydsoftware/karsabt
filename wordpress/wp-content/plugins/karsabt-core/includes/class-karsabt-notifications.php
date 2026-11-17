<?php
/**
 * رابط استاندارد اعلان‌های پیامکی کارثبت.
 * اتصال‌دهنده پیامک باید به action/filter های تعریف‌شده متصل شود.
 */
if (!defined('ABSPATH')) { exit; }

/**
 * ارسال رویداد پیامکی به ماژول پیامک خارجی.
 * اطلاعات حساس درخواست و متن مکاتبات در اعلان قرار نمی‌گیرند.
 */
function karsabt_notify_sms($mobile, $message, $event, $context = array()) {
    $mobile = function_exists('karsabt_core_normalize_mobile') ? karsabt_core_normalize_mobile($mobile) : '';
    if (!$mobile || !is_string($message) || '' === trim($message)) {
        return false;
    }
    $payload = array(
        'mobile' => $mobile,
        'message' => sanitize_text_field($message),
        'event' => sanitize_key($event),
        'context' => is_array($context) ? array_map('absint', $context) : array(),
    );
    /**
     * ماژول پیامک با این action اعلان‌های رویدادی را دریافت می‌کند.
     * نمونه: add_action('karsabt_sms_notification', 'my_sms_handler', 10, 1);
     */
    do_action('karsabt_sms_notification', $payload);
    return true;
}

/** اعلان ثبت درخواست جدید به مشتری و مدیر از طریق دو رویداد مستقل. */
function karsabt_notify_order_created($order_id) {
    $mobile = get_post_meta($order_id, '_karsabt_customer_phone', true);
    $tracking = get_post_meta($order_id, '_karsabt_tracking_code', true);
    karsabt_notify_sms($mobile, 'درخواست شما در کارثبت ثبت شد. کد پیگیری: ' . $tracking, 'order_created', array('order_id' => $order_id));
}
