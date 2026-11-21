<?php
/**
 * مدارک خصوصی درخواست‌های کارثبت؛ فایل‌ها فقط خارج از web root ذخیره می‌شوند.
 */
if (!defined('ABSPATH')) { exit; }

function karsabt_docs_private_dir() {
    if (!defined('KARSABT_PRIVATE_UPLOAD_DIR') || !is_string(KARSABT_PRIVATE_UPLOAD_DIR)) {
        return '';
    }

    $configured = trim(KARSABT_PRIVATE_UPLOAD_DIR);
    if ('' === $configured || !path_is_absolute($configured)) {
        return '';
    }

    $dir = untrailingslashit(wp_normalize_path($configured));
    if (!wp_mkdir_p($dir) || !is_dir($dir) || !is_writable($dir)) {
        return '';
    }

    $real_dir = realpath($dir);
    $real_root = realpath(ABSPATH);
    if (false === $real_dir || false === $real_root) {
        return '';
    }

    $real_dir = untrailingslashit(wp_normalize_path($real_dir));
    $real_root = untrailingslashit(wp_normalize_path($real_root));
    if ($real_dir === $real_root || 0 === strpos($real_dir . '/', $real_root . '/')) {
        return '';
    }

    @chmod($real_dir, 0700);
    return $real_dir;
}

function karsabt_docs_default_checklists() {
    return array(
        'company' => array('!مدارک هویتی شرکا یا سهامداران','!اطلاعات تماس متقاضی','اطلاعات یا مدارک مربوط به نشانی شرکت','مدارک یا اطلاعات تکمیلی موضوع فعالیت'),
        'trademark' => array('!تصویر یا فایل نمونه برند','!مشخصات مالک برند','فهرست کالاها یا خدمات مرتبط','مدارک مرتبط با فعالیت متقاضی'),
        'business' => array('!مدارک هویتی متقاضی','!اطلاعات محل کسب','مدارک مرتبط با نوع فعالیت صنفی'),
        'home_business' => array('!مدارک هویتی متقاضی','!شرح فعالیت یا محصول','تصاویر یا نمونه محصول (در صورت وجود)'),
        'online_cafe' => array('!اطلاعات لازم برای انجام خدمت','فایل‌ها و مستندات مرتبط با درخواست'),
        'other' => array('!اطلاعات و مستندات مرتبط با خدمت درخواستی'),
    );
}

function karsabt_docs_checklists() {
    $saved = get_option('karsabt_service_document_checklists', array());
    $defaults = karsabt_docs_default_checklists();
    if (!is_array($saved)) { $saved = array(); }
    $result = array();
    foreach ($defaults as $service => $items) {
        $lines = isset($saved[$service]) && is_array($saved[$service]) ? $saved[$service] : $items;
        $result[$service] = array();
        foreach ($lines as $line) {
            if (!is_string($line)) { continue; }
            $line = trim($line);
            if ('' === ltrim($line, '!')) { continue; }
            $required = '!' === substr($line, 0, 1);
            $title = sanitize_text_field($required ? substr($line, 1) : $line);
            if ('' !== $title) { $result[$service][] = array('key'=>substr(hash('sha256', $title), 0, 16), 'title'=>$title, 'required'=>$required); }
        }
    }
    return $result;
}

