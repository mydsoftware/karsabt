<?php
/**
 * پنل مدیریت مشتریان کارثبت.
 */
if (!defined('ABSPATH')) {
    exit;
}

function karsabt_core_register_customer_admin_page() {
    add_menu_page(
        'مشتریان کارثبت',
        'مشتریان',
        'manage_options',
        'karsabt-customers',
        'karsabt_core_render_customer_admin_page',
        'dashicons-groups',
        26
    );
}
add_action('admin_menu', 'karsabt_core_register_customer_admin_page');

function karsabt_core_render_customer_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی مجاز نیست.');
    }

    $notice = '';
    if ('POST' === strtoupper(isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '') && isset($_POST['karsabt_customer_save'])) {
        check_admin_referer('karsabt_customer_save');
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $user = $user_id ? get_user_by('id', $user_id) : false;
        $name = isset($_POST['display_name']) ? sanitize_text_field(wp_unslash($_POST['display_name'])) : '';
        $mobile = isset($_POST['mobile']) ? karsabt_core_normalize_mobile(wp_unslash($_POST['mobile'])) : '';

        if (!$user || '' === $name || strlen($name) > 300 || !$mobile) {
            $notice = '<div class="notice notice-error"><p>اطلاعات واردشده معتبر نیست.</p></div>';
        } else {
            $old_mobile = karsabt_core_normalize_mobile(get_user_meta($user_id, '_karsabt_mobile', true));
            $mobile_updated = ($old_mobile === $mobile) ? true : karsabt_core_update_customer_mobile($mobile, $user_id);
            if (!$mobile_updated) {
                $notice = '<div class="notice notice-error"><p>این شماره به مشتری دیگری اختصاص دارد یا به‌روزرسانی امن آن انجام نشد. اطلاعات قبلی حفظ شد.</p></div>';
            } else {
                $updated = wp_update_user(array('ID' => $user_id, 'display_name' => $name, 'nickname' => $name));
                if (is_wp_error($updated)) {
                    $notice = '<div class="notice notice-error"><p>ذخیره نام مشتری انجام نشد.</p></div>';
                } else {
                    $notice = '<div class="notice notice-success"><p>اطلاعات مشتری ذخیره شد. در صورت تغییر موبایل، تأیید شماره لغو شد و مشتری باید شماره جدید را با کد پیامکی تأیید کند.</p></div>';
                }
            }
        }
    }

    $selected_id = isset($_GET['customer_id']) ? absint($_GET['customer_id']) : 0;
    echo '<div class="wrap"><h1>مدیریت مشتریان کارثبت</h1>' . $notice;

    if ($selected_id) {
        $customer = get_user_by('id', $selected_id);
        if (!$customer || !in_array('subscriber', (array) $customer->roles, true)) {
            echo '<div class="notice notice-error"><p>مشتری موردنظر پیدا نشد.</p></div></div>';
            return;
        }
        $mobile = get_user_meta($selected_id, '_karsabt_mobile', true);
        $verified = '1' === get_user_meta($selected_id, '_karsabt_mobile_verified', true);
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=karsabt-customers')) . '">بازگشت به فهرست مشتریان</a></p>';
        echo '<h2>پروفایل مشتری</h2><table class="form-table"><tr><th>شناسه</th><td>' . esc_html($selected_id) . '</td></tr><tr><th>ایمیل</th><td>' . esc_html($customer->user_email ? $customer->user_email : 'ثبت نشده') . '</td></tr><tr><th>وضعیت موبایل</th><td>' . ($verified ? 'تأییدشده' : 'تأییدنشده') . '</td></tr><tr><th>تاریخ عضویت</th><td>' . esc_html(mysql2date('Y/m/d H:i', $customer->user_registered)) . '</td></tr></table>';
        echo '<h2>ویرایش اطلاعات پایه</h2><form method="post">';
        wp_nonce_field('karsabt_customer_save');
        echo '<input type="hidden" name="user_id" value="' . esc_attr($selected_id) . '"><table class="form-table"><tr><th><label for="karsabt-customer-display-name">نام مشتری</label></th><td><input class="regular-text" id="karsabt-customer-display-name" name="display_name" value="' . esc_attr($customer->display_name) . '" required></td></tr><tr><th><label for="karsabt-customer-mobile">موبایل</label></th><td><input class="regular-text" id="karsabt-customer-mobile" name="mobile" value="' . esc_attr($mobile) . '" inputmode="tel" required></td></tr></table><p><button class="button button-primary" type="submit" name="karsabt_customer_save" value="1">ذخیره اطلاعات</button></p></form>';

        $orders = get_posts(array(
            'post_type' => 'karsabt_order',
            'post_status' => 'private',
            'posts_per_page' => 100,
            'meta_key' => '_karsabt_customer_user_id',
            'meta_value' => (string) $selected_id,
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        echo '<h2>سوابق درخواست‌ها</h2>';
        if (!$orders) {
            echo '<p>درخواستی به این حساب متصل نشده است.</p>';
        } else {
            echo '<table class="widefat striped"><thead><tr><th>کد پیگیری</th><th>خدمت</th><th>وضعیت</th><th>تاریخ</th><th>جزئیات</th></tr></thead><tbody>';
            foreach ($orders as $order) {
                $code = get_post_meta($order->ID, '_karsabt_tracking_code', true);
                $service = get_post_meta($order->ID, '_karsabt_service', true);
                $status = get_post_meta($order->ID, '_karsabt_order_status', true);
                $services = karsabt_core_services();
                $statuses = karsabt_core_statuses();
                echo '<tr><td><bdi>' . esc_html($code ? $code : '—') . '</bdi></td><td>' . esc_html(isset($services[$service]) ? $services[$service] : '—') . '</td><td>' . esc_html(isset($statuses[$status]) ? $statuses[$status] : 'جدید') . '</td><td>' . esc_html(mysql2date('Y/m/d', $order->post_date)) . '</td><td><a href="' . esc_url(get_edit_post_link($order->ID)) . '">مشاهده درخواست</a></td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
        return;
    }

    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $args = array(
        'role' => 'subscriber',
        'number' => 20,
        'paged' => $page,
        'orderby' => 'registered',
        'order' => 'DESC',
        'count_total' => true,
    );
    if ('' !== $search) {
        // جست‌وجوی شماره از جست‌وجوی نام/ایمیل جداست؛ WP_User_Query
        // شرط search و meta_query را با AND ترکیب می‌کند.
        if (preg_match('/^[0-9۰-۹٠-٩+()\\s-]+$/u', $search)) {
            $mobile_search = karsabt_core_normalize_mobile($search);
            $args['meta_query'] = array(array(
                'key' => '_karsabt_mobile',
                'value' => $mobile_search ? $mobile_search : preg_replace('/\\s+/', '', $search),
                'compare' => 'LIKE',
            ));
        } else {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'user_email', 'display_name');
        }
    }
    $query = new WP_User_Query($args);
    $customers = $query->get_results();
    echo '<form method="get"><input type="hidden" name="page" value="karsabt-customers"><p class="search-box"><label class="screen-reader-text" for="karsabt-customer-search">جستجوی مشتری</label><input id="karsabt-customer-search" type="search" name="s" value="' . esc_attr($search) . '" placeholder="نام، ایمیل یا موبایل"><button class="button">جستجو</button></p></form>';
    echo '<p>تعداد مشتریان: ' . esc_html((string) $query->get_total()) . '</p>';
    if (!$customers) {
        echo '<p>مشتری‌ای پیدا نشد.</p>';
    } else {
        echo '<table class="widefat striped"><thead><tr><th>نام</th><th>موبایل</th><th>تأیید موبایل</th><th>تاریخ عضویت</th><th>پروفایل</th></tr></thead><tbody>';
        foreach ($customers as $customer) {
            $mobile = get_user_meta($customer->ID, '_karsabt_mobile', true);
            $verified = '1' === get_user_meta($customer->ID, '_karsabt_mobile_verified', true);
            $url = add_query_arg(array('page' => 'karsabt-customers', 'customer_id' => $customer->ID), admin_url('admin.php'));
            echo '<tr><td>' . esc_html($customer->display_name) . '</td><td><bdi>' . esc_html($mobile ? $mobile : '—') . '</bdi></td><td>' . ($verified ? 'تأییدشده' : 'تأییدنشده') . '</td><td>' . esc_html(mysql2date('Y/m/d', $customer->user_registered)) . '</td><td><a href="' . esc_url($url) . '">مشاهده پروفایل</a></td></tr>';
        }
        echo '</tbody></table>';
        $pages = (int) ceil($query->get_total() / 20);
        if ($pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo wp_kses_post(paginate_links(array(
                'base' => add_query_arg(array('page' => 'karsabt-customers', 'paged' => '%#%', 's' => $search), admin_url('admin.php')),
                'format' => '',
                'current' => $page,
                'total' => $pages,
            )));
            echo '</div></div>';
        }
    }
    echo '</div>';
}
