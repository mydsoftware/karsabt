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
    add_theme_support('editor-styles');
    add_editor_style('assets/css/editor-style.css');
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

    $design_system_path = get_template_directory() . '/assets/css/design-system.css';
    $design_system_version = file_exists($design_system_path) ? (string) filemtime($design_system_path) : $theme_version;
    wp_enqueue_style(
        'karsabt-design-system',
        get_template_directory_uri() . '/assets/css/design-system.css',
        array('karsabt-style'),
        $design_system_version
    );

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


/**
 * جعبه پرسش‌های متداول صفحات خدمات.
 * هر خط با قالب «پرسش | پاسخ» ذخیره می‌شود.
 */
function karsabt_add_faq_meta_box() {
    add_meta_box(
        'karsabt-service-faq',
        'پرسش‌های متداول خدمت',
        'karsabt_render_faq_meta_box',
        'page',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'karsabt_add_faq_meta_box');

function karsabt_render_faq_meta_box($post) {
    wp_nonce_field('karsabt_save_service_faq', 'karsabt_service_faq_nonce');
    $items = get_post_meta($post->ID, '_karsabt_service_faq', true);
    $lines = array();
    if (is_array($items)) {
        foreach ($items as $item) {
            if (!empty($item['question']) && !empty($item['answer'])) {
                $lines[] = $item['question'] . ' | ' . $item['answer'];
            }
        }
    }
    ?>
    <p>هر پرسش و پاسخ را در یک خط وارد کنید و بین آن‌ها از علامت | استفاده کنید.</p>
    <textarea name="karsabt_service_faq" rows="8" style="width:100%" placeholder="برای ثبت شرکت چه مدارکی لازم است؟ | مدارک بسته به نوع شرکت متفاوت است."><?php echo esc_textarea(implode("\n", $lines)); ?></textarea>
    <?php
}

function karsabt_save_service_faq($post_id) {
    if (!isset($_POST['karsabt_service_faq_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['karsabt_service_faq_nonce'])), 'karsabt_save_service_faq')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_page', $post_id)) {
        return;
    }
    if (!isset($_POST['karsabt_service_faq'])) {
        delete_post_meta($post_id, '_karsabt_service_faq');
        return;
    }

    $raw = sanitize_textarea_field(wp_unslash($_POST['karsabt_service_faq']));
    $items = array();
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $parts = explode('|', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $question = sanitize_text_field(trim($parts[0]));
        $answer = sanitize_textarea_field(trim($parts[1]));
        if ($question !== '' && $answer !== '') {
            $items[] = array('question' => $question, 'answer' => $answer);
        }
    }

    if ($items) {
        update_post_meta($post_id, '_karsabt_service_faq', $items);
    } else {
        delete_post_meta($post_id, '_karsabt_service_faq');
    }
}
add_action('save_post_page', 'karsabt_save_service_faq');

/** نمایش FAQPage JSON-LD فقط زمانی که پرسش و پاسخ واقعی ثبت شده باشد. */
function karsabt_output_service_faq_schema() {
    if (!is_singular('page')) {
        return;
    }
    $items = get_post_meta(get_queried_object_id(), '_karsabt_service_faq', true);
    if (!is_array($items) || !$items) {
        return;
    }
    $entities = array();
    foreach ($items as $item) {
        if (empty($item['question']) || empty($item['answer'])) {
            continue;
        }
        $entities[] = array(
            '@type' => 'Question',
            'name' => wp_strip_all_tags($item['question']),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => wp_strip_all_tags($item['answer']),
            ),
        );
    }
    if (!$entities) {
        return;
    }
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $entities,
    );
    echo "\n<script type=\"application/ld+json\">" . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
}
add_action('wp_head', 'karsabt_output_service_faq_schema');
