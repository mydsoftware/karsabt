<?php
/**
 * داشبورد جامع عملیات کارثبت.
 */
if (!defined('ABSPATH')) { exit; }

function karsabt_operations_register_menu() {
    add_submenu_page(
        'edit.php?post_type=karsabt_order',
        'داشبورد عملیات',
        'داشبورد عملیات',
        'manage_options',
        'karsabt-operations',
        'karsabt_operations_render_dashboard'
    );
}
add_action('admin_menu', 'karsabt_operations_register_menu');

/** شمارش پرونده‌ها با شرط‌های متای استاندارد وردپرس. */
function karsabt_operations_count($meta_query = array(), $date_query = array()) {
    $args = array(
        'post_type' => 'karsabt_order',
        'post_status' => 'private',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => false,
        'meta_query' => $meta_query,
    );
    if ($date_query) { $args['date_query'] = $date_query; }
    $query = new WP_Query($args);
    return (int) $query->found_posts;
}

function karsabt_operations_render_dashboard() {
    if (!current_user_can('manage_options')) { wp_die('دسترسی غیرمجاز'); }

    $today = wp_date('Y-m-d');
    $soon = wp_date('Y-m-d', strtotime('+3 days', current_time('timestamp')));
    $open_status = array('relation' => 'OR',
        array('key'=>'_karsabt_order_status','compare'=>'NOT EXISTS'),
        array('key'=>'_karsabt_order_status','value'=>array('completed','rejected'),'compare'=>'NOT IN')
    );
    $overdue_meta = array(
        'relation'=>'AND',
        array('key'=>'_karsabt_due_date','value'=>$today,'compare'=>'<','type'=>'DATE'),
        $open_status,
    );
    $soon_meta = array(
        'relation'=>'AND',
        array('key'=>'_karsabt_due_date','value'=>array($today,$soon),'compare'=>'BETWEEN','type'=>'DATE'),
        $open_status,
    );
    $unassigned_meta = array(
        'relation'=>'OR',
        array('key'=>'_karsabt_assigned_user_id','compare'=>'NOT EXISTS'),
        array('key'=>'_karsabt_assigned_user_id','value'=>0,'compare'=>'=','type'=>'NUMERIC'),
    );

    $today_count = karsabt_operations_count(array(), array(array(
        'after'=>$today.' 00:00:00','before'=>$today.' 23:59:59','inclusive'=>true,
    )));
    $overdue_count = karsabt_operations_count($overdue_meta);
    $soon_count = karsabt_operations_count($soon_meta);
    $unassigned_count = karsabt_operations_count($unassigned_meta);
    $open_count = karsabt_operations_count(array(
        'relation'=>'OR',
        array('key'=>'_karsabt_order_status','compare'=>'NOT EXISTS'),
        array('key'=>'_karsabt_order_status','value'=>array('completed','rejected'),'compare'=>'NOT IN'),
    ));

    $status_counts = array();
    foreach (karsabt_core_statuses() as $key=>$label) {
        $status_counts[$label] = karsabt_operations_count(array(
            array('key'=>'_karsabt_order_status','value'=>$key,'compare'=>'='),
        ));
    }

    $staff = get_users(array(
        'role__in'=>array('administrator','karsabt_case_worker'),
        'orderby'=>'display_name','order'=>'ASC',
        'fields'=>array('ID','display_name'),
    ));
    $staff_rows = array();
    foreach ($staff as $person) {
        $assigned = karsabt_operations_count(array(
            array('key'=>'_karsabt_assigned_user_id','value'=>$person->ID,'compare'=>'=','type'=>'NUMERIC'),
            array('key'=>'_karsabt_order_status','value'=>array('completed','rejected'),'compare'=>'NOT IN'),
        ));
        $staff_rows[] = array('name'=>$person->display_name,'assigned'=>$assigned);
    }

    $base_url = admin_url('edit.php?post_type=karsabt_order');
    echo '<div class="wrap"><h1>داشبورد عملیات کارثبت</h1>';
    echo '<p>نمای کلی وضعیت اجرایی پرونده‌ها؛ مهلت‌ها بر اساس تاریخ هدف ثبت‌شده در هر پرونده محاسبه می‌شوند.</p>';
    echo '<style>
    .karsabt-op-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:18px 0}
    .karsabt-op-card{background:#fff;border:1px solid #dcdcde;border-right:4px solid #2271b1;border-radius:8px;padding:16px}
    .karsabt-op-card strong{display:block;color:#50575e;font-size:13px}
    .karsabt-op-card span{display:block;font-size:30px;font-weight:700;margin-top:8px}
    .karsabt-op-card.is-warning{border-right-color:#dba617}
    .karsabt-op-card.is-danger{border-right-color:#d63638}
    .karsabt-op-card a{display:inline-block;margin-top:8px}
    </style>';
    echo '<div class="karsabt-op-grid">';
    $cards = array(
        array('پرونده‌های ثبت‌شده امروز',$today_count,''),
        array('پرونده‌های باز',$open_count,''),
        array('پرونده‌های معوق',$overdue_count,'is-danger'),
        array('سررسید امروز تا ۳ روز آینده',$soon_count,'is-warning'),
        array('پرونده‌های بدون مسئول',$unassigned_count,'is-warning'),
    );
    foreach ($cards as $card) {
        echo '<div class="karsabt-op-card '.esc_attr($card[2]).'"><strong>'.esc_html($card[0]).'</strong><span>'.esc_html(number_format_i18n($card[1])).'</span></div>';
    }
    echo '</div>';

    echo '<h2>توزیع پرونده‌ها بر اساس وضعیت</h2><table class="widefat striped"><thead><tr><th>وضعیت</th><th>تعداد</th></tr></thead><tbody>';
    foreach ($status_counts as $label=>$count) {
        echo '<tr><td>'.esc_html($label).'</td><td>'.esc_html(number_format_i18n($count)).'</td></tr>';
    }
    echo '</tbody></table>';

    echo '<h2 style="margin-top:24px">بار کاری مسئولان</h2><table class="widefat striped"><thead><tr><th>مسئول</th><th>پرونده‌های باز تخصیص‌یافته</th></tr></thead><tbody>';
    if (!$staff_rows) { echo '<tr><td colspan="2">کاربری برای نمایش وجود ندارد.</td></tr>'; }
    foreach ($staff_rows as $row) {
        echo '<tr><td>'.esc_html($row['name']).'</td><td>'.esc_html(number_format_i18n($row['assigned'])).'</td></tr>';
    }
    echo '</tbody></table>';
    echo '<p style="margin-top:18px"><a class="button button-primary" href="'.esc_url($base_url).'">رفتن به فهرست پرونده‌ها</a> ';
    echo '<a class="button" href="'.esc_url(admin_url('edit.php?post_type=karsabt_order&page=karsabt-admin-reports')).'">گزارش مدیریتی تفصیلی</a></p>';
    echo '<p class="description">پرونده معوق یعنی تاریخ هدف آن قبل از امروز و وضعیت آن غیر از انجام‌شده یا مختومه باشد. پرونده‌های بدون تاریخ هدف در این شمارش قرار نمی‌گیرند.</p>';
    echo '</div>';
}