function karsabt_docs_checklist_admin_page() {
    if (!current_user_can('manage_options')) { wp_die('دسترسی مجاز نیست.'); }
    if (isset($_POST['karsabt_save_checklists'])) {
        check_admin_referer('karsabt_save_checklists');
        $raw = isset($_POST['checklists']) && is_array($_POST['checklists']) ? wp_unslash($_POST['checklists']) : array();
        $clean = array();
        foreach (karsabt_core_services() as $service => $label) {
            $text = isset($raw[$service]) && is_string($raw[$service]) ? $raw[$service] : '';
            $rows = preg_split('/\r\n|\r|\n/', $text);
            $clean[$service] = array();
            foreach ($rows as $row) {
                $row = trim($row);
                if ('' === ltrim($row, '!')) { continue; }
                $required = '!' === substr($row, 0, 1);
                $title = sanitize_text_field($required ? substr($row, 1) : $row);
                if ('' !== $title && strlen($title) <= 240) { $clean[$service][] = ($required ? '!' : '') . $title; }
            }
        }
        update_option('karsabt_service_document_checklists', $clean, false);
        echo '<div class="notice notice-success"><p>چک‌لیست مدارک ذخیره شد.</p></div>';
    }
    $checklists = karsabt_docs_checklists();
    echo '<div class="wrap"><h1>چک‌لیست مدارک خدمات</h1><p>هر مدرک را در یک خط بنویسید. ابتدای مدارک الزامی علامت ! بگذارید. این فهرست راهنمای اولیه است و باید متناسب با پرونده و مقررات روز بررسی شود.</p><form method="post">';
    wp_nonce_field('karsabt_save_checklists');
    foreach (karsabt_core_services() as $service => $label) {
        $lines = array();
        foreach ($checklists[$service] as $item) { $lines[] = ($item['required'] ? '!' : '') . $item['title']; }
        echo '<h2>' . esc_html($label) . '</h2><textarea name="checklists[' . esc_attr($service) . ']" rows="5" class="large-text code">' . esc_textarea(implode("\n", $lines)) . '</textarea>';
    }
    echo '<p><button class="button button-primary" name="karsabt_save_checklists" value="1">ذخیره چک‌لیست‌ها</button></p></form></div>';
}

function karsabt_docs_categories() {
    return array('identity' => 'مدارک هویتی', 'company' => 'مدارک شرکت', 'other' => 'سایر مدارک');
}
function karsabt_docs_review_states() {
    return array('pending' => 'در انتظار بررسی', 'approved' => 'تأیید شد', 'revision' => 'نیازمند اصلاح');
}

function karsabt_docs_customer_portal() {
    if (!is_user_logged_in()) {
        return '<p class="karsabt-form-notice">برای مدیریت مدارک ابتدا وارد حساب کاربری شوید.</p>';
    }
    $user_id = get_current_user_id();
    $orders = get_posts(array(
        'post_type' => 'karsabt_order', 'post_status' => 'private', 'posts_per_page' => 100,
        'meta_key' => '_karsabt_customer_user_id', 'meta_value' => (string) $user_id,
        'orderby' => 'date', 'order' => 'DESC',
    ));
    $html = '<section class="karsabt-customer-documents"><h3>مدارک درخواست‌ها</h3>';
    if (!$orders) { return $html . '<p>درخواستی به حساب شما متصل نیست.</p></section>'; }
    if (!karsabt_docs_private_dir()) {
        return $html . '<p class="karsabt-form-notice">ذخیره‌سازی امن هنوز توسط مدیر سایت پیکربندی نشده است؛ بارگذاری فعلاً غیرفعال است.</p></section>';
    }
    foreach ($orders as $order) {
        if ((int) get_post_meta($order->ID, '_karsabt_customer_user_id', true) !== $user_id) { continue; }
        $html .= '<div class="karsabt-document-order"><h4>درخواست ' . esc_html(get_post_meta($order->ID, '_karsabt_tracking_code', true)) . '</h4>';
        $docs = get_post_meta($order->ID, '_karsabt_documents', true);
        $docs = is_array($docs) ? $docs : array();
        $service = get_post_meta($order->ID, '_karsabt_service', true);
        $checklists = karsabt_docs_checklists();
        $items = isset($checklists[$service]) ? $checklists[$service] : array();
        if ($items) {
            $html .= '<h5>چک‌لیست مدارک این خدمت</h5><ul class="karsabt-required-documents">';
            foreach ($items as $item) {
                $matched = false;
                foreach ($docs as $uploaded) {
                    if (($uploaded['checklist_key'] ?? '') === $item['key'] && in_array(($uploaded['status'] ?? ''), array('pending','approved'), true)) { $matched = true; break; }
                }
                $html .= '<li>' . esc_html($item['title']) . ' — ' . esc_html($item['required'] ? 'الزامی' : 'اختیاری') . ' — ' . esc_html($matched ? 'بارگذاری شده' : 'بارگذاری نشده') . '</li>';
            }
            $html .= '</ul>';
        }
        if ($docs) {
            $html .= '<ul>';
            foreach ($docs as $doc) {
                $cat = isset(karsabt_docs_categories()[$doc['category'] ?? '']) ? karsabt_docs_categories()[$doc['category']] : 'سایر مدارک';
                $state = isset(karsabt_docs_review_states()[$doc['status'] ?? '']) ? karsabt_docs_review_states()[$doc['status']] : 'در انتظار بررسی';
                $html .= '<li>' . esc_html($doc['name'] ?? 'مدرک') . ' — ' . esc_html($cat) . ' — ' . esc_html($state);
                // یادداشت داخلی مدیر نباید در پنل مشتری افشا شود.
                $html .= ' <a href="' . esc_url(add_query_arg(array('action'=>'karsabt_document_download','order_id'=>$order->ID,'document_id'=>$doc['id'],'_wpnonce'=>wp_create_nonce('karsabt_document_download_'.$order->ID.'_'.$doc['id'])), admin_url('admin-post.php'))) . '">دریافت</a></li>';
            }
            $html .= '</ul>';
        } else { $html .= '<p>هنوز مدرکی ثبت نشده است.</p>'; }
        $html .= '<form method="post" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-post.php')) . '">';
        $html .= '<input type="hidden" name="action" value="karsabt_document_upload"><input type="hidden" name="order_id" value="' . esc_attr($order->ID) . '">';
        $html .= '<p><label>عنوان مدرک <select name="checklist_key"><option value="">مدرک متفرقه</option>';
        foreach ($items as $item) { $html .= '<option value="' . esc_attr($item['key']) . '">' . esc_html($item['title']) . '</option>'; }
        $html .= '</select></label></p>';
        $html .= wp_nonce_field('karsabt_document_upload_'.$order->ID, 'karsabt_docs_nonce', true, false);
        $html .= '<p><label>دسته مدرک <select name="category" required>';
        foreach (karsabt_docs_categories() as $key=>$label) { $html .= '<option value="'.esc_attr($key).'">'.esc_html($label).'</option>'; }
        $html .= '</select></label></p><p><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></p><p><button type="submit">بارگذاری مدرک (حداکثر ۵ مگابایت)</button></p></form></div>';
    }
    return $html . '</section>';
}
add_shortcode('karsabt_customer_documents', 'karsabt_docs_customer_portal');

