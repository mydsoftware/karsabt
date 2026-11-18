<?php
/**
 * فرم‌های تکمیلی هر خدمت و ذخیره امن جزئیات درخواست.
 */
if (!defined('ABSPATH')) { exit; }

function karsabt_service_forms_schema() {
    return array(
        'company' => array(
            'title' => 'اطلاعات اولیه ثبت شرکت',
            'fields' => array(
                'proposed_name' => array('label'=>'نام پیشنهادی شرکت (در صورت انتخاب)', 'type'=>'text', 'required'=>false, 'max'=>160),
                'company_activity' => array('label'=>'موضوع یا زمینه فعالیت شرکت', 'type'=>'textarea', 'required'=>false, 'max'=>1500),
                'partner_count' => array('label'=>'تعداد شرکا/سهامداران', 'type'=>'number', 'required'=>false, 'min'=>1, 'max'=>100),
            ),
        ),
        'trademark' => array(
            'title' => 'اطلاعات اولیه ثبت برند',
            'fields' => array(
                'brand_name' => array('label'=>'نام یا عنوان برند', 'type'=>'text', 'required'=>false, 'max'=>160),
                'brand_latin_name' => array('label'=>'عنوان لاتین (در صورت وجود)', 'type'=>'text', 'required'=>false, 'max'=>160),
                'goods_services' => array('label'=>'محصولات یا خدمات مرتبط با برند', 'type'=>'textarea', 'required'=>false, 'max'=>1500),
            ),
        ),
        'business' => array(
            'title' => 'اطلاعات اولیه جواز کسب',
            'fields' => array(
                'business_activity' => array('label'=>'نوع فعالیت صنفی', 'type'=>'text', 'required'=>false, 'max'=>160),
                'business_city' => array('label'=>'شهر محل فعالیت', 'type'=>'text', 'required'=>false, 'max'=>100),
                'business_address' => array('label'=>'نشانی محل فعالیت (در صورت مشخص بودن)', 'type'=>'textarea', 'required'=>false, 'max'=>500),
            ),
        ),
        'home_business' => array(
            'title' => 'اطلاعات اولیه مشاغل خانگی',
            'fields' => array(
                'job_title' => array('label'=>'عنوان فعالیت یا محصول', 'type'=>'text', 'required'=>false, 'max'=>160),
                'production_location' => array('label'=>'محل انجام فعالیت', 'type'=>'text', 'required'=>false, 'max'=>160),
                'activity_description' => array('label'=>'شرح کوتاه فعالیت', 'type'=>'textarea', 'required'=>false, 'max'=>1200),
            ),
        ),
        'online_cafe' => array(
            'title' => 'جزئیات خدمت کافی‌نت آنلاین',
            'fields' => array(
                'requested_service' => array('label'=>'خدمت موردنیاز', 'type'=>'text', 'required'=>true, 'max'=>180),
                'deadline' => array('label'=>'مهلت یا زمان موردنظر (اختیاری)', 'type'=>'text', 'required'=>false, 'max'=>100),
                'details' => array('label'=>'توضیحات تکمیلی', 'type'=>'textarea', 'required'=>false, 'max'=>1200),
            ),
        ),
        'other' => array(
            'title' => 'شرح خدمت درخواستی',
            'fields' => array(
                'requested_service' => array('label'=>'عنوان خدمت موردنیاز', 'type'=>'text', 'required'=>true, 'max'=>180),
                'details' => array('label'=>'توضیحات', 'type'=>'textarea', 'required'=>false, 'max'=>1500),
            ),
        ),
    );
}

