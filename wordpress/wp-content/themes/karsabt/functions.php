<?php
if (!defined('ABSPATH')) { exit; }

/** راه‌اندازی قابلیت‌های اصلی قالب */
function karsabt_setup() {
    load_theme_textdomain('karsabt', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array('height'=>80,'width'=>260,'flex-height'=>true,'flex-width'=>true));
    add_theme_support('html5', array('search-form','comment-form','comment-list','gallery','caption','style','script'));
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    register_nav_menus(array('primary' => 'منوی اصلی'));
    add_image_size('karsabt-card', 720, 440, true);
}
add_action('after_setup_theme', 'karsabt_setup');

/** بارگذاری فایل‌های استایل و اسکریپت */
function karsabt_assets() {
    $style_path = get_stylesheet_directory() . '/style.css';
    $script_path = get_template_directory() . '/assets/js/theme.js';
    $style_version = file_exists($style_path) ? (string) filemtime($style_path) : '1.1.0';
    $script_version = file_exists($script_path) ? (string) filemtime($script_path) : '1.1.0';
    wp_enqueue_style('karsabt-style', get_stylesheet_uri(), array(), $style_version);
    wp_enqueue_script('karsabt-main', get_template_directory_uri() . '/assets/js/theme.js', array(), $script_version, true);
}
add_action('wp_enqueue_scripts', 'karsabt_assets');

/** تنظیمات قابل ویرایش اطلاعات تماس از پیشخوان */
function karsabt_customize_register($wp_customize) {
    $wp_customize->add_section('karsabt_contact', array(
        'title' => 'اطلاعات تماس کارثبت',
        'priority' => 35,
        'description' => 'اطلاعات این بخش در سربرگ و پابرگ سایت نمایش داده می‌شود.'
    ));
    $fields = array(
        'karsabt_phone' => array('label'=>'شماره تماس', 'default'=>''),
        'karsabt_email' => array('label'=>'ایمیل', 'default'=>'info@karsabt.ir'),
        'karsabt_address' => array('label'=>'نشانی', 'default'=>'دزفول، خوزستان'),
    );
    foreach ($fields as $id => $field) {
        $wp_customize->add_setting($id, array(
            'default' => $field['default'],
            'sanitize_callback' => $id === 'karsabt_email' ? 'sanitize_email' : 'sanitize_text_field',
            'transport' => 'refresh'
        ));
        $wp_customize->add_control($id, array(
            'section' => 'karsabt_contact',
            'label' => $field['label'],
            'type' => 'text'
        ));
    }
}
add_action('customize_register', 'karsabt_customize_register');

add_filter('excerpt_length', function($length) { return 24; });
add_filter('excerpt_more', function($more) { return '…'; });

/** ثبت نوع محتوای خدمات برای مدیریت از پیشخوان */
function karsabt_register_service_post_type() {
    register_post_type('karsabt_service', array(
        'labels' => array(
            'name' => 'خدمات کارثبت',
            'singular_name' => 'خدمت',
            'add_new' => 'افزودن خدمت',
            'add_new_item' => 'افزودن خدمت جدید',
            'edit_item' => 'ویرایش خدمت',
            'new_item' => 'خدمت جدید',
            'view_item' => 'مشاهده خدمت',
            'search_items' => 'جستجوی خدمات',
            'not_found' => 'خدمتی یافت نشد',
            'menu_name' => 'خدمات'
        ),
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'services'),
        'menu_icon' => 'dashicons-portfolio',
        'supports' => array('title','editor','excerpt','thumbnail','page-attributes'),
    ));
}
add_action('init', 'karsabt_register_service_post_type');

/** فیلدهای نماد و قیمت‌گذاری خدمات */
function karsabt_service_meta_boxes() {
    add_meta_box('karsabt_service_details', 'مشخصات و قیمت خدمت', 'karsabt_service_details_render', 'karsabt_service', 'side', 'default');
}
add_action('add_meta_boxes', 'karsabt_service_meta_boxes');
function karsabt_service_details_render($post) {
    wp_nonce_field('karsabt_service_meta_save', 'karsabt_service_meta_nonce');
    $icon = get_post_meta($post->ID, '_karsabt_service_icon', true);
    $price = get_post_meta($post->ID, '_karsabt_service_price', true);
    $discount = get_post_meta($post->ID, '_karsabt_service_discount', true);
    $featured = get_post_meta($post->ID, '_karsabt_service_featured', true);
    $popular = get_post_meta($post->ID, '_karsabt_service_popular', true);
    echo '<p><label for="karsabt_service_icon_field">نماد / ایموجی</label><input id="karsabt_service_icon_field" name="karsabt_service_icon_field" type="text" maxlength="12" style="width:100%" value="' . esc_attr($icon) . '"></p>';
    echo '<p><label for="karsabt_service_price">قیمت پایه (تومان)</label><input id="karsabt_service_price" name="karsabt_service_price" type="number" min="0" step="1000" style="width:100%" value="' . esc_attr($price) . '"></p>';
    echo '<p><label for="karsabt_service_discount">قیمت با تخفیف (تومان)</label><input id="karsabt_service_discount" name="karsabt_service_discount" type="number" min="0" step="1000" style="width:100%" value="' . esc_attr($discount) . '"></p>';
    echo '<p><label><input type="checkbox" name="karsabt_service_featured" value="1" ' . checked($featured, '1', false) . '> خدمت ویژه</label></p>';
    echo '<p><label><input type="checkbox" name="karsabt_service_popular" value="1" ' . checked($popular, '1', false) . '> خدمت پرفروش</label></p>';
}
function karsabt_service_meta_save($post_id) {
    if (!isset($_POST['karsabt_service_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['karsabt_service_meta_nonce'])), 'karsabt_service_meta_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['karsabt_service_icon_field'])) update_post_meta($post_id, '_karsabt_service_icon', sanitize_text_field(wp_unslash($_POST['karsabt_service_icon_field'])));
    foreach (array('karsabt_service_price'=>'_karsabt_service_price','karsabt_service_discount'=>'_karsabt_service_discount') as $field=>$meta) {
        if (isset($_POST[$field])) update_post_meta($post_id, $meta, absint(wp_unslash($_POST[$field])));
    }
    update_post_meta($post_id, '_karsabt_service_featured', isset($_POST['karsabt_service_featured']) ? '1' : '0');
    update_post_meta($post_id, '_karsabt_service_popular', isset($_POST['karsabt_service_popular']) ? '1' : '0');
}
add_action('save_post_karsabt_service', 'karsabt_service_meta_save');
