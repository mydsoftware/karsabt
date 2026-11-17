<?php
/**
 * Plugin Name: کارثبت | هسته سامانه
 * Description: مدیریت درخواست‌های خدمات کارثبت و فرم ثبت سفارش امن.
 * Version: 0.4.0
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Author: Karsabt
 * Text Domain: karsabt-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KARSABT_CORE_VERSION', '0.4.0');

function karsabt_core_enqueue_styles() {
    wp_enqueue_style('karsabt-core-forms', plugin_dir_url(__FILE__) . 'assets/css/forms.css', array(), KARSABT_CORE_VERSION);
}
add_action('wp_enqueue_scripts', 'karsabt_core_enqueue_styles');

function karsabt_core_register_order_type() {
    $labels = array(
        'name'               => 'درخواست‌ها',
        'singular_name'      => 'درخواست',
        'menu_name'          => 'درخواست‌های کارثبت',
        'add_new'            => 'درخواست جدید',
        'add_new_item'       => 'افزودن درخواست',
        'edit_item'          => 'مشاهده درخواست',
        'new_item'           => 'درخواست جدید',
        'view_item'          => 'مشاهده درخواست',
        'search_items'       => 'جستجوی درخواست‌ها',
        'not_found'          => 'درخواستی پیدا نشد.',
        'not_found_in_trash' => 'درخواستی در زباله‌دان نیست.',
    );

    register_post_type('karsabt_order', array(
        'labels'              => $labels,
        'public'              => false,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_rest'        => false,
        'menu_icon'           => 'dashicons-clipboard',
        'supports'            => array('title'),
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'capabilities'        => array(
            'edit_post'              => 'manage_options',
            'read_post'              => 'manage_options',
            'delete_post'            => 'manage_options',
            'edit_posts'             => 'manage_options',
            'edit_others_posts'      => 'manage_options',
            'publish_posts'          => 'manage_options',
            'read_private_posts'     => 'manage_options',
            'delete_posts'           => 'manage_options',
            'delete_private_posts'   => 'manage_options',
            'delete_published_posts' => 'manage_options',
            'delete_others_posts'    => 'manage_options',
            'edit_private_posts'     => 'manage_options',
            'edit_published_posts'   => 'manage_options',
            'create_posts'           => 'manage_options',
        ),
    ));
}
add_action('init', 'karsabt_core_register_order_type');

function karsabt_core_services() {
    return array(
        'company'       => 'ثبت شرکت و تغییرات',
        'trademark'     => 'ثبت برند و علامت تجاری',
        'business'      => 'جواز کسب و پروانه صنفی',
        'home_business' => 'مجوز مشاغل خانگی',
        'online_cafe'   => 'کافی‌نت آنلاین',
        'other'         => 'خدمات اداری و اینترنتی',
    );
}

function karsabt_core_statuses() {
    return array(
        'new'         => 'جدید',
        'in_progress' => 'در حال بررسی',
        'completed'   => 'انجام‌شده',
        'rejected'    => 'مختومه',
    );
}

function karsabt_core_order_form() {
    $result = isset($_GET['karsabt_order']) ? sanitize_key(wp_unslash($_GET['karsabt_order'])) : '';
    $notice = '';

    if ('success' === $result) {
        $notice = '<p class="karsabt-form-notice karsabt-form-success" role="status">درخواست شما با موفقیت ثبت شد. برای پیگیری، اطلاعات تماس خود را در دسترس نگه دارید.</p>';
    } elseif ('invalid' === $result) {
        $notice = '<p class="karsabt-form-notice karsabt-form-error" role="alert">اطلاعات واردشده کامل یا معتبر نیست. لطفاً فرم را بررسی کنید.</p>';
    } elseif ('error' === $result) {
        $notice = '<p class="karsabt-form-notice karsabt-form-error" role="alert">ثبت درخواست انجام نشد. لطفاً کمی بعد دوباره تلاش کنید.</p>';
    }

    ob_start();
    echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
    <form class="karsabt-order-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="karsabt_submit_order">
        <?php echo wp_nonce_field('karsabt_submit_order', 'karsabt_order_nonce', true, false); ?>
        <p>
            <label for="karsabt-customer-name">نام و نام خانوادگی</label>
            <input id="karsabt-customer-name" name="customer_name" type="text" maxlength="100" autocomplete="name" required>
        </p>
        <p>
            <label for="karsabt-customer-phone">شماره تماس</label>
            <input id="karsabt-customer-phone" name="customer_phone" type="tel" maxlength="20" inputmode="tel" autocomplete="tel" required>
        </p>
        <p>
            <label for="karsabt-service">نوع خدمت</label>
            <select id="karsabt-service" name="service" required>
                <option value="">انتخاب خدمت</option>
                <?php foreach (karsabt_core_services() as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="karsabt-order-message">توضیحات (اختیاری)</label>
            <textarea id="karsabt-order-message" name="customer_message" rows="4" maxlength="2000"></textarea>
        </p>
        <p class="karsabt-honeypot" aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden">
            <label for="karsabt-website">این قسمت را خالی بگذارید</label>
            <input id="karsabt-website" name="website" type="text" tabindex="-1" autocomplete="off">
        </p>
        <button type="submit">ثبت درخواست</button>
        <p class="karsabt-form-privacy">اطلاعات شما فقط برای بررسی و پیگیری همین درخواست استفاده می‌شود.</p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('karsabt_order_form', 'karsabt_core_order_form');

function karsabt_core_redirect_form($result) {
    $url = wp_get_referer();
    if (!$url) {
        $url = home_url('/');
    }
    $url = remove_query_arg('karsabt_order', $url);
    wp_safe_redirect(add_query_arg('karsabt_order', $result, $url) . '#contact');
    exit;
}

function karsabt_core_submit_order() {
    if (
        !isset($_POST['karsabt_order_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['karsabt_order_nonce'])), 'karsabt_submit_order')
    ) {
        karsabt_core_redirect_form('invalid');
    }

    // فیلد مخفی ضد هرزنامه؛ پر بودن آن باعث رد بی‌سروصدای درخواست می‌شود.
    if (!empty($_POST['website'])) {
        karsabt_core_redirect_form('invalid');
    }

    $name = isset($_POST['customer_name']) ? sanitize_text_field(wp_unslash($_POST['customer_name'])) : '';
    $phone = isset($_POST['customer_phone']) ? sanitize_text_field(wp_unslash($_POST['customer_phone'])) : '';
    $service = isset($_POST['service']) ? sanitize_key(wp_unslash($_POST['service'])) : '';
    $message = isset($_POST['customer_message']) ? sanitize_textarea_field(wp_unslash($_POST['customer_message'])) : '';

    $phone_pattern = '/^[0-9۰-۹٠-٩+()\-\s]{8,20}$/u';
    $services = karsabt_core_services();

    if (
        '' === $name ||
        !preg_match($phone_pattern, $phone) ||
        !isset($services[$service]) ||
        strlen($name) > 400 ||
        strlen($message) > 8000
    ) {
        karsabt_core_redirect_form('invalid');
    }

    $order_id = wp_insert_post(array(
        'post_type'   => 'karsabt_order',
        'post_status' => 'private',
        'post_title'  => 'درخواست جدید - ' . current_time('Y-m-d H:i'),
    ), true);

    if (is_wp_error($order_id) || !$order_id) {
        karsabt_core_redirect_form('error');
    }

    update_post_meta($order_id, '_karsabt_customer_name', $name);
    update_post_meta($order_id, '_karsabt_customer_phone', $phone);
    update_post_meta($order_id, '_karsabt_service', $service);
    update_post_meta($order_id, '_karsabt_customer_message', $message);
    update_post_meta($order_id, '_karsabt_order_status', 'new');
    update_post_meta($order_id, '_karsabt_tracking_code', karsabt_core_generate_tracking_code($order_id));
    update_post_meta($order_id, '_karsabt_status_history', array(
        array('status' => 'new', 'label' => 'جدید', 'time' => current_time('mysql'), 'user_id' => 0),
    ));

    karsabt_core_redirect_form('success');
}
add_action('admin_post_nopriv_karsabt_submit_order', 'karsabt_core_submit_order');
add_action('admin_post_karsabt_submit_order', 'karsabt_core_submit_order');


/**
 * نمایش امن وضعیت درخواست با کد پیگیری؛ هیچ اطلاعات هویتی یا یادداشت داخلی برگردانده نمی‌شود.
 */
