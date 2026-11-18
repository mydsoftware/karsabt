<?php
/**
 * ابزارهای مدیریتی فهرست درخواست‌ها.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * فقط فهرست درخواست‌های کارثبت را در پیشخوان فیلتر می‌کند.
 */
function karsabt_core_filter_order_admin_list($query) {
    if (!is_admin() || !$query->is_main_query() || 'karsabt_order' !== $query->get('post_type')) {
        return;
    }

    $meta_query = (array) $query->get('meta_query');

    if (!empty($_GET['karsabt_status'])) {
        $status = sanitize_key(wp_unslash($_GET['karsabt_status']));
        if (isset(karsabt_core_statuses()[$status])) {
            $meta_query[] = array(
                'key'     => '_karsabt_order_status',
                'value'   => $status,
                'compare' => '=',
            );
        }
    }

    if (!empty($_GET['karsabt_service'])) {
        $service = sanitize_key(wp_unslash($_GET['karsabt_service']));
        if (isset(karsabt_core_services()[$service])) {
            $meta_query[] = array(
                'key'     => '_karsabt_service',
                'value'   => $service,
                'compare' => '=',
            );
        }
    }

    if ($meta_query) {
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'karsabt_core_filter_order_admin_list');

/**
 * فهرست انتخاب وضعیت و خدمت را بالای جدول درخواست‌ها نمایش می‌دهد.
 */
function karsabt_core_order_admin_filters($post_type) {
    if ('karsabt_order' !== $post_type) {
        return;
    }

    $selected_status = isset($_GET['karsabt_status']) ? sanitize_key(wp_unslash($_GET['karsabt_status'])) : '';
    $selected_service = isset($_GET['karsabt_service']) ? sanitize_key(wp_unslash($_GET['karsabt_service'])) : '';

    echo '<label class="screen-reader-text" for="karsabt-status-filter">فیلتر وضعیت</label>';
    echo '<select id="karsabt-status-filter" name="karsabt_status">';
    echo '<option value="">همه وضعیت‌ها</option>';
    foreach (karsabt_core_statuses() as $key => $label) {
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr($key),
            selected($selected_status, $key, false),
            esc_html($label)
        );
    }
    echo '</select>';

    echo '<label class="screen-reader-text" for="karsabt-service-filter">فیلتر خدمت</label>';
    echo '<select id="karsabt-service-filter" name="karsabt_service">';
    echo '<option value="">همه خدمات</option>';
    foreach (karsabt_core_services() as $key => $label) {
        printf(
            '<option value="%1$s" %2$s>%3$s</option>',
            esc_attr($key),
            selected($selected_service, $key, false),
            esc_html($label)
        );
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'karsabt_core_order_admin_filters');

/**
 * جست‌وجوی پیشخوان را برای نام و شماره تماس مشتری نیز فعال می‌کند.
 */
function karsabt_core_search_order_customer_meta($search, $query) {
    global $wpdb;

    if (!is_admin() || !$query->is_main_query() || 'karsabt_order' !== $query->get('post_type')) {
        return $search;
    }

    $term = $query->get('s');
    if (!is_string($term) || '' === trim($term)) {
        return $search;
    }

    $like = '%' . $wpdb->esc_like(trim($term)) . '%';
    $meta_search = $wpdb->prepare(
        " OR EXISTS (
            SELECT 1
            FROM {$wpdb->postmeta} AS karsabt_search_meta
            WHERE karsabt_search_meta.post_id = {$wpdb->posts}.ID
              AND karsabt_search_meta.meta_key IN (%s, %s)
              AND karsabt_search_meta.meta_value LIKE %s
        )",
        '_karsabt_customer_name',
        '_karsabt_customer_phone',
        $like
    );

    if (false !== strpos($search, ' AND (')) {
        $search = preg_replace('/\)\s*$/', $meta_search . ')', $search, 1);
    } else {
        $search .= $meta_search;
    }

    return $search;
}
add_filter('posts_search', 'karsabt_core_search_order_customer_meta', 10, 2);