function karsabt_docs_upload_handler() {
    if (!is_user_logged_in()) { wp_die('برای این عملیات باید وارد حساب کاربری شوید.', '', array('response'=>403)); }
    $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
    if (!$order_id || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['karsabt_docs_nonce'] ?? '')), 'karsabt_document_upload_'.$order_id)) {
        wp_die('درخواست معتبر نیست.', '', array('response'=>403));
    }
    $order = get_post($order_id);
    if (!$order || 'karsabt_order' !== $order->post_type || 'private' !== $order->post_status ||
        (int) get_post_meta($order_id, '_karsabt_customer_user_id', true) !== get_current_user_id()) {
        wp_die('دسترسی به این درخواست مجاز نیست.', '', array('response'=>403));
    }
    $rate_key = 'karsabt_docs_up_' . get_current_user_id();
    $uploads = (int) get_transient($rate_key);
    if ($uploads >= 10) { wp_die('سقف بارگذاری ساعتی تکمیل شده است.', '', array('response'=>429)); }
    $existing_docs = get_post_meta($order_id, '_karsabt_documents', true);
    if (is_array($existing_docs) && count($existing_docs) >= 20) { wp_die('برای این درخواست حداکثر ۲۰ مدرک مجاز است.', '', array('response'=>400)); }
    $dir = karsabt_docs_private_dir();
    if (!$dir || empty($_FILES['document']) || !isset($_FILES['document']['tmp_name'])) { wp_die('ذخیره‌سازی امن آماده نیست یا فایل ارسال نشده است.', '', array('response'=>400)); }
    $file = $_FILES['document'];
    if (!empty($file['error']) || (int)$file['size'] < 1 || (int)$file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
        wp_die('فایل نامعتبر است یا از محدودیت ۵ مگابایت بیشتر است.', '', array('response'=>400));
    }
    if (!class_exists('finfo')) { wp_die('بررسی نوع واقعی فایل روی سرور فعال نیست.', '', array('response'=>500)); }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = array('application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png');
    if (!isset($allowed[$mime])) { wp_die('فقط فایل PDF، JPG و PNG پذیرفته می‌شود.', '', array('response'=>400)); }
    $category = sanitize_key(wp_unslash($_POST['category'] ?? ''));
    if (!isset(karsabt_docs_categories()[$category])) { wp_die('دسته‌بندی معتبر نیست.', '', array('response'=>400)); }
    $checklist_key = sanitize_text_field(wp_unslash($_POST['checklist_key'] ?? ''));
    $service = get_post_meta($order_id, '_karsabt_service', true);
    $valid_item = null;
    foreach ((karsabt_docs_checklists()[$service] ?? array()) as $item) {
        if ($item['key'] === $checklist_key) { $valid_item = $item; break; }
    }
    if ('' !== $checklist_key && !$valid_item) { wp_die('مدرک انتخاب‌شده معتبر نیست.', '', array('response'=>400)); }

    $id = bin2hex(random_bytes(16));
    $filename = $id . '.' . $allowed[$mime];
    $destination = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) { wp_die('ذخیره فایل انجام نشد.', '', array('response'=>500)); }
    @chmod($destination, 0600);

    $docs = get_post_meta($order_id, '_karsabt_documents', true);
    $docs = is_array($docs) ? $docs : array();
    $docs[] = array('id'=>$id,'file'=>$filename,'name'=>sanitize_file_name($file['name']),'checklist_key'=>$valid_item ? $valid_item['key'] : '', 'checklist_title'=>$valid_item ? $valid_item['title'] : '', 'mime'=>$mime,'size'=>(int)$file['size'],'category'=>$category,'status'=>'pending','admin_note'=>'','uploaded_at'=>current_time('mysql'),'user_id'=>get_current_user_id());
    if (!update_post_meta($order_id, '_karsabt_documents', $docs)) {
        if (is_file($destination)) { @unlink($destination); }
        wp_die('ثبت اطلاعات مدرک انجام نشد؛ فایل ذخیره‌شده حذف شد.', '', array('response'=>500));
    }
    set_transient($rate_key, $uploads + 1, HOUR_IN_SECONDS);
    wp_safe_redirect(wp_get_referer() ?: home_url('/')); exit;
}
add_action('admin_post_karsabt_document_upload', 'karsabt_docs_upload_handler');