function karsabt_core_order_tracking() {
    global $wpdb;

    $result_html = '';
    $submitted = isset($_POST['karsabt_tracking_submit']);
    if ($submitted) {
        $nonce = isset($_POST['karsabt_tracking_nonce']) ? sanitize_text_field(wp_unslash($_POST['karsabt_tracking_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'karsabt_tracking_lookup')) {
            $result_html = '<p class="karsabt-form-notice karsabt-form-error" role="alert">درخواست معتبر نیست. صفحه را تازه‌سازی و دوباره تلاش کنید.</p>';
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
            $rate_key = 'karsabt_track_' . hash('sha256', $ip);
            $attempts = (int) get_transient($rate_key);
            if ($attempts >= 8) {
                $result_html = '<p class="karsabt-form-notice karsabt-form-error" role="alert">تعداد تلاش‌ها بیش از حد مجاز است. ۱۵ دقیقه دیگر دوباره تلاش کنید.</p>';
            } else {
                set_transient($rate_key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
                $code = isset($_POST['tracking_code']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['tracking_code']))) : '';
                if (!preg_match('/^KRS-[0-9]{4,6}-[0-9]{6}-[A-Z0-9]{4}$/', $code)) {
                    $result_html = '<p class="karsabt-form-notice karsabt-form-error" role="alert">کد پیگیری معتبر نیست یا درخواستی با این کد پیدا نشد.</p>';
                } else {
                    $meta_table = $wpdb->postmeta;
                    $posts_table = $wpdb->posts;
                    $order_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT p.ID FROM {$posts_table} p INNER JOIN {$meta_table} pm ON p.ID = pm.post_id WHERE p.post_type = %s AND p.post_status = %s AND pm.meta_key = %s AND pm.meta_value = %s LIMIT 1",
                        'karsabt_order',
                        'private',
                        '_karsabt_tracking_code',
                        $code
                    ));

                    if ($order_id) {
                        $status = get_post_meta((int) $order_id, '_karsabt_order_status', true);
                        $statuses = karsabt_core_statuses();
                        $history = get_post_meta((int) $order_id, '_karsabt_status_history', true);
                        $history = is_array($history) ? $history : array();
                        $last_event = $history ? end($history) : array();
                        $status_label = isset($statuses[$status]) ? $statuses[$status] : 'در حال بررسی';
                        $updated_at = isset($last_event['time']) ? $last_event['time'] : get_post_field('post_date', (int) $order_id);
                        $result_html = '<div class="karsabt-tracking-result" role="status"><p><strong>وضعیت درخواست:</strong> ' . esc_html($status_label) . '</p><p><strong>آخرین به‌روزرسانی:</strong> ' . esc_html($updated_at) . '</p></div>';
                    } else {
                        $result_html = '<p class="karsabt-form-notice karsabt-form-error" role="alert">کد پیگیری معتبر نیست یا درخواستی با این کد پیدا نشد.</p>';
                    }
                }
            }
        }
    }

    ob_start();
    echo $result_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
    <form class="karsabt-order-form karsabt-tracking-form" method="post">
        <?php echo wp_nonce_field('karsabt_tracking_lookup', 'karsabt_tracking_nonce', true, false); ?>
        <p>
            <label for="karsabt-tracking-code">کد پیگیری</label>
            <input id="karsabt-tracking-code" name="tracking_code" type="text" maxlength="24" placeholder="KRS-YYMM-000001-AB12" autocomplete="off" required>
        </p>
        <button type="submit" name="karsabt_tracking_submit" value="1">پیگیری درخواست</button>
        <p class="karsabt-form-privacy">در این بخش فقط وضعیت درخواست نمایش داده می‌شود.</p>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('karsabt_order_tracking', 'karsabt_core_order_tracking');

