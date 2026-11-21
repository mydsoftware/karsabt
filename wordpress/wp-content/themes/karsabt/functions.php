<?php
if (!defined('ABSPATH')) { exit; }

function karsabt_setup() {
    load_theme_textdomain('karsabt', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo', array('height' => 80, 'width' => 260, 'flex-height' => true, 'flex-width' => true));
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_editor_style('assets/css/editor-style.css');
    add_theme_support('elementor');
    register_nav_menus(array('primary' => 'منوی اصلی', 'footer' => 'منوی پایین صفحه'));
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
    wp_enqueue_style('karsabt-design-system', get_template_directory_uri() . '/assets/css/design-system.css', array('karsabt-style'), $design_system_version);
    wp_enqueue_script('karsabt-main', get_template_directory_uri() . '/assets/js/theme.js', array(), $script_version, true);
    // وردپرس ۶.۳ به بعد اجرای defer را با ترتیب وابستگی‌ها مدیریت می‌کند.
    if (function_exists('wp_script_add_data')) {
        wp_script_add_data('karsabt-main', 'strategy', 'defer');
    }
}
add_action('wp_enqueue_scripts', 'karsabt_assets');
/** رنگ‌های برند قابل تنظیم؛ مقادیر فقط پس از اعتبارسنجی رنگ وارد CSS می‌شوند. */
function karsabt_custom_brand_styles() {
    $primary = sanitize_hex_color(get_theme_mod('karsabt_brand_color', '#0876d8'));
    $navy = sanitize_hex_color(get_theme_mod('karsabt_navy_color', '#10264c'));
    $css = '';
    if ($primary) { $css .= ':root{--blue:' . $primary . ';--ks-color-primary:' . $primary . ';}'; }
    if ($navy) { $css .= ':root{--navy:' . $navy . ';--ks-color-navy:' . $navy . ';}'; }
    if ($css) { wp_add_inline_style('karsabt-design-system', $css); }
}
add_action('wp_enqueue_scripts', 'karsabt_custom_brand_styles', 20);


function karsabt_excerpt_length($length) { return 24; }
add_filter('excerpt_length', 'karsabt_excerpt_length');
function karsabt_excerpt_more($more) { return '…'; }
add_filter('excerpt_more', 'karsabt_excerpt_more');

function karsabt_register_elementor_locations($elementor_theme_manager) {
    if (is_object($elementor_theme_manager) && method_exists($elementor_theme_manager, 'register_all_core_location')) {
        $elementor_theme_manager->register_all_core_location();
    }
}
add_action('elementor/theme/register_locations', 'karsabt_register_elementor_locations');


/** تنظیمات اطلاعات تماس و نشانی کسب‌وکار از طریق سفارشی‌ساز وردپرس. */
function karsabt_customize_register($wp_customize) {
    $wp_customize->add_section('karsabt_contact', array(
        'title' => 'اطلاعات تماس کارثبت',
        'priority' => 35,
        'description' => 'اطلاعات واقعی کسب‌وکار را وارد کنید. فیلدهای خالی در سایت نمایش داده نمی‌شوند.',
    ));
    $wp_customize->add_setting('karsabt_footer_description', array(
        'default' => 'سامانه خدمات ثبتی و اداری؛ همراه شما برای انجام امور کسب‌وکار.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('karsabt_footer_description', array(
        'section' => 'karsabt_contact',
        'label' => 'توضیحات پایین صفحه',
        'type' => 'textarea',
    ));
    $wp_customize->add_setting('karsabt_cta_label', array(
        'default' => 'ثبت سفارش آنلاین',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('karsabt_cta_label', array(
        'section' => 'karsabt_contact',
        'label' => 'متن دکمه ثبت سفارش',
        'type' => 'text',
    ));
    $wp_customize->add_setting('karsabt_cta_url', array(
        'default' => home_url('/#contact'),
        'sanitize_callback' => 'esc_url_raw',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('karsabt_cta_url', array(
        'section' => 'karsabt_contact',
        'label' => 'پیوند دکمه ثبت سفارش',
        'type' => 'url',
    ));
    // تنظیمات تکمیلی سربرگ، پابرگ و رنگ‌های برند.
    $wp_customize->add_setting('karsabt_show_topbar', array('default' => true, 'sanitize_callback' => 'wp_validate_boolean'));
    $wp_customize->add_control('karsabt_show_topbar', array('section' => 'karsabt_contact', 'label' => 'نمایش نوار اطلاعات تماس', 'type' => 'checkbox'));
    $wp_customize->add_setting('karsabt_show_header_cta', array('default' => true, 'sanitize_callback' => 'wp_validate_boolean'));
    $wp_customize->add_control('karsabt_show_header_cta', array('section' => 'karsabt_contact', 'label' => 'نمایش دکمه سفارش در سربرگ', 'type' => 'checkbox'));
    $wp_customize->add_setting('karsabt_show_mobile_cta', array('default' => true, 'sanitize_callback' => 'wp_validate_boolean'));
    $wp_customize->add_control('karsabt_show_mobile_cta', array('section' => 'karsabt_contact', 'label' => 'نمایش دکمه ثابت سفارش در موبایل', 'type' => 'checkbox'));
    $wp_customize->add_setting('karsabt_footer_copyright', array('default' => 'تمامی حقوق محفوظ است.', 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('karsabt_footer_copyright', array('section' => 'karsabt_contact', 'label' => 'متن حقوق پابرگ', 'type' => 'text'));
    $wp_customize->add_setting('karsabt_brand_color', array('default' => '#0876d8', 'sanitize_callback' => 'sanitize_hex_color'));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'karsabt_brand_color', array('section' => 'karsabt_contact', 'label' => 'رنگ اصلی برند')));
    $wp_customize->add_setting('karsabt_navy_color', array('default' => '#10264c', 'sanitize_callback' => 'sanitize_hex_color'));
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'karsabt_navy_color', array('section' => 'karsabt_contact', 'label' => 'رنگ تیره سربرگ و پابرگ')));
    $fields = array(
        'karsabt_contact_phone' => array('تلفن تماس', 'text', ''),
        'karsabt_contact_email' => array('ایمیل تماس', 'email', ''),
        'karsabt_contact_location' => array('نشانی یا محدوده فعالیت', 'text', ''),
    );
    foreach ($fields as $setting => $field) {
        $wp_customize->add_setting($setting, array(
            'default' => $field[2],
            'sanitize_callback' => $field[1] === 'email' ? 'sanitize_email' : 'sanitize_text_field',
            'transport' => 'refresh',
        ));
        $wp_customize->add_control($setting, array(
            'section' => 'karsabt_contact',
            'label' => $field[0],
            'type' => $field[1],
        ));
    }
}
add_action('customize_register', 'karsabt_customize_register');

function karsabt_contact_value($key) {
    return trim((string) get_theme_mod($key, ''));
}

/** تشخیص افزونه‌های سئو برای جلوگیری از تولید متادیتای تکراری. */
function karsabt_has_seo_plugin() {
    return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') ||
        defined('SEOPRESS_VERSION') || defined('THE_SEO_FRAMEWORK_VERSION') ||
        defined('AIOSEO_VERSION') || class_exists('\RankMath\\Helper');
}

/** توضیح متای کوتاه و پاک‌سازی‌شده برای صفحه جاری. */
function karsabt_get_meta_description() {
    if (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post) {
            $description = has_excerpt($post) ? get_the_excerpt($post) : '';
            if (!$description) {
                $description = wp_strip_all_tags(strip_shortcodes($post->post_content));
            }
            if ($description) {
                return wp_trim_words(preg_replace('/\\s+/u', ' ', $description), 28, '…');
            }
        }
    }
    $description = get_bloginfo('description', 'display');
    return $description ? wp_trim_words(wp_strip_all_tags($description), 28, '…') : '';
}

function karsabt_output_social_meta() {
    if (is_admin() || karsabt_has_seo_plugin()) { return; }
    $description = karsabt_get_meta_description();
    $title = wp_get_document_title();
    $url = is_singular() ? get_permalink() : (is_front_page() ? home_url('/') : get_pagenum_link());
    $image = '';
    if (is_singular() && has_post_thumbnail()) {
        $image = get_the_post_thumbnail_url(get_queried_object_id(), 'large');
    } elseif (has_custom_logo()) {
        $logo_id = get_theme_mod('custom_logo');
        $image = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
    }
    if ($description) {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }
    echo '<meta property="og:type" content="' . (is_singular('post') ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:locale" content="fa_IR">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    if ($description) { echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n"; }
    if ($image) { echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n"; }
    echo '<meta name="twitter:card" content="' . ($image ? 'summary_large_image' : 'summary') . '">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    if ($description) { echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n"; }
    if ($image) { echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n"; }
}
add_action('wp_head', 'karsabt_output_social_meta', 2);

/** مسیر راهنما؛ برای نمایش دستی از شورت‌کد [karsabt_breadcrumbs] استفاده کنید. */
function karsabt_get_breadcrumb_items() {
    $items = array(array('name' => 'خانه', 'url' => home_url('/')));
    if (is_front_page()) { return $items; }
    if (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post && $post->post_type === 'page') {
            $ancestors = array_reverse(get_post_ancestors($post));
            foreach ($ancestors as $ancestor_id) {
                $items[] = array('name' => get_the_title($ancestor_id), 'url' => get_permalink($ancestor_id));
            }
        } elseif ($post instanceof WP_Post && $post->post_type === 'post') {
            $categories = get_the_category($post->ID);
            if (!empty($categories)) {
                $category = $categories[0];
                $items[] = array('name' => $category->name, 'url' => get_category_link($category->term_id));
            }
        }
        $items[] = array('name' => get_the_title($post), 'url' => get_permalink($post));
    } elseif (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) { $items[] = array('name' => single_term_title('', false), 'url' => get_term_link($term)); }
    } elseif (is_search()) {
        $items[] = array('name' => 'نتایج جستجو', 'url' => get_search_link());
    } elseif (is_archive()) {
        $items[] = array('name' => wp_strip_all_tags(get_the_archive_title()), 'url' => get_pagenum_link());
    } elseif (is_404()) {
        $items[] = array('name' => 'صفحه پیدا نشد', 'url' => home_url('/'));
    }
    return $items;
}

function karsabt_breadcrumbs_shortcode() {
    $items = karsabt_get_breadcrumb_items();
    if (is_front_page() || count($items) < 2) { return ''; }
    $html = '<nav class="karsabt-breadcrumbs" aria-label="مسیر صفحه"><ol>';
    $last = count($items) - 1;
    foreach ($items as $index => $item) {
        $html .= '<li>';
        if ($index === $last) {
            $html .= '<span aria-current="page">' . esc_html($item['name']) . '</span>';
        } else {
            $html .= '<a href="' . esc_url($item['url']) . '">' . esc_html($item['name']) . '</a>';
        }
        $html .= '</li>';
    }
    return $html . '</ol></nav>';
}
add_shortcode('karsabt_breadcrumbs', 'karsabt_breadcrumbs_shortcode');

/** نمایش خودکار مسیر راهنما در برگه‌های معمولی؛ صفحات Elementor کنترل دستی دارند. */
function karsabt_prepend_page_breadcrumbs($content) {
    if (is_admin() || !is_singular(array('page', 'post')) || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    $post_id = get_queried_object_id();
    if (is_page() && get_post_meta($post_id, '_elementor_edit_mode', true) === 'builder') {
        return $content;
    }
    if (strpos($content, '[karsabt_breadcrumbs]') !== false || strpos($content, 'karsabt-breadcrumbs') !== false) {
        return $content;
    }
    $breadcrumbs = karsabt_breadcrumbs_shortcode();
    return $breadcrumbs . $content;
}
add_filter('the_content', 'karsabt_prepend_page_breadcrumbs', 8);



function karsabt_output_seo_schema() {
    if (is_admin() || karsabt_has_seo_plugin()) { return; }
    $graph = array();
    $organization = array(
        '@type' => 'Organization',
        '@id' => home_url('/#organization'),
        'name' => get_bloginfo('name'),
        'url' => home_url('/'),
    );
    if (has_custom_logo()) {
        $logo_id = get_theme_mod('custom_logo');
        $logo = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
        if ($logo) { $organization['logo'] = esc_url_raw($logo); }
    }
    $graph[] = $organization;

    $items = karsabt_get_breadcrumb_items();
    if (count($items) > 1) {
        $list = array();
        foreach ($items as $index => $item) {
            $entry = array('@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name']);
            if ($index < count($items) - 1) { $entry['item'] = $item['url']; }
            $list[] = $entry;
        }
        $graph[] = array('@type' => 'BreadcrumbList', 'itemListElement' => $list);
    }
    echo "\n<script type=\"application/ld+json\">" . wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $graph), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . "</script>\n";
}
add_action('wp_head', 'karsabt_output_seo_schema', 20);

/** داده ساختاریافته خدمت برای برگه‌هایی که از الگوی جزئیات خدمت استفاده می‌کنند. */
function karsabt_output_service_schema() {
    if (is_admin() || !is_singular('page') || karsabt_has_seo_plugin()) { return; }
    if (get_page_template_slug(get_queried_object_id()) !== 'page-templates/service-detail.php') { return; }
    $post = get_queried_object();
    if (!$post instanceof WP_Post) { return; }
    $description = has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 35, '…');
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => get_the_title($post),
        'serviceType' => get_the_title($post),
        'url' => get_permalink($post),
        'description' => wp_strip_all_tags($description),
        'provider' => array('@id' => home_url('/#organization')),
        'areaServed' => array('@type' => 'Country', 'name' => 'ایران'),
    );
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
}
add_action('wp_head', 'karsabt_output_service_schema', 22);

