<?php
if (!defined('ABSPATH')) { exit; }
function karsabt_setup() {
    load_theme_textdomain('karsabt', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array('height'=>80,'width'=>260,'flex-height'=>true,'flex-width'=>true));
    add_theme_support('html5', array('search-form','comment-form','comment-list','gallery','caption','style','script'));
    register_nav_menus(array('primary' => 'منوی اصلی'));
}
add_action('after_setup_theme', 'karsabt_setup');
function karsabt_assets() {
    wp_enqueue_style('karsabt-style', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_script('karsabt-main', get_template_directory_uri() . '/assets/js/theme.js', array(), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'karsabt_assets');
add_filter('excerpt_length', function($length){ return 18; });
add_filter('excerpt_more', function($more){ return '…'; });
