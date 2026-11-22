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

/** ثبت سفارش‌های مشتریان (ذخیره امن در پیشخوان) */
function karsabt_register_order_post_type() {
    register_post_type('karsabt_order', array(
        'labels'=>array('name'=>'سفارش‌ها','singular_name'=>'سفارش','menu_name'=>'سفارش‌ها','edit_item'=>'مشاهده سفارش'),
        'public'=>false,'show_ui'=>true,'show_in_menu'=>true,'show_in_rest'=>false,
        'supports'=>array('title'),'capability_type'=>'post','map_meta_cap'=>true,
        'menu_icon'=>'dashicons-clipboard'
    ));
}
add_action('init','karsabt_register_order_post_type');

function karsabt_order_details_box() {
    add_meta_box('karsabt_order_info','اطلاعات مشتری و سفارش','karsabt_order_details_render','karsabt_order','normal','high');
}
add_action('add_meta_boxes_karsabt_order','karsabt_order_details_box');
function karsabt_order_details_render($post) {
    $fields=array('نام مشتری'=>'_karsabt_customer_name','شماره تماس'=>'_karsabt_customer_phone','خدمت'=>'_karsabt_order_service','شرح درخواست'=>'_karsabt_order_message','شناسه سفارش'=>'_karsabt_order_code','زمان ثبت'=>'_karsabt_order_time');
    echo '<table class="widefat striped"><tbody>';
    foreach($fields as $label=>$meta){$value=get_post_meta($post->ID,$meta,true);echo '<tr><th style="width:180px">'.esc_html($label).'</th><td>'.nl2br(esc_html($value)).'</td></tr>';}
    echo '</tbody></table>';
}

