<?php
/**
 * داشبورد اختصاصی کارشناسان کارثبت.
 */
if (!defined('ABSPATH')) { exit; }

function karsabt_staff_register_dashboard() {
    add_menu_page(
        'داشبورد کارشناس',
        'داشبورد کارشناس',
        'edit_karsabt_orders',
        'karsabt-staff-dashboard',
        'karsabt_staff_render_dashboard',
        'dashicons-clipboard',
        4
    );
}
add_action('admin_menu', 'karsabt_staff_register_dashboard');

function karsabt_staff_render_dashboard() {
    if (!current_user_can('edit_karsabt_orders') || current_user_can('manage_options')) {
        wp_die('این صفحه فقط برای کارشناسان در دسترس است.');
    }

    $user_id = get_current_user_id();
    $status_filter = isset($_GET['karsabt_status']) ? sanitize_key(wp_unslash($_GET['karsabt_status'])) : '';
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $statuses = karsabt_core_statuses();
    if (!isset($statuses[$status_filter])) { $status_filter = ''; }

    $base_meta = array(
        array('key' => '_karsabt_assigned_user_id', 'value' => $user_id, 'compare' => '=', 'type' => 'NUMERIC'),
    );
    $count_by_status = array();
    foreach ($statuses as $key => $label) {
        $count_by_status[$key] = (int) (new WP_Query(array(
            'post_type' => 'karsabt_order',
            'post_status' => 'private',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'meta_query' => array_merge($base_meta, array(array('key' => '_karsabt_order_status', 'value' => $key, 'compare' => '='))),
        )))->found_posts;
    }
    $total = array_sum($count_by_status);
    $page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $meta_query = $base_meta;
    if ($status_filter) {
        $meta_query[] = array('key' => '_karsabt_order_status', 'value' => $status_filter, 'compare' => '=');
    }
    $args = array(
        'post_type' => 'karsabt_order',
        'post_status' => 'private',
        'posts_per_page' => 20,
        'paged' => $page,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => $meta_query,
    );
    if ($search !== '') {
        $args['s'] = $search;
    }
    $orders = new WP_Query($args);

    echo '<div class="wrap"><h1>داشبورد کارشناس</h1>';
    echo '<p>در این صفحه فقط درخواست‌هایی نمایش داده می‌شوند که به حساب شما تخصیص یافته‌اند.</p>';
    echo '<div style="display:flex;flex-wrap:wrap;gap:10px;margin:18px 0">';
    echo '<div class="card" style="min-width:110px;padding:12px"><strong>همه درخواست‌ها</strong><p style="font-size:24px;margin:8px 0">' . esc_html((string) $total) . '</p></div>';
    foreach ($statuses as $key => $label) {
        echo '<div class="card" style="min-width:110px;padding:12px"><strong>' . esc_html($label) . '</strong><p style="font-size:24px;margin:8px 0">' . esc_html((string) $count_by_status[$key]) . '</p></div>';
    }
    echo '</div>';

    echo '<form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:16px 0">';
    echo '<input type="hidden" name="page" value="karsabt-staff-dashboard">';
    echo '<label for="karsabt-staff-status">وضعیت</label><select id="karsabt-staff-status" name="karsabt_status"><option value="">همه وضعیت‌ها</option>';
    foreach ($statuses as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($status_filter, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<label class="screen-reader-text" for="karsabt-staff-search">جستجوی درخواست</label><input id="karsabt-staff-search" type="search" name="s" value="' . esc_attr($search) . '" placeholder="جستجوی عنوان درخواست">';
    submit_button('اعمال فیلتر', 'secondary', '', false);
    echo '</form>';

    if (!$orders->have_posts()) {
        echo '<div class="notice notice-info"><p>درخواستی با این فیلتر پیدا نشد.</p></div>';
    } else {
        echo '<table class="widefat striped"><thead><tr><th>کد پیگیری</th><th>مشتری</th><th>شماره تماس</th><th>خدمت</th><th>وضعیت</th><th>تاریخ ثبت</th><th>پرونده</th></tr></thead><tbody>';
        foreach ($orders->posts as $order) {
            $id = $order->ID;
            if (!current_user_can('edit_karsabt_order', $id)) { continue; }
            $code = get_post_meta($id, '_karsabt_tracking_code', true);
            $name = get_post_meta($id, '_karsabt_customer_name', true);
            $phone = get_post_meta($id, '_karsabt_customer_phone', true);
            $service = get_post_meta($id, '_karsabt_service', true);
            $status = get_post_meta($id, '_karsabt_order_status', true);
            $services = karsabt_core_services();
            echo '<tr><td><bdi>' . esc_html($code ? $code : '—') . '</bdi></td>';
            echo '<td>' . esc_html($name) . '</td><td><bdi>' . esc_html($phone) . '</bdi></td>';
            echo '<td>' . esc_html(isset($services[$service]) ? $services[$service] : '—') . '</td>';
            echo '<td>' . esc_html(isset($statuses[$status]) ? $statuses[$status] : 'جدید') . '</td>';
            echo '<td>' . esc_html(mysql2date('Y/m/d H:i', $order->post_date)) . '</td>';
            echo '<td><a class="button button-small" href="' . esc_url(get_edit_post_link($id, '')) . '">بازکردن پرونده</a></td></tr>';
        }
        echo '</tbody></table>';
        if ($orders->max_num_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(paginate_links(array(
                'base' => add_query_arg(array('page' => 'karsabt-staff-dashboard', 'paged' => '%#%', 'karsabt_status' => $status_filter, 's' => $search), admin_url('admin.php')),
                'format' => '', 'current' => $page, 'total' => $orders->max_num_pages,
            ))) . '</div></div>';
        }
    }
    echo '</div>';
}
