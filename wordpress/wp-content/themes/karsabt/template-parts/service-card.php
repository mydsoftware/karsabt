<?php
/**
 * کارت خدمت صفحه اصلی.
 *
 * @package Karsabt
 */
if (!defined('ABSPATH')) {
    exit;
}

$service = isset($args['service']) && is_array($args['service']) ? $args['service'] : array();
$icon = isset($service[0]) ? $service[0] : '📄';
$title = isset($service[1]) ? $service[1] : '';
$description = isset($service[2]) ? $service[2] : '';
$url = isset($service[3]) ? $service[3] : '#contact';
?>
<a class="service-card" href="<?php echo esc_url($url); ?>">
    <div class="service-icon" aria-hidden="true"><?php echo esc_html($icon); ?></div>
    <h3><?php echo esc_html($title); ?></h3>
    <p><?php echo esc_html($description); ?></p>
</a>
