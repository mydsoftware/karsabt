<?php
/**
 * گزارش مدیریتی عملکرد و وضعیت پرونده‌های کارثبت.
 */
if (!defined('ABSPATH')) { exit; }

function karsabt_admin_reports_register_menu() {
    add_submenu_page(
        'edit.php?post_type=karsabt_order',
        'گزارش مدیریتی',
        'گزارش مدیریتی',
        'manage_options',
        'karsabt-admin-reports',
        'karsabt_admin_reports_render'
    );
}
add_action('admin_menu', 'karsabt_admin_reports_register_menu');

/**
 * داده‌های گزارش بر پایه پرونده‌های خصوصی و تاریخچه ثبت‌شده.
 */
function karsabt_admin_reports_collect($month) {
    $start = DateTime::createFromFormat('!Y-m', $month);
    if (!$start || $start->format('Y-m') !== $month) {
        $start = new DateTime('first day of this month', wp_timezone());
        $month = $start->format('Y-m');
    }
    $end = clone $start;
    $end->modify('first day of next month');
    $start_sql = $start->format('Y-m-d H:i:s');
    $end_sql = $end->format('Y-m-d H:i:s');
    $overdue_before = (new DateTime('now', wp_timezone()))->modify('-7 days')->format('Y-m-d H:i:s');
    $today = wp_date('Y-m-d');

    $ids = get_posts(array(
        'post_type' => 'karsabt_order',
        'post_status' => 'private',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
    ));

    $statuses = karsabt_core_statuses();
    $services = karsabt_core_services();
    $summary = array(
        'total' => count($ids),
        'by_status' => array_fill_keys(array_keys($statuses), 0),
        'overdue' => 0,
        'created_month' => 0,
        'completed_month' => 0,
        'staff' => array(),
        'orders' => array(),
    );

    foreach ($ids as $id) {
        $status = get_post_meta($id, '_karsabt_order_status', true);
        $service = get_post_meta($id, '_karsabt_service', true);
        $assignee = absint(get_post_meta($id, '_karsabt_assigned_user_id', true));
        $created = get_post_field('post_date', $id);
        if (isset($summary['by_status'][$status])) { $summary['by_status'][$status]++; }
        if ($created >= $start_sql && $created < $end_sql) { $summary['created_month']++; }
        $due_date = get_post_meta($id, '_karsabt_due_date', true);
        $is_overdue = !in_array($status, array('completed', 'rejected'), true) && (
            (is_string($due_date) && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $due_date) && $due_date < $today)
            || ((!$due_date || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', (string) $due_date)) && $created < $overdue_before)
        );
        if ($is_overdue) { $summary['overdue']++; }

        if ($assignee) {
            if (!isset($summary['staff'][$assignee])) {
                $user = get_user_by('id', $assignee);
                $summary['staff'][$assignee] = array(
                    'name' => $user ? $user->display_name : 'کاربر حذف‌شده',
                    'assigned' => 0,
                    'completed_month' => 0,
                    'durations' => array(),
                );
            }
            $summary['staff'][$assignee]['assigned']++;
        }

        $history = get_post_meta($id, '_karsabt_status_history', true);
        $history = is_array($history) ? $history : array();
        $counted_staff = array();
        $order_completed_this_month = false;
        foreach ($history as $event) {
            if ('completed' !== ($event['status'] ?? '') || empty($event['time'])) { continue; }
            $actor = absint($event['user_id'] ?? 0);
            $event_time = (string) $event['time'];
            if ($event_time < $start_sql || $event_time >= $end_sql) { continue; }
            if (!$order_completed_this_month) {
                $summary['completed_month']++;
                $order_completed_this_month = true;
            }
            if (!$actor) { continue; }
            if (!isset($summary['staff'][$actor])) {
                $user = get_user_by('id', $actor);
                $summary['staff'][$actor] = array(
                    'name' => $user ? $user->display_name : 'کاربر حذف‌شده',
                    'assigned' => 0,
                    'completed_month' => 0,
                    'durations' => array(),
                );
            }
            if (!isset($counted_staff[$actor])) {
                $summary['staff'][$actor]['completed_month']++;
                $counted_staff[$actor] = true;
            }
            $created_ts = strtotime($created);
            $completed_ts = strtotime($event_time);
            if ($created_ts && $completed_ts && $completed_ts >= $created_ts) {
                $summary['staff'][$actor]['durations'][] = ($completed_ts - $created_ts) / DAY_IN_SECONDS;
            }
        }

        $summary['orders'][] = array(
            'id' => $id,
            'tracking' => get_post_meta($id, '_karsabt_tracking_code', true),
            'customer' => get_post_meta($id, '_karsabt_customer_name', true),
            'service' => isset($services[$service]) ? $services[$service] : '—',
            'status' => isset($statuses[$status]) ? $statuses[$status] : 'نامشخص',
            'created' => $created,
            'assignee' => $assignee,
            'overdue' => $is_overdue,
        );
    }

    foreach ($summary['staff'] as &$staff) {
        $staff['average_days'] = $staff['durations'] ? array_sum($staff['durations']) / count($staff['durations']) : null;
        unset($staff['durations']);
    }
    unset($staff);
    return $summary;
}

function karsabt_admin_reports_render() {
    if (!current_user_can('manage_options')) { wp_die('دسترسی غیرمجاز'); }
    $month = isset($_GET['report_month']) ? sanitize_text_field(wp_unslash($_GET['report_month'])) : wp_date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) { $month = wp_date('Y-m'); }
    $data = karsabt_admin_reports_collect($month);

    echo '<div class="wrap"><h1>گزارش مدیریتی کارثبت</h1>';
    echo '<p>این گزارش فقط برای مدیران قابل مشاهده است. پرونده معوق یعنی درخواست باز با مهلت هدف گذشته؛ در نبود مهلت معتبر، عمر بیش از ۷ روز ملاک است.</p>';
    echo '<form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">';
    echo '<input type="hidden" name="post_type" value="karsabt_order"><input type="hidden" name="page" value="karsabt-admin-reports">';
    echo '<label for="karsabt-report-month">ماه گزارش</label><input id="karsabt-report-month" type="month" name="report_month" value="' . esc_attr($month) . '">';
    submit_button('نمایش گزارش', 'secondary', '', false);
    echo '</form>';

    echo '<div style="display:flex;gap:12px;flex-wrap:wrap;margin:18px 0">';
    $cards = array(
        'کل پرونده‌ها' => $data['total'],
        'ثبت‌شده در ماه' => $data['created_month'],
        'تکمیل‌شده در ماه' => $data['completed_month'],
        'پرونده‌های معوق' => $data['overdue'],
    );
    foreach ($cards as $label => $value) {
        echo '<div class="card" style="min-width:150px;padding:14px"><strong>' . esc_html($label) . '</strong><p style="font-size:26px;margin:8px 0">' . esc_html((string) $value) . '</p></div>';
    }
    echo '</div>';

    echo '<h2>وضعیت پرونده‌ها</h2><table class="widefat striped"><thead><tr><th>وضعیت</th><th>تعداد</th></tr></thead><tbody>';
    foreach (karsabt_core_statuses() as $key => $label) {
        echo '<tr><td>' . esc_html($label) . '</td><td>' . esc_html((string) $data['by_status'][$key]) . '</td></tr>';
    }
    echo '</tbody></table>';

    echo '<h2 style="margin-top:24px">عملکرد کارشناسان</h2><table class="widefat striped"><thead><tr><th>کارشناس / ثبت‌کننده تکمیل</th><th>پرونده تخصیص‌یافته فعلی</th><th>تکمیل‌شده در ماه</th><th>میانگین روز تا تکمیل ثبت‌شده</th></tr></thead><tbody>';
    if (!$data['staff']) {
        echo '<tr><td colspan="4">داده‌ای برای نمایش وجود ندارد.</td></tr>';
    } else {
        foreach ($data['staff'] as $staff) {
            echo '<tr><td>' . esc_html($staff['name']) . '</td><td>' . esc_html((string) $staff['assigned']) . '</td><td>' . esc_html((string) $staff['completed_month']) . '</td><td>' . esc_html(null === $staff['average_days'] ? 'ناموجود' : number_format_i18n($staff['average_days'], 1) . ' روز') . '</td></tr>';
        }
    }
    echo '</tbody></table>';

    echo '<h2 style="margin-top:24px">پرونده‌های معوق (مهلت گذشته / بدون مهلت و بیش از ۷ روز)</h2><table class="widefat striped"><thead><tr><th>کد پیگیری</th><th>مشتری</th><th>خدمت</th><th>وضعیت</th><th>تاریخ ثبت</th><th>مسئول</th><th>پرونده</th></tr></thead><tbody>';
    $overdue_rows = array_filter($data['orders'], function($order) { return $order['overdue']; });
    if (!$overdue_rows) {
        echo '<tr><td colspan="7">پرونده معوقی وجود ندارد.</td></tr>';
    } else {
        foreach ($overdue_rows as $order) {
            $assignee = $order['assignee'] ? get_user_by('id', $order['assignee']) : false;
            echo '<tr><td><bdi>' . esc_html($order['tracking'] ? $order['tracking'] : '—') . '</bdi></td>';
            echo '<td>' . esc_html($order['customer']) . '</td><td>' . esc_html($order['service']) . '</td><td>' . esc_html($order['status']) . '</td>';
            echo '<td>' . esc_html(mysql2date('Y/m/d', $order['created'])) . '</td><td>' . esc_html($assignee ? $assignee->display_name : 'بدون مسئول') . '</td>';
            echo '<td><a href="' . esc_url(get_edit_post_link($order['id'])) . '">مشاهده</a></td></tr>';
        }
    }
    echo '</tbody></table>';

    $export_url = wp_nonce_url(add_query_arg(array('action' => 'karsabt_export_admin_report', 'report_month' => $month), admin_url('admin-post.php')), 'karsabt_export_admin_report');
    echo '<p style="margin-top:18px"><a class="button button-primary" href="' . esc_url($export_url) . '">دریافت گزارش CSV</a></p>';
    echo '<p class="description">تکمیل پرونده بر اساس رویداد تغییر وضعیت به «انجام‌شده» در تاریخچه است. اگر شناسه ثبت‌کننده در رویداد ثبت نشده باشد، در آمار فردی کارشناسان محاسبه نمی‌شود.</p>';
    echo '</div>';
}