function karsabt_docs_find($order_id, $document_id) {
    $docs = get_post_meta($order_id, '_karsabt_documents', true);
    if (!is_array($docs)) { return array(null, array()); }
    foreach ($docs as $index=>$doc) { if (hash_equals((string)($doc['id'] ?? ''), (string)$document_id)) { return array($doc, $docs); } }
    return array(null, $docs);
}

function karsabt_docs_download_handler() {
    $order_id = isset($_REQUEST['order_id']) ? absint($_REQUEST['order_id']) : 0;
    $document_id = isset($_REQUEST['document_id']) ? sanitize_text_field(wp_unslash($_REQUEST['document_id'])) : '';
    if (!$order_id || !preg_match('/^[a-f0-9]{32}$/', $document_id) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'] ?? '')), 'karsabt_document_download_'.$order_id.'_'.$document_id)) {
        wp_die('درخواست معتبر نیست.', '', array('response'=>403));
    }
    $is_admin = current_user_can('manage_options');
    $owner = is_user_logged_in() && (int)get_post_meta($order_id, '_karsabt_customer_user_id', true) === get_current_user_id();
    if (!$is_admin && !$owner) { wp_die('دسترسی مجاز نیست.', '', array('response'=>403)); }
    list($doc,) = karsabt_docs_find($order_id, $document_id);
    $dir = karsabt_docs_private_dir();
    if (!$doc || !$dir || !preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $doc['file'] ?? '')) { wp_die('مدرک پیدا نشد.', '', array('response'=>404)); }
    $path = $dir . '/' . $doc['file'];
    $real_path = realpath($path);
    $real_dir = realpath($dir);
    $allowed_mimes = array('pdf'=>'application/pdf', 'jpg'=>'image/jpeg', 'png'=>'image/png');
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (false === $real_path || false === $real_dir ||
        0 !== strpos(wp_normalize_path($real_path), trailingslashit(wp_normalize_path($real_dir))) ||
        !is_file($real_path) || !is_readable($real_path) ||
        !isset($allowed_mimes[$extension]) || ($doc['mime'] ?? '') !== $allowed_mimes[$extension]) {
        wp_die('فایل موجود نیست یا معتبر نیست.', '', array('response'=>404));
    }
    $path = $real_path;
    nocache_headers();
    header('X-Content-Type-Options: nosniff');
    header('Content-Security-Policy: default-src none; sandbox');
    header('Content-Type: ' . $allowed_mimes[$extension]);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="document.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
    readfile($path); exit;
}
add_action('admin_post_karsabt_document_download', 'karsabt_docs_download_handler');
add_action('admin_post_nopriv_karsabt_document_download', 'karsabt_docs_download_handler');