function karsabt_core_generate_tracking_code($order_id) {
    return 'KRS-' . gmdate('ym') . '-' . str_pad((string) $order_id, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(wp_generate_password(4, false, false));
}

function karsabt_core_add_order_meta_box() {
    add_meta_box(
        'karsabt-order-details',
        'اطلاعات درخواست',
        'karsabt_core_render_order_meta_box',
        'karsabt_order',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_karsabt_order', 'karsabt_core_add_order_meta_box');

function karsabt_core_render_order_meta_box($post) {
    wp_nonce_field('karsabt_save_order', 'karsabt_save_order_nonce');

    $name = get_post_meta($post->ID, '_karsabt_customer_name', true);
    $phone = get_post_meta($post->ID, '_karsabt_customer_phone', true);
    $service = get_post_meta($post->ID, '_karsabt_service', true);
    $message = get_post_meta($post->ID, '_karsabt_customer_message', true);
    $status = get_post_meta($post->ID, '_karsabt_order_status', true);
    $statuses = karsabt_core_statuses();
    $tracking_code = get_post_meta($post->ID, '_karsabt_tracking_code', true);
    $history = get_post_meta($post->ID, '_karsabt_status_history', true);
    $history = is_array($history) ? $history : array();
    $notes = get_post_meta($post->ID, '_karsabt_internal_notes', true);
    $notes = is_array($notes) ? $notes : array();
    ?>
    <table class="form-table">
        <tr><th>کد پیگیری</th><td><strong><?php echo esc_html($tracking_code ? $tracking_code : 'برای درخواست قدیمی ثبت نشده'); ?></strong></td></tr>
        <tr><th>نام مشتری</th><td><?php echo esc_html($name); ?></td></tr>
        <tr><th>شماره تماس</th><td><a href="<?php echo esc_url('tel:' . preg_replace('/[^0-9+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a></td></tr>
        <tr><th>خدمت</th><td><?php echo esc_html(isset(karsabt_core_services()[$service]) ? karsabt_core_services()[$service] : '—'); ?></td></tr>
        <tr><th>توضیحات مشتری</th><td><?php echo nl2br(esc_html($message)); ?></td></tr>
        <tr>
            <th><label for="karsabt-order-status">وضعیت درخواست</label></th>
            <td>
                <select name="karsabt_order_status" id="karsabt-order-status">
                    <?php foreach ($statuses as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($status ? $status : 'new', $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <hr>
    <h3>تاریخچه وضعیت درخواست</h3>
    <?php if ($history) : ?>
        <ol>
            <?php foreach (array_reverse($history) as $event) : ?>
                <li><?php echo esc_html(isset($event['label']) ? $event['label'] : 'وضعیت'); ?> — <?php echo esc_html(isset($event['time']) ? $event['time'] : ''); ?></li>
            <?php endforeach; ?>
        </ol>
    <?php else : ?><p>تاریخچه‌ای ثبت نشده است.</p><?php endif; ?>
    <hr>
    <h3>یادداشت‌های داخلی (فقط مدیران)</h3>
    <?php if ($notes) : ?>
        <ul>
            <?php foreach (array_reverse($notes) as $note) : ?>
                <li><strong><?php echo esc_html(isset($note['author']) ? $note['author'] : 'مدیر'); ?></strong> — <?php echo esc_html(isset($note['time']) ? $note['time'] : ''); ?><br><?php echo nl2br(esc_html(isset($note['text']) ? $note['text'] : '')); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <p><label for="karsabt-internal-note">افزودن یادداشت جدید</label></p>
    <textarea id="karsabt-internal-note" name="karsabt_internal_note" rows="3" maxlength="2000" class="widefat"></textarea>
    <p class="description">این یادداشت به مشتری نمایش داده نمی‌شود.</p>
    <?php
}

function karsabt_core_save_order_meta($post_id) {
    if (
        !isset($_POST['karsabt_save_order_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['karsabt_save_order_nonce'])), 'karsabt_save_order') ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        !current_user_can('edit_post', $post_id)
    ) {
        return;
    }

    $status = isset($_POST['karsabt_order_status']) ? sanitize_key(wp_unslash($_POST['karsabt_order_status'])) : 'new';
    $statuses = karsabt_core_statuses();

    $old_status = get_post_meta($post_id, '_karsabt_order_status', true);
    if (isset($statuses[$status]) && $status !== $old_status) {
        update_post_meta($post_id, '_karsabt_order_status', $status);
        $history = get_post_meta($post_id, '_karsabt_status_history', true);
        $history = is_array($history) ? $history : array();
        $history[] = array('status' => $status, 'label' => $statuses[$status], 'time' => current_time('mysql'), 'user_id' => get_current_user_id());
        update_post_meta($post_id, '_karsabt_status_history', $history);
    }

    if (isset($_POST['karsabt_internal_note'])) {
        $note_text = sanitize_textarea_field(wp_unslash($_POST['karsabt_internal_note']));
        if ('' !== trim($note_text) && strlen($note_text) <= 8000) {
            $notes = get_post_meta($post_id, '_karsabt_internal_notes', true);
            $notes = is_array($notes) ? $notes : array();
            $user = wp_get_current_user();
            $notes[] = array('text' => $note_text, 'author' => $user->display_name, 'user_id' => get_current_user_id(), 'time' => current_time('mysql'));
            update_post_meta($post_id, '_karsabt_internal_notes', $notes);
        }
    }

    if (!get_post_meta($post_id, '_karsabt_tracking_code', true)) {
        update_post_meta($post_id, '_karsabt_tracking_code', karsabt_core_generate_tracking_code($post_id));
    }
}
add_action('save_post_karsabt_order', 'karsabt_core_save_order_meta');

function karsabt_core_order_columns($columns) {
    return array(
        'cb'       => isset($columns['cb']) ? $columns['cb'] : '',
        'title'    => 'کد / شناسه درخواست',
        'service'  => 'خدمت',
        'customer' => 'مشتری',
        'phone'    => 'شماره تماس',
        'status'   => 'وضعیت',
        'date'     => 'تاریخ ثبت',
    );
}
add_filter('manage_karsabt_order_posts_columns', 'karsabt_core_order_columns');

function karsabt_core_order_column_content($column, $post_id) {
    if ('title' === $column) {
        $tracking_code = get_post_meta($post_id, '_karsabt_tracking_code', true);
        if ($tracking_code) {
            echo '<strong>' . esc_html($tracking_code) . '</strong><br>';
        }
        echo '<a href="' . esc_url(get_edit_post_link($post_id)) . '">مشاهده جزئیات</a>';
    } elseif ('service' === $column) {
        $service = get_post_meta($post_id, '_karsabt_service', true);
        $services = karsabt_core_services();
        echo esc_html(isset($services[$service]) ? $services[$service] : '—');
    } elseif ('customer' === $column) {
        echo esc_html(get_post_meta($post_id, '_karsabt_customer_name', true));
    } elseif ('phone' === $column) {
        echo esc_html(get_post_meta($post_id, '_karsabt_customer_phone', true));
    } elseif ('status' === $column) {
        $status = get_post_meta($post_id, '_karsabt_order_status', true);
        $statuses = karsabt_core_statuses();
        echo esc_html(isset($statuses[$status]) ? $statuses[$status] : 'جدید');
    }
}
add_action('manage_karsabt_order_posts_custom_column', 'karsabt_core_order_column_content', 10, 2);
require_once __DIR__ . '/includes/admin-orders.php';