function karsabt_order_submit() {
    if(!isset($_POST['karsabt_order_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['karsabt_order_nonce'])),'karsabt_order_submit')) wp_die('درخواست نامعتبر است.');
    if(!empty($_POST['karsabt_website'])) wp_die('درخواست پذیرفته نشد.');
    $name=isset($_POST['karsabt_customer_name'])?sanitize_text_field(wp_unslash($_POST['karsabt_customer_name'])):'';
    $phone=isset($_POST['karsabt_customer_phone'])?sanitize_text_field(wp_unslash($_POST['karsabt_customer_phone'])):'';
    $message=isset($_POST['karsabt_customer_message'])?sanitize_textarea_field(wp_unslash($_POST['karsabt_customer_message'])):'';
    $service_id=isset($_POST['karsabt_service_id'])?absint($_POST['karsabt_service_id']):0;
    if(mb_strlen($name)<2||mb_strlen($phone)<7||!$service_id||get_post_type($service_id)!=='karsabt_service') wp_die('اطلاعات واردشده کامل یا معتبر نیست. لطفاً بازگشته و فرم را بررسی کنید.');
    $service_title=get_the_title($service_id);
    $code='KS-'.gmdate('ymd').'-'.wp_rand(1000,9999);
    $order_id=wp_insert_post(array('post_type'=>'karsabt_order','post_status'=>'karsabt_new','post_title'=>$code.' | '.$name,'post_content'=>''),true);
    if(is_wp_error($order_id)||!$order_id) wp_die('ثبت سفارش انجام نشد. لطفاً دوباره تلاش کنید.');
    update_post_meta($order_id,'_karsabt_customer_name',$name);
    update_post_meta($order_id,'_karsabt_customer_phone',$phone);
    update_post_meta($order_id,'_karsabt_order_service',$service_title);
    update_post_meta($order_id,'_karsabt_order_message',$message);
    update_post_meta($order_id,'_karsabt_order_code',$code);
    update_post_meta($order_id,'_karsabt_order_time',current_time('mysql'));
    wp_mail(get_option('admin_email'),'سفارش جدید کارثبت: '.$code,"سفارش جدید ثبت شد\nنام: {$name}\nشماره تماس: {$phone}\nخدمت: {$service_title}\nشرح: {$message}");
    $redirect=add_query_arg('order_submitted','1',get_permalink($service_id));
    wp_safe_redirect($redirect);exit;
}
add_action('admin_post_nopriv_karsabt_order_submit','karsabt_order_submit');
add_action('admin_post_karsabt_order_submit','karsabt_order_submit');


/** گردش وضعیت سفارش‌ها */
function karsabt_order_workflow_statuses() {
    $statuses = array(
        'karsabt_new' => 'جدید',
        'karsabt_processing' => 'در حال انجام',
        'karsabt_completed' => 'تکمیل شده',
        'karsabt_cancelled' => 'لغو شده',
    );
    foreach ($statuses as $key => $label) {
        register_post_status($key, array(
            'label' => $label,
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop($label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>')
        ));
    }
}
add_action('init', 'karsabt_order_workflow_statuses', 1);

/** انتخاب وضعیت در صفحه ویرایش سفارش */
function karsabt_order_status_box() {
    add_meta_box('karsabt_order_status', 'وضعیت سفارش', 'karsabt_order_status_render', 'karsabt_order', 'side', 'high');
}
add_action('add_meta_boxes_karsabt_order', 'karsabt_order_status_box');
function karsabt_order_status_render($post) {
    wp_nonce_field('karsabt_order_status_save', 'karsabt_order_status_nonce');
    $current = get_post_status($post->ID);
    $statuses = array('karsabt_new'=>'جدید','karsabt_processing'=>'در حال انجام','karsabt_completed'=>'تکمیل شده','karsabt_cancelled'=>'لغو شده');
    echo '<label for="karsabt_order_status_select">انتخاب وضعیت</label><select name="karsabt_order_status_select" id="karsabt_order_status_select" style="width:100%;margin-top:8px">';
    foreach($statuses as $key=>$label) echo '<option value="'.esc_attr($key).'" '.selected($current,$key,false).'>'.esc_html($label).'</option>';
    echo '</select>';
}
function karsabt_order_status_save($post_id) {
    if(!isset($_POST['karsabt_order_status_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['karsabt_order_status_nonce'])),'karsabt_order_status_save')) return;
    if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE) return;
    if(wp_is_post_revision($post_id)||wp_is_post_autosave($post_id)) return;
    if(!current_user_can('edit_post',$post_id)) return;
    $status=isset($_POST['karsabt_order_status_select'])?sanitize_key(wp_unslash($_POST['karsabt_order_status_select'])):'';
    $allowed=array('karsabt_new','karsabt_processing','karsabt_completed','karsabt_cancelled');
    if(in_array($status,$allowed,true)&&get_post_status($post_id)!==$status) {
        remove_action('save_post_karsabt_order','karsabt_order_status_save');
        wp_update_post(array('ID'=>$post_id,'post_status'=>$status));
        add_action('save_post_karsabt_order','karsabt_order_status_save');
    }
}
add_action('save_post_karsabt_order','karsabt_order_status_save');

/** ستون‌های اختصاصی سفارش‌ها */
function karsabt_order_admin_columns($columns) {
    $new=array();
    $new['cb']=isset($columns['cb'])?$columns['cb']:'<input type="checkbox" />';
    $new['title']='سفارش';
    $new['karsabt_code']='کد سفارش';
    $new['karsabt_customer']='مشتری';
    $new['karsabt_phone']='شماره تماس';
    $new['karsabt_service']='خدمت';
    $new['karsabt_status']='وضعیت';
    $new['date']='تاریخ ثبت';
    return $new;
}
add_filter('manage_karsabt_order_posts_columns','karsabt_order_admin_columns');
function karsabt_order_admin_column_content($column,$post_id) {
    switch($column) {
        case 'karsabt_code': echo esc_html(get_post_meta($post_id,'_karsabt_order_code',true)); break;
        case 'karsabt_customer': echo esc_html(get_post_meta($post_id,'_karsabt_customer_name',true)); break;
        case 'karsabt_phone': echo esc_html(get_post_meta($post_id,'_karsabt_customer_phone',true)); break;
        case 'karsabt_service': echo esc_html(get_post_meta($post_id,'_karsabt_order_service',true)); break;
        case 'karsabt_status':
            $map=array('karsabt_new'=>'جدید','karsabt_processing'=>'در حال انجام','karsabt_completed'=>'تکمیل شده','karsabt_cancelled'=>'لغو شده');
            echo isset($map[get_post_status($post_id)])?esc_html($map[get_post_status($post_id)]):esc_html(get_post_status($post_id)); break;
    }
}
add_action('manage_karsabt_order_posts_custom_column','karsabt_order_admin_column_content',10,2);

/** فیلتر وضعیت در فهرست سفارش‌ها */
function karsabt_order_status_filter_dropdown() {
    global $typenow;
    if($typenow!=='karsabt_order') return;
    $selected=isset($_GET['karsabt_status_filter'])?sanitize_key(wp_unslash($_GET['karsabt_status_filter'])):'';
    $statuses=array('karsabt_new'=>'جدید','karsabt_processing'=>'در حال انجام','karsabt_completed'=>'تکمیل شده','karsabt_cancelled'=>'لغو شده');
    echo '<select name="karsabt_status_filter"><option value="">همه وضعیت‌ها</option>';
    foreach($statuses as $key=>$label) echo '<option value="'.esc_attr($key).'" '.selected($selected,$key,false).'>'.esc_html($label).'</option>';
    echo '</select>';
}
add_action('restrict_manage_posts','karsabt_order_status_filter_dropdown');
function karsabt_order_status_filter_query($query) {
    if(!is_admin()||!$query->is_main_query()) return;
    if($query->get('post_type')!=='karsabt_order') return;
    if(isset($_GET['karsabt_status_filter'])) {
        $status=sanitize_key(wp_unslash($_GET['karsabt_status_filter']));
        if(in_array($status,array('karsabt_new','karsabt_processing','karsabt_completed','karsabt_cancelled'),true)) $query->set('post_status',$status);
    }
}
add_action('pre_get_posts','karsabt_order_status_filter_query');