/** جعبه پرسش‌های متداول صفحات خدمات؛ هر خط با قالب «پرسش | پاسخ». */
function karsabt_add_faq_meta_box() {
    add_meta_box('karsabt-service-faq', 'پرسش‌های متداول خدمت', 'karsabt_render_faq_meta_box', 'page', 'normal', 'default');
}
add_action('add_meta_boxes', 'karsabt_add_faq_meta_box');

function karsabt_render_faq_meta_box($post) {
    wp_nonce_field('karsabt_save_service_faq', 'karsabt_service_faq_nonce');
    $items = get_post_meta($post->ID, '_karsabt_service_faq', true);
    $lines = array();
    if (is_array($items)) {
        foreach ($items as $item) {
            if (!empty($item['question']) && !empty($item['answer'])) { $lines[] = $item['question'] . ' | ' . $item['answer']; }
        }
    }
    ?>
    <p>هر پرسش و پاسخ را در یک خط وارد کنید و بین آن‌ها از علامت | استفاده کنید.</p>
    <textarea name="karsabt_service_faq" rows="8" style="width:100%" placeholder="برای ثبت شرکت چه مدارکی لازم است؟ | مدارک بسته به نوع شرکت متفاوت است."><?php echo esc_textarea(implode("\n", $lines)); ?></textarea>
    <?php
}

