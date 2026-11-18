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
    $service_filter = isset($_GET['karsabt_service']) ? sanitize_key(wp_unslash($_GET['karsabt_service'])) : '';
    $services = karsabt_core_services();
    if (!isset($services[$service_filter])) { $service_filter = ''; }
    $statuses = karsabt_core_statuses();
    if (!isset($statuses[$status_filter])) { $status_filter = ''; }

    $base_meta = array(
        array('key' => '_karsabt_assigned_user_id', 'value' => $user_id, 'compare' => '=', 'type' => 'NUMERIC'),
    );

    // آمار وضعیت، فقط برای پرونده‌هایی که اکنون به این کارشناس تخصیص دارند.
    $count_by_status = array();
    foreach ($statuses as $key => $label) {
        $query = new WP_Query(array(
            'post_type' => 'karsabt_order',
            'post_status' => 'private',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => false,
            'meta_query' => array_merge($base_meta, array(
                array('key' => '_karsabt_order_status', 'value' => $key, 'compare' => '='),
            )),
        ));
        $count_by_status[$key] = (int) $query->found_posts;
    }
    $total = array_sum($count_by_status);

    // گزارش ماه جاری از رویدادهای واقعی تاریخچه وضعیت؛ فقط تکمیل‌هایی که همین کارشناس ثبت کرده است.
    $month_key = wp_date('Y-m', current_time('timestamp'));
    $assigned_ids = get_posts(array(
        'post_type' => 'karsabt_order',
        'post_status' => 'private',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => $base_meta,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
    ));
    $completed_this_month = 0;
    $durations = array();
    foreach ($assigned_ids as $assigned_id) {
        $history = get_post_meta($assigned_id, '_karsabt_status_history', true);
        if (!is_array($history)) { continue; }
        $first_completion = null;
        $completed_in_month = false;
        foreach ($history as $event) {
            if (
                'completed' === ($event['status'] ?? '') &&
                (int) ($event['user_id'] ?? 0) === $user_id &&
                !empty($event['time'])
            ) {
                $event_time = (string) $event['time'];
                if (0 === strpos($event_time, $month_key . '-')) {
                    $completed_in_month = true;
                }
                if (null === $first_completion || strcmp($event_time, $first_completion) < 0) {
                    $first_completion = $event_time;
                }
            }
        }
        if ($completed_in_month) { $completed_this_month++; }
        if ($first_completion) {
            $created = get_post_field('post_date', $assigned_id);
            $start_ts = strtotime($created);
            $end_ts = strtotime($first_completion);
            if ($start_ts && $end_ts && $end_ts >= $start_ts) {
                $durations[] = ($end_ts - $start_ts) / DAY_IN_SECONDS;
            }
        }
    }
    $average_days = $durations ? array_sum($durations) / count($durations) : null;

    // صف کاری پیشنهادی: فقط پرونده‌های باز تخصیص‌یافته به همین کارشناس.
    // برای جلوگیری از بارگذاری کل آرشیو، حداکثر ۱۰۰ پرونده بررسی می‌شود.
    $queue_query = new WP_Query(array(
        'post_type' => 'karsabt_order',
        'post_status' => 'private',
        'posts_per_page' => 100,
        'fields' => 'all',
        'no_found_rows' => true,
        'meta_query' => array(
            'relation' => 'AND',
            $base_meta[0],
            array('key' => '_karsabt_order_status', 'value' => array('completed','rejected'), 'compare' => 'NOT IN'),
        ),
        'orderby' => 'date',
        'order' => 'ASC',
    ));
    $today_date = wp_date('Y-m-d');
    $now_utc = current_time('timestamp', true);
    $work_queue = array();
    foreach ($queue_query->posts as $queue_order) {
        $due = (string) get_post_meta($queue_order->ID, '_karsabt_due_date', true);
        $created_utc = (int) get_post_time('U', true, $queue_order);
        $age_days = $created_utc > 0 ? (int) floor(($now_utc - $created_utc) / DAY_IN_SECONDS) : 0;
        $rank = 4;
        $priority_label = 'عادی';
        if ($due && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $due) && $due < $today_date) {
            $rank = 0; $priority_label = 'معوق';
        } elseif ($due === $today_date) {
            $rank = 1; $priority_label = 'سررسید امروز';
        } elseif ($due && $due > $today_date && $due <= wp_date('Y-m-d', strtotime('+3 days', current_time('timestamp')))) {
            $rank = 2; $priority_label = 'نزدیک سررسید';
        } elseif ($age_days >= 7) {
            $rank = 3; $priority_label = 'قدیمی و نیازمند بررسی';
        }
        $work_queue[] = array(
            'id' => $queue_order->ID,
            'rank' => $rank,
            'priority' => $priority_label,
            'due' => $due,
            'created' => $queue_order->post_date,
            'age' => max(0, $age_days),
        );
    }
    usort($work_queue, function($a, $b) {
        if ($a['rank'] !== $b['rank']) { return $a['rank'] <=> $b['rank']; }
        if ($a['due'] !== $b['due']) {
            if ('' === $a['due']) { return 1; }
            if ('' === $b['due']) { return -1; }
            return strcmp($a['due'], $b['due']);
        }
        return strcmp($a['created'], $b['created']);
    });
    $work_queue = array_slice($work_queue, 0, 10);


    $page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $meta_query = $base_meta;
    if ($status_filter) {
        $meta_query[] = array('key' => '_karsabt_order_status', 'value' => $status_filter, 'compare' => '=');
    }
    if ($service_filter) {
        $meta_query[] = array('key' => '_karsabt_service', 'value' => $service_filter, 'compare' => '=');
    }
    if ($search !== '') {
        $meta_query[] = array(
            'relation' => 'OR',
            array('key' => '_karsabt_customer_name', 'value' => $search, 'compare' => 'LIKE'),
            array('key' => '_karsabt_customer_phone', 'value' => $search, 'compare' => 'LIKE'),
            array('key' => '_karsabt_tracking_code', 'value' => strtoupper($search), 'compare' => 'LIKE'),
        );
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
    if ($search !== '') { $args['s'] = $search; }
    $orders = new WP_Query($args);

    echo '<div class="wrap"><h1>داشبورد کارشناس</h1>';
    echo '<p>در این صفحه فقط درخواست‌هایی نمایش داده می‌شوند که اکنون به حساب شما تخصیص یافته‌اند.</p>';
    echo '<div style="display:flex;flex-wrap:wrap;gap:10px;margin:18px 0">';
    echo '<div class="card" style="min-width:110px;padding:12px"><strong>همه درخواست‌ها</strong><p style="font-size:24px;margin:8px 0">' . esc_html((string) $total) . '</p></div>';
    foreach ($statuses as $key => $label) {
        echo '<div class="card" style="min-width:110px;padding:12px"><strong>' . esc_html($label) . '</strong><p style="font-size:24px;margin:8px 0">' . esc_html((string) $count_by_status[$key]) . '</p></div>';
    }
    echo '</div>';

    echo '<h2>عملکرد ماه جاری (' . esc_html($month_key) . ')</h2>';
    echo '<div style="display:flex;flex-wrap:wrap;gap:10px;margin:12px 0 20px">';
    echo '<div class="card" style="min-width:180px;padding:12px"><strong>پرونده‌های تکمیل‌شده توسط شما</strong><p style="font-size:24px;margin:8px 0">' . esc_html((string) $completed_this_month) . '</p></div>';
    echo '<div class="card" style="min-width:180px;padding:12px"><strong>میانگین زمان تا اولین تکمیل</strong><p style="font-size:24px;margin:8px 0">' . esc_html(null === $average_days ? 'داده کافی نیست' : number_format_i18n($average_days, 1) . ' روز') . '</p></div>';
    echo '</div>';
    echo '<p class="description">محاسبات از تاریخچه ثبت‌شده وضعیت‌ها و پرونده‌های تخصیص‌یافته فعلی انجام می‌شود؛ رویدادهای قدیمی یا فاقد زمان معتبر در میانگین لحاظ نمی‌شوند.</p>';

    echo '<h2>صف کاری پیشنهادی امروز</h2>';
    echo '<p class="description">اولویت‌بندی خودکار بر اساس مهلت ثبت‌شده، سپس سن پرونده انجام می‌شود. این صف حداکثر ۱۰۰ پرونده باز اخیر شما را بررسی و ۱۰ مورد اولویت‌دار را نمایش می‌دهد.</p>';
    echo '<table class="widefat striped"><thead><tr><th>اولویت</th><th>کد پیگیری</th><th>مشتری</th><th>خدمت</th><th>مهلت هدف</th><th>سن پرونده</th><th>اقدام</th></tr></thead><tbody>';
    if (!$work_queue) {
        echo '<tr><td colspan="7">پرونده باز تخصیص‌یافته‌ای برای نمایش وجود ندارد.</td></tr>';
    } else {
        foreach ($work_queue as $queue_item) {
            $qid = $queue_item['id'];
            if (!current_user_can('edit_karsabt_order', $qid)) { continue; }
            $qcode = get_post_meta($qid, '_karsabt_tracking_code', true);
            $qname = get_post_meta($qid, '_karsabt_customer_name', true);
            $qservice = get_post_meta($qid, '_karsabt_service', true);
            echo '<tr><td><strong>'.esc_html($queue_item['priority']).'</strong></td>';
            echo '<td><bdi>'.esc_html($qcode ? $qcode : '—').'</bdi></td>';
            echo '<td>'.esc_html($qname).'</td>';
            echo '<td>'.esc_html(isset($services[$qservice]) ? $services[$qservice] : '—').'</td>';
            echo '<td>'.esc_html($queue_item['due'] ? $queue_item['due'] : 'ثبت نشده').'</td>';
            echo '<td>'.esc_html(number_format_i18n($queue_item['age'])).' روز</td>';
            echo '<td><a class="button button-small" href="'.esc_url(get_edit_post_link($qid, '')).'">بازکردن پرونده</a></td></tr>';
        }
    }
    echo '</tbody></table>';

    echo '<form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:16px 0">';
    echo '<input type="hidden" name="page" value="karsabt-staff-dashboard">';
    echo '<label for="karsabt-staff-status">وضعیت</label><select id="karsabt-staff-status" name="karsabt_status"><option value="">همه وضعیت‌ها</option>';
    foreach ($statuses as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($status_filter, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<label for="karsabt-staff-service">خدمت</label><select id="karsabt-staff-service" name="karsabt_service"><option value="">همه خدمات</option>';
    foreach ($services as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($service_filter, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<label class="screen-reader-text" for="karsabt-staff-search">جستجوی درخواست</label><input id="karsabt-staff-search" type="search" name="s" value="' . esc_attr($search) . '" placeholder="نام، موبایل یا کد پیگیری">';
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
                'base' => add_query_arg(array('page' => 'karsabt-staff-dashboard', 'paged' => '%#%', 'karsabt_status' => $status_filter, 'karsabt_service' => $service_filter, 's' => $search), admin_url('admin.php')),
                'format' => '', 'current' => $page, 'total' => $orders->max_num_pages,
            ))) . '</div></div>';
        }
    }
    echo '</div>';
}
