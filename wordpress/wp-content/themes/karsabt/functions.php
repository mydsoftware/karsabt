<?php
if (!defined('ABSPATH')) { exit; }

function karsabt_setup() {
    load_theme_textdomain('karsabt', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo', array(
        'height' => 80,
        'width' => 260,
        'flex-height' => true,
        'flex-width' => true,
    ));
    add_theme_support('html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery',
        'caption', 'style', 'script',
    ));
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    // پشتیبانی پایه از Elementor و امکان استفاده از قالب‌ساز آن.
    add_theme_support('elementor');
    register_nav_menus(array(
        'primary' => 'منوی اصلی',
        'footer' => 'منوی پایین صفحه',
    ));
    add_image_size('karsabt-card', 640, 420, true);
}
add_action('after_setup_theme', 'karsabt_setup');

function karsabt_assets() {
    $theme_version = wp_get_theme()->get('Version');
    $style_path = get_stylesheet_directory() . '/style.css';
    $script_path = get_template_directory() . '/assets/js/theme.js';
    $style_version = file_exists($style_path) ? (string) filemtime($style_path) : $theme_version;
    $script_version = file_exists($script_path) ? (string) filemtime($script_path) : $theme_version;

    wp_enqueue_style('karsabt-style', get_stylesheet_uri(), array(), $style_version);
    wp_enqueue_script('karsabt-main', get_template_directory_uri() . '/assets/js/theme.js', array(), $script_version, true);
}
add_action('wp_enqueue_scripts', 'karsabt_assets');

function karsabt_excerpt_length($length) {
    return 24;
}
add_filter('excerpt_length', 'karsabt_excerpt_length');

function karsabt_excerpt_more($more) {
    return '…';
}
add_filter('excerpt_more', 'karsabt_excerpt_more');

/** ثبت جایگاه‌های استاندارد برای قالب‌ساز Elementor. */
function karsabt_register_elementor_locations($elementor_theme_manager) {
    if (is_object($elementor_theme_manager) && method_exists($elementor_theme_manager, 'register_all_core_location')) {
        $elementor_theme_manager->register_all_core_location();
    }
}
add_action('elementor/theme/register_locations', 'karsabt_register_elementor_locations');