function karsabt_save_service_faq($post_id) {
    if (!isset($_POST['karsabt_service_faq_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['karsabt_service_faq_nonce'])), 'karsabt_save_service_faq')) { return; }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) { return; }
    if (!current_user_can('edit_page', $post_id)) { return; }
    if (!isset($_POST['karsabt_service_faq'])) { delete_post_meta($post_id, '_karsabt_service_faq'); return; }
    $raw = sanitize_textarea_field(wp_unslash($_POST['karsabt_service_faq']));
    $items = array();
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $parts = explode('|', $line, 2);
        if (count($parts) !== 2) { continue; }
        $question = sanitize_text_field(trim($parts[0]));
        $answer = sanitize_textarea_field(trim($parts[1]));
        if ($question !== '' && $answer !== '') { $items[] = array('question' => $question, 'answer' => $answer); }
    }
    if ($items) { update_post_meta($post_id, '_karsabt_service_faq', $items); }
    else { delete_post_meta($post_id, '_karsabt_service_faq'); }
}

add_action('save_post_page', 'karsabt_save_service_faq');

function karsabt_output_service_faq_schema() {
    if (!is_singular('page') || karsabt_has_seo_plugin()) { return; }
    $items = get_post_meta(get_queried_object_id(), '_karsabt_service_faq', true);
    if (!is_array($items) || !$items) { return; }
    $entities = array();
    foreach ($items as $item) {
        if (empty($item['question']) || empty($item['answer'])) { continue; }
        $entities[] = array('@type' => 'Question', 'name' => wp_strip_all_tags($item['question']), 'acceptedAnswer' => array('@type' => 'Answer', 'text' => wp_strip_all_tags($item['answer'])));
    }
    if (!$entities) { return; }
    $schema = array('@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities);
    echo "\n<script type=\"application/ld+json\">" . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . "</script>\n";
}
add_action('wp_head', 'karsabt_output_service_faq_schema', 21);
