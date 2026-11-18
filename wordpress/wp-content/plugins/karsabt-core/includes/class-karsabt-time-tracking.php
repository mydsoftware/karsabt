<?php
/**
 * ثبت زمان کار و توقف پرونده‌ها.
 */
if (!defined('ABSPATH')) { exit; }

function karsabt_time_tracking_get($post_id) {
    $data = get_post_meta($post_id, '_karsabt_time_tracking', true);
    if (!is_array($data)) {
        $data = array('status'=>'not_started','started_at'=>0,'paused_at'=>0,'work_seconds'=>0,'pause_seconds'=>0,'events'=>array());
    }
    $data = wp_parse_args($data, array('status'=>'not_started','started_at'=>0,'paused_at'=>0,'work_seconds'=>0,'pause_seconds'=>0,'events'=>array()));
    if (!is_array($data['events'])) { $data['events'] = array(); }
    return $data;
}

function karsabt_time_tracking_elapsed($data) {
    $work = max(0, (int) $data['work_seconds']);
    $pause = max(0, (int) $data['pause_seconds']);
    $now = current_time('timestamp', true);
    if ('running' === $data['status'] && !empty($data['started_at'])) {
        $work += max(0, $now - (int) $data['started_at']);
    } elseif ('paused' === $data['status'] && !empty($data['paused_at'])) {
        $pause += max(0, $now - (int) $data['paused_at']);
    }
    return array('work'=>$work,'pause'=>$pause);
}

function karsabt_time_tracking_format($seconds) {
    $seconds = max(0, (int) $seconds);
    $hours = (int) floor($seconds / HOUR_IN_SECONDS);
    $minutes = (int) floor(($seconds % HOUR_IN_SECONDS) / MINUTE_IN_SECONDS);
    return sprintf('%02d:%02d', $hours, $minutes);
}

function karsabt_time_tracking_add_metabox() {
    add_meta_box('karsabt-time-tracking','زمان‌سنج و توقف پرونده','karsabt_time_tracking_render_metabox','karsabt_order','side','high');
}
add_action('add_meta_boxes_karsabt_order','karsabt_time_tracking_add_metabox');

function karsabt_time_tracking_render_metabox($post) {
    if (!current_user_can('edit_karsabt_order', $post->ID)) {
        echo '<p>دسترسی مشاهده زمان پرونده را ندارید.</p>';
        return;
    }
    $data = karsabt_time_tracking_get($post->ID);
    $elapsed = karsabt_time_tracking_elapsed($data);
    echo '<p><strong>زمان کار مفید:</strong> '.esc_html(karsabt_time_tracking_format($elapsed['work'])).'</p>';
    echo '<p><strong>زمان توقف:</strong> '.esc_html(karsabt_time_tracking_format($elapsed['pause'])).'</p>';
    echo '<p><strong>وضعیت زمان‌سنج:</strong> '.esc_html(array('not_started'=>'شروع نشده','running'=>'در حال کار','paused'=>'متوقف موقت','finished'=>'پایان یافته')[$data['status']] ?? 'نامشخص').'</p>';
    if ('finished' !== $data['status']) {
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="karsabt_time_tracking_action"><input type="hidden" name="order_id" value="'.esc_attr($post->ID).'">';
        wp_nonce_field('karsabt_time_'.$post->ID);
        if ('not_started' === $data['status']) {
            echo '<button class="button button-primary" name="time_action" value="start">شروع کار</button>';
        } elseif ('running' === $data['status']) {
            echo '<p><label for="karsabt-hold-reason">علت توقف موقت</label><input class="widefat" id="karsabt-hold-reason" name="reason" maxlength="200" placeholder="مثلاً انتظار مدارک مشتری"></p>';
            echo '<button class="button" name="time_action" value="pause">توقف موقت</button> ';
            echo '<button class="button button-primary" name="time_action" value="finish" onclick="return confirm(\'زمان‌سنج این پرونده پایان یابد؟\')">پایان کار</button>';
        } elseif ('paused' === $data['status']) {
            echo '<p><label for="karsabt-hold-reason">علت توقف / انتظار</label><input class="widefat" id="karsabt-hold-reason" name="reason" maxlength="200" placeholder="علت توقف را بنویسید"></p>';
            echo '<button class="button button-primary" name="time_action" value="resume">ادامه کار</button> ';
            echo '<button class="button" name="time_action" value="finish">پایان پرونده کاری</button>';
        }
        echo '</form>';
    }
    if ($data['events']) {
        echo '<hr><strong>آخرین رویدادها</strong><ol style="padding-right:18px">';
        foreach (array_slice(array_reverse($data['events']),0,8) as $event) {
            $label = array('start'=>'شروع','pause'=>'توقف','resume'=>'ادامه','finish'=>'پایان')[$event['action']] ?? 'رویداد';
            echo '<li>'.esc_html($label).' — '.esc_html($event['local_time'] ?? '').(!empty($event['reason']) ? '<br>'.esc_html($event['reason']) : '').'</li>';
        }
        echo '</ol>';
    }
    echo '<p class="description">زمان کار مفید از مجموع بازه‌های فعال محاسبه می‌شود؛ زمان توقف جداگانه ثبت می‌شود.</p>';
}