function karsabt_admin_reports_export_csv() {
    if (!current_user_can('manage_options')) { wp_die('دسترسی غیرمجاز'); }
    check_admin_referer('karsabt_export_admin_report');
    $month = isset($_GET['report_month']) ? sanitize_text_field(wp_unslash($_GET['report_month'])) : wp_date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) { $month = wp_date('Y-m'); }
    $data = karsabt_admin_reports_collect($month);
    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="karsabt-report-' . $month . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('نوع گزارش','ماه','عنوان','مقدار'));
    fputcsv($out, array('خلاصه',$month,'کل پرونده‌ها',$data['total']));
    fputcsv($out, array('خلاصه',$month,'ثبت‌شده در ماه',$data['created_month']));
    fputcsv($out, array('خلاصه',$month,'تکمیل‌شده در ماه',$data['completed_month']));
    fputcsv($out, array('خلاصه',$month,'پرونده‌های معوق بیش از ۷ روز',$data['overdue']));
    foreach ($data['by_status'] as $key => $count) {
        $labels = karsabt_core_statuses();
        fputcsv($out, array('وضعیت',$month,$labels[$key] ?? $key,$count));
    }
    foreach ($data['staff'] as $staff) {
        fputcsv($out, array('کارشناس',$month,$staff['name'],'تخصیص فعلی: ' . $staff['assigned'] . ' | تکمیل ماه: ' . $staff['completed_month'] . ' | میانگین روز: ' . (null === $staff['average_days'] ? 'ناموجود' : number_format($staff['average_days'], 1))));
    }
    fclose($out);
    exit;
}
add_action('admin_post_karsabt_export_admin_report', 'karsabt_admin_reports_export_csv');
