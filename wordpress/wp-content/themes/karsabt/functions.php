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