function karsabt_time_tracking_handle_action() {
    $post_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
    if (!$post_id || 'karsabt_order' !== get_post_type($post_id)) { wp_die('پرونده معتبر نیست.'); }
    check_admin_referer('karsabt_time_'.$post_id);
    if (!current_user_can('edit_karsabt_order', $post_id)) { wp_die('دسترسی غیرمجاز.'); }
    $action = isset($_POST['time_action']) ? sanitize_key(wp_unslash($_POST['time_action'])) : '';
    $reason = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : '';
    $reason = substr($reason,0,200);
    $data = karsabt_time_tracking_get($post_id);
    $now = current_time('timestamp', true);
    $allowed = false;

    if ('start' === $action && 'not_started' === $data['status']) {
        $data['status']='running'; $data['started_at']=$now; $allowed=true;
    } elseif ('pause' === $action && 'running' === $data['status'] && '' !== $reason) {
        $data['work_seconds'] += max(0,$now-(int)$data['started_at']);
        $data['started_at']=0; $data['paused_at']=$now; $data['status']='paused'; $allowed=true;
    } elseif ('resume' === $action && 'paused' === $data['status']) {
        $data['pause_seconds'] += max(0,$now-(int)$data['paused_at']);
        $data['paused_at']=0; $data['started_at']=$now; $data['status']='running'; $allowed=true;
    } elseif ('finish' === $action && in_array($data['status'],array('running','paused'),true)) {
        if ('running' === $data['status']) {
            $data['work_seconds'] += max(0,$now-(int)$data['started_at']);
        } else {
            $data['pause_seconds'] += max(0,$now-(int)$data['paused_at']);
        }
        $data['started_at']=0; $data['paused_at']=0; $data['status']='finished'; $allowed=true;
    }

    if ($allowed) {
        $data['events'][]=array(
            'action'=>$action,
            'time'=>$now,
            'local_time'=>wp_date('Y-m-d H:i:s',$now),
            'user_id'=>get_current_user_id(),
            'reason'=>('pause'===$action && $reason) ? $reason : '',
        );
        if (count($data['events'])>200) { $data['events']=array_slice($data['events'],-200); }
        update_post_meta($post_id,'_karsabt_time_tracking',$data);
        update_post_meta($post_id,'_karsabt_time_tracking_updated_at',$now);
    }
    $url = add_query_arg('karsabt_time_result',$allowed?'saved':'invalid',admin_url('post.php?post='.$post_id.'&action=edit'));
    wp_safe_redirect($url);
    exit;
}
add_action('admin_post_karsabt_time_tracking_action','karsabt_time_tracking_handle_action');

function karsabt_time_tracking_admin_notice() {
    if (!isset($_GET['karsabt_time_result'])) { return; }
    $result = sanitize_key(wp_unslash($_GET['karsabt_time_result']));
    if ('saved' === $result) {
        echo '<div class="notice notice-success is-dismissible"><p>رویداد زمان‌سنج ثبت شد.</p></div>';
    } elseif ('invalid' === $result) {
        echo '<div class="notice notice-warning is-dismissible"><p>این عملیات با وضعیت فعلی زمان‌سنج سازگار نیست؛ صفحه را تازه‌سازی کنید.</p></div>';
    }
}
add_action('admin_notices','karsabt_time_tracking_admin_notice');