function karsabt_docs_admin_page() {
    if (!current_user_can('manage_options')) { wp_die('دسترسی مجاز نیست.'); }
    $notice = '';
    if (isset($_POST['karsabt_doc_review'])) {
        check_admin_referer('karsabt_doc_review');
        $order_id = absint($_POST['order_id'] ?? 0);
        $document_id = sanitize_text_field(wp_unslash($_POST['document_id'] ?? ''));
        $status = sanitize_key(wp_unslash($_POST['status'] ?? ''));
        $note = sanitize_textarea_field(wp_unslash($_POST['admin_note'] ?? ''));
        list($doc, $docs) = karsabt_docs_find($order_id, $document_id);
        if ($doc && isset(karsabt_docs_review_states()[$status])) {
            $doc['status'] = $status; $doc['admin_note'] = $note;
            foreach ($docs as $i=>$item) { if (($item['id'] ?? '') === $document_id) { $docs[$i] = $doc; break; } }
            update_post_meta($order_id, '_karsabt_documents', $docs);
            $notice = '<div class="notice notice-success"><p>وضعیت بررسی ذخیره شد.</p></div>';
        } else { $notice = '<div class="notice notice-error"><p>مدرک یا وضعیت معتبر نیست.</p></div>'; }
    }
    $orders = get_posts(array('post_type'=>'karsabt_order','post_status'=>'private','posts_per_page'=>200,'meta_key'=>'_karsabt_documents','orderby'=>'date','order'=>'DESC'));
    echo '<div class="wrap"><h1>مدیریت مدارک مشتریان</h1>'.$notice;
    if (!karsabt_docs_private_dir()) { echo '<div class="notice notice-error"><p>مسیر خصوصی خارج از web root در wp-config.php تنظیم نشده است؛ دریافت فایل غیرفعال است.</p></div>'; }
    foreach ($orders as $order) {
        $docs = get_post_meta($order->ID, '_karsabt_documents', true);
        if (!is_array($docs)) { continue; }
        foreach ($docs as $doc) {
            $name = get_post_meta($order->ID, '_karsabt_customer_name', true);
            $url = add_query_arg(array('action'=>'karsabt_document_download','order_id'=>$order->ID,'document_id'=>$doc['id'],'_wpnonce'=>wp_create_nonce('karsabt_document_download_'.$order->ID.'_'.$doc['id'])), admin_url('admin-post.php'));
            echo '<div class="card" style="max-width:900px;padding:12px;margin:12px 0"><p><strong>'.esc_html($name).'</strong> — '.esc_html($doc['name']).' — '.esc_html(karsabt_docs_categories()[$doc['category']] ?? 'سایر').'</p>';
            echo '<p><a href="'.esc_url($url).'">دریافت امن فایل</a> | '.esc_html($doc['uploaded_at']).'</p><form method="post">';
            wp_nonce_field('karsabt_doc_review');
            echo '<input type="hidden" name="order_id" value="'.esc_attr($order->ID).'"><input type="hidden" name="document_id" value="'.esc_attr($doc['id']).'"><select name="status">';
            foreach (karsabt_docs_review_states() as $key=>$label) { echo '<option value="'.esc_attr($key).'" '.selected($doc['status'],$key,false).'>'.esc_html($label).'</option>'; }
            echo '</select> <input type="text" name="admin_note" maxlength="500" value="'.esc_attr($doc['admin_note']).'" placeholder="توضیح برای مشتری"> <button class="button button-primary" name="karsabt_doc_review" value="1">ذخیره بررسی</button></form></div>';
        }
    }
    echo '</div>';
}
function karsabt_docs_admin_menu() {
    add_submenu_page('edit.php?post_type=karsabt_order','مدارک مشتریان','مدارک مشتریان','manage_options','karsabt-documents','karsabt_docs_admin_page');
    add_submenu_page('edit.php?post_type=karsabt_order','چک‌لیست مدارک','چک‌لیست مدارک','manage_options','karsabt-document-checklists','karsabt_docs_checklist_admin_page');
}
add_action('admin_menu','karsabt_docs_admin_menu');