function karsabt_service_forms_render_fields() {
    $schema = karsabt_service_forms_schema();
    $tariffs = get_option('karsabt_service_tariffs', array());
    $html = '<div class="karsabt-service-details" id="karsabt-service-details">';
    foreach ($schema as $service => $config) {
        $html .= '<fieldset class="karsabt-service-fieldset" data-service="' . esc_attr($service) . '" hidden><legend>' . esc_html($config['title']) . '</legend>';
        foreach ($config['fields'] as $key => $field) {
            $id = 'karsabt-detail-' . $service . '-' . $key;
            $name = 'service_details[' . $key . ']';
            $required = !empty($field['required']);
            $label = '<label for="' . esc_attr($id) . '">' . esc_html($field['label']) . ($required ? ' *' : '') . '</label>';
            if ('textarea' === $field['type']) {
                $control = '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" rows="3" maxlength="' . esc_attr($field['max']) . '"' . ($required ? ' required' : '') . ' disabled></textarea>';
            } else {
                $type = 'number' === $field['type'] ? 'number' : 'text';
                $control = '<input id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" type="' . esc_attr($type) . '" maxlength="' . esc_attr($field['max']) . '"' . ('number' === $type ? ' min="' . esc_attr($field['min']) . '" max="' . esc_attr($field['max']) . '"' : '') . ($required ? ' required' : '') . ' disabled>'';
            }
            $html .= '<p>' . $label . $control . '</p>';
        }
        $tariff = isset($tariffs[$service]) ? (int) $tariffs[$service] : 0;
        if ($tariff > 0) {
            $html .= '<p class="karsabt-form-tariff">تعرفه ثبت‌شده این خدمت: <strong>' . esc_html(number_format_i18n($tariff)) . ' ریال</strong> <small>(مبلغ نهایی پس از بررسی اعلام می‌شود.)</small></p>';
        }
        $html .= '</fieldset>';
    }
    $html .= '</div><script>(function(){var s=document.getElementById("karsabt-service");var w=document.getElementById("karsabt-service-details");if(!s||!w){return;}function sync(){w.querySelectorAll("[data-service]").forEach(function(fs){var active=fs.getAttribute("data-service")===s.value;fs.hidden=!active;fs.querySelectorAll("input,textarea,select").forEach(function(el){el.disabled=!active;});});}s.addEventListener("change",sync);sync();})();</script>';
    return $html;
}

function karsabt_service_forms_validate($service, $raw) {
    $schema = karsabt_service_forms_schema();
    if (!isset($schema[$service]) || !is_array($raw)) { return new WP_Error('invalid_service_details', 'اطلاعات خدمت معتبر نیست.'); }
    $clean = array();
    foreach ($schema[$service]['fields'] as $key => $field) {
        $value = isset($raw[$key]) && is_scalar($raw[$key]) ? trim((string) wp_unslash($raw[$key])) : '';
        $value = 'textarea' === $field['type'] ? sanitize_textarea_field($value) : sanitize_text_field($value);
        if (!empty($field['required']) && '' === $value) { return new WP_Error('required_service_detail', 'تکمیل اطلاعات خدمت الزامی است.'); }
        if (strlen($value) > (int) $field['max']) { return new WP_Error('service_detail_too_long', 'طول یکی از فیلدها بیش از حد مجاز است.'); }
        if ('number' === $field['type'] && '' !== $value && (!ctype_digit($value) || (int)$value < (int)$field['min'] || (int)$value > (int)$field['max'])) {
            return new WP_Error('invalid_service_number', 'مقدار عددی واردشده معتبر نیست.');
        }
        $clean[$key] = $value;
    }
    return $clean;
}

function karsabt_service_forms_save_details($order_id, $service, $details) {
    if (!is_array($details)) { return false; }
    update_post_meta($order_id, '_karsabt_service_details', array('service'=>$service, 'fields'=>$details));
    return true;
}

function karsabt_service_forms_admin_box() {
    add_meta_box('karsabt-service-details', 'اطلاعات اختصاصی خدمت', 'karsabt_service_forms_admin_box_render', 'karsabt_order', 'normal', 'default');
}
add_action('add_meta_boxes_karsabt_order', 'karsabt_service_forms_admin_box');

function karsabt_service_forms_admin_box_render($post) {
    if (!current_user_can('edit_post', $post->ID)) { return; }
    $saved = get_post_meta($post->ID, '_karsabt_service_details', true);
    if (!is_array($saved) || empty($saved['fields']) || !is_array($saved['fields'])) {
        echo '<p>برای این درخواست اطلاعات اختصاصی ثبت نشده است.</p>'; return;
    }
    $schema = karsabt_service_forms_schema();
    $service = $saved['service'] ?? '';
    $fields = isset($schema[$service]['fields']) ? $schema[$service]['fields'] : array();
    echo '<dl>';
    foreach ($saved['fields'] as $key=>$value) {
        if (!isset($fields[$key]) || !is_scalar($value) || '' === (string)$value) { continue; }
        echo '<dt><strong>' . esc_html($fields[$key]['label']) . '</strong></dt><dd>' . nl2br(esc_html((string)$value)) . '</dd>';
    }
    echo '</dl>';
}
