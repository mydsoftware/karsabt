<?php
/**
 * آزمون HTTP واقعی مسیرهای پیام، پرداخت و مدارک کارثبت.
 * این فایل باید پس از نصب وردپرس و اجرای PHP built-in server اجرا شود.
 */
declare(strict_types=1);

$wpPath = getenv('KARSABT_WP_PATH');
$baseUrl = rtrim((string) getenv('KARSABT_HTTP_BASE_URL'), '/');
if (!$wpPath || !is_file($wpPath . '/wp-load.php') || !$baseUrl || !function_exists('curl_init')) {
    fwrite(STDERR, "محیط آزمون HTTP کامل نیست.\n");
    exit(1);
}
require $wpPath . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$checks = 0;
$failures = array();
$assert = static function ($condition, string $label) use (&$checks, &$failures): void {
    $checks++;
    if (!$condition) {
        $failures[] = $label;
        fwrite(STDERR, "[ناموفق] {$label}\n");
    } else {
        fwrite(STDOUT, "[موفق] {$label}\n");
    }
};
$request = static function (string $url, string $jar, string $method = 'GET', array $data = array(), array $files = array()) {
    $ch = curl_init($url);
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => $method,
    );
    if ('POST' === $method) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $data;
    }
    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    if (false === $raw) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('HTTP request failed: ' . $error);
    }
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return array('status' => $status, 'headers' => substr($raw, 0, $headerSize), 'body' => substr($raw, $headerSize));
};
$parallelPost = static function (string $url, string $jar, array $payloads): array {
    $multi = curl_multi_init();
    $handles = array();
    foreach ($payloads as $index => $payload) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE => $jar,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
        ));
        curl_multi_add_handle($multi, $ch);
        $handles[$index] = $ch;
    }
    do {
        $status = curl_multi_exec($multi, $active);
        if ($active) { curl_multi_select($multi, 1.0); }
    } while ($active && CURLM_OK === $status);
    $results = array();
    foreach ($handles as $index => $ch) {
        $raw = curl_multi_getcontent($ch);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $results[$index] = array(
            'status' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
            'body' => false === $raw ? '' : substr($raw, $headerSize),
        );
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
    }
    curl_multi_close($multi);
    return $results;
};
$login = static function (string $username, string $password, string $jar) use ($baseUrl, $request): bool {
    $loginUrl = $baseUrl . '/wp-login.php';
    $request($loginUrl, $jar);
    $response = $request($loginUrl, $jar, 'POST', array(
        'log' => $username, 'pwd' => $password, 'wp-submit' => 'ورود',
        'redirect_to' => $baseUrl . '/wp-admin/', 'testcookie' => '1',
    ));
    return in_array($response['status'], array(302, 303), true);
};
$extract = static function (string $html, string $name): string {
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    foreach ($dom->getElementsByTagName('input') as $input) {
        if ($name === $input->getAttribute('name')) {
            return $input->getAttribute('value');
        }
    }
    return '';
};
$findDownloadUrl = static function (string $html): string {
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    foreach ($dom->getElementsByTagName('a') as $link) {
        $href = html_entity_decode($link->getAttribute('href'), ENT_QUOTES, 'UTF-8');
        if (false !== strpos($href, 'action=karsabt_document_download')) {
            return $href;
        }
    }
    return '';
};
$nonce = static function (string $html, string $name) use ($extract): string { return $extract($html, $name); };

$stamp = bin2hex(random_bytes(4));
$adminPass = 'Karsabt-http-admin-' . bin2hex(random_bytes(8));
$customerPass = 'Karsabt-http-customer-' . bin2hex(random_bytes(8));
$otherPass = 'Karsabt-http-other-' . bin2hex(random_bytes(8));
$adminRole = 'karsabt_http_test_admin';
if (!get_role($adminRole)) {
    add_role($adminRole, 'مدیر آزمایشی HTTP', array('read'=>true,'manage_options'=>true,'edit_karsabt_orders'=>true));
}
$adminId = wp_create_user('karsabt-http-admin-' . $stamp, $adminPass, 'http-admin-' . $stamp . '@example.test');
$customerId = wp_create_user('karsabt-http-customer-' . $stamp, $customerPass, 'http-customer-' . $stamp . '@example.test');
$otherId = wp_create_user('karsabt-http-other-' . $stamp, $otherPass, 'http-other-' . $stamp . '@example.test');
if (is_wp_error($adminId) || is_wp_error($customerId) || is_wp_error($otherId)) {
    throw new RuntimeException('ساخت کاربران آزمون ناموفق بود.');
}
(new WP_User($adminId))->set_role($adminRole);
(new WP_User($customerId))->set_role('subscriber');
(new WP_User($otherId))->set_role('subscriber');
if (!user_can($adminId, 'manage_options') || !user_can($adminId, 'edit_karsabt_orders')) {
    throw new RuntimeException('نقش مدیر آزمایشی مجوزهای لازم را دریافت نکرد.');
}

$orderId = wp_insert_post(array('post_type'=>'karsabt_order','post_status'=>'private','post_title'=>'HTTP test '.$stamp), true);
if (is_wp_error($orderId)) { throw new RuntimeException('ساخت پرونده آزمون ناموفق بود.'); }
update_post_meta($orderId, '_karsabt_customer_user_id', $customerId);
update_post_meta($orderId, '_karsabt_customer_name', 'مشتری آزمون HTTP');
update_post_meta($orderId, '_karsabt_customer_phone', '09120000000');
update_post_meta($orderId, '_karsabt_service', 'company');
update_post_meta($orderId, '_karsabt_tracking_code', 'KRS-2611-000001-' . strtoupper(substr($stamp, 0, 4)));

$customerPageId = wp_insert_post(array(
    'post_type'=>'page','post_status'=>'publish','post_title'=>'Karsabt HTTP Customer '.$stamp,
    'post_content'=>'[karsabt_customer_messages]\n[karsabt_customer_documents]',
), true);
if (is_wp_error($customerPageId)) { throw new RuntimeException('ساخت صفحه مشتری ناموفق بود.'); }
$customerUrl = get_permalink($customerPageId);

$invoiceId = wp_insert_post(array('post_type'=>'karsabt_invoice','post_status'=>'private','post_title'=>'HTTP invoice '.$stamp), true);
if (is_wp_error($invoiceId)) { throw new RuntimeException('ساخت فاکتور آزمون ناموفق بود.'); }
update_post_meta($invoiceId, '_karsabt_invoice_amount', 100);
update_post_meta($invoiceId, '_karsabt_invoice_number', 'HTTP-' . $stamp);
update_post_meta($invoiceId, '_karsabt_invoice_customer_user_id', $customerId);
update_post_meta($invoiceId, '_karsabt_invoice_customer_name', 'مشتری آزمون HTTP');
update_post_meta($invoiceId, '_karsabt_invoice_description', 'فاکتور آزمون HTTP');

$privateDir = function_exists('karsabt_docs_private_dir') ? karsabt_docs_private_dir() : '';
$assert(is_string($privateDir) && '' !== $privateDir, 'مسیر ذخیره‌سازی خصوصی خارج از web root فعال است');

$root = sys_get_temp_dir() . '/karsabt-http-' . $stamp;
if (!wp_mkdir_p($root)) { throw new RuntimeException('ساخت پوشه آزمون ناموفق بود.'); }
$adminJar = $root . '/admin.cookies';
$customerJar = $root . '/customer.cookies';
$otherJar = $root . '/other.cookies';
$guestJar = $root . '/guest.cookies';
$doc = array();

try {
    $assert($login('karsabt-http-admin-' . $stamp, $adminPass, $adminJar), 'ورود مدیر آزمایشی از مسیر HTTP وردپرس موفق است');
    $assert($login('karsabt-http-customer-' . $stamp, $customerPass, $customerJar), 'ورود مشتری از مسیر HTTP وردپرس موفق است');
    $assert($login('karsabt-http-other-' . $stamp, $otherPass, $otherJar), 'ورود مشتری دوم از مسیر HTTP وردپرس موفق است');

    $customerPage = $request($customerUrl, $customerJar);
    $messageNonce = $nonce($customerPage['body'], 'karsabt_message_nonce');
    $docsNonce = $nonce($customerPage['body'], 'karsabt_docs_nonce');
    $assert(200 === $customerPage['status'] && '' !== $messageNonce, 'صفحه مشتری nonce واقعی ارسال پیام را ارائه می‌کند');
    $assert('' !== $docsNonce, 'صفحه مشتری nonce واقعی بارگذاری مدرک را ارائه می‌کند');

    $messageResponse = $request($baseUrl . '/wp-admin/admin-post.php', $customerJar, 'POST', array(
        'action'=>'karsabt_customer_message','order_id'=>$orderId,
        'karsabt_message_nonce'=>$messageNonce,'message'=>'پیام آزمایشی از درخواست HTTP',
    ));
    $messages = karsabt_messages_get($orderId);
    $assert(in_array($messageResponse['status'], array(302,303), true), 'ارسال پیام مشتری از admin-post.php پاسخ هدایت معتبر دارد');
    $assert(1 === count($messages) && 'پیام آزمایشی از درخواست HTTP' === $messages[0]['text'], 'پیام مشتری از مسیر HTTP در پرونده ذخیره شد');
    $parallelMessages = $parallelPost($baseUrl . '/wp-admin/admin-post.php', $customerJar, array(
        array('action'=>'karsabt_customer_message','order_id'=>$orderId,'karsabt_message_nonce'=>$messageNonce,'message'=>'پیام هم‌زمان اول'),
        array('action'=>'karsabt_customer_message','order_id'=>$orderId,'karsabt_message_nonce'=>$messageNonce,'message'=>'پیام هم‌زمان دوم'),
        array('action'=>'karsabt_customer_message','order_id'=>$orderId,'karsabt_message_nonce'=>$messageNonce,'message'=>'پیام هم‌زمان سوم'),
    ));
    wp_cache_delete($orderId, 'post_meta');
    $messages = karsabt_messages_get($orderId);
    $parallelMessageSuccess = count(array_filter($parallelMessages, static function ($item) { return in_array($item['status'], array(302,303), true); }));
    $parallelTexts = array_column($messages, 'text');
    $assert(3 === $parallelMessageSuccess, 'سه درخواست واقعی ارسال پیام به‌صورت هم‌زمان پاسخ موفق گرفتند');
    if (4 !== count($messages) || !in_array('پیام هم‌زمان اول', $parallelTexts, true) || !in_array('پیام هم‌زمان دوم', $parallelTexts, true) || !in_array('پیام هم‌زمان سوم', $parallelTexts, true)) {
        fwrite(STDERR, '[جزئیات پیام‌های ذخیره‌شده] ' . wp_json_encode($parallelTexts, JSON_UNESCAPED_UNICODE) . "\\n");
    }
    $assert(4 === count($messages) && in_array('پیام هم‌زمان اول', $parallelTexts, true) && in_array('پیام هم‌زمان دوم', $parallelTexts, true) && in_array('پیام هم‌زمان سوم', $parallelTexts, true), 'قفل گفت‌وگو مانع حذف یا بازنویسی پیام‌های هم‌زمان شد');

    $adminMessagesUrl = $baseUrl . '/wp-admin/edit.php?post_type=karsabt_order&page=karsabt-messages';
    $adminMessagesPage = $request($adminMessagesUrl, $adminJar);
    $adminMessageNonce = $nonce($adminMessagesPage['body'], '_wpnonce');
    if (200 !== $adminMessagesPage['status'] || '' === $adminMessageNonce) {
        fwrite(STDERR, "[جزئیات پیام مدیر] HTTP=" . $adminMessagesPage['status'] . " nonce=" . ('' !== $adminMessageNonce ? 'yes' : 'no') . " body=" . substr(strip_tags($adminMessagesPage['body']), 0, 300) . "\\n");
    }
    $assert(200 === $adminMessagesPage['status'] && '' !== $adminMessageNonce, 'مدیر صفحه گفت‌وگو و nonce پاسخ را دریافت می‌کند');
    $adminReply = $request($adminMessagesUrl, $adminJar, 'POST', array(
        '_wpnonce'=>$adminMessageNonce,'order_id'=>$orderId,
        'message'=>'پاسخ مدیر از مسیر HTTP','karsabt_admin_message_submit'=>'1',
    ));
    wp_cache_delete($orderId, 'post_meta');
    $messages = karsabt_messages_get($orderId);
    $assert(200 === $adminReply['status'] && false !== strpos($adminReply['body'], 'پاسخ ثبت شد'), 'پاسخ مدیر از فرم واقعی مدیریت ثبت شد');
    $assert(5 === count($messages) && 'admin' === $messages[4]['sender'], 'پاسخ مدیر در گفت‌وگوی پرونده ذخیره شد');

    update_post_meta($orderId, '_karsabt_customer_user_id', $otherId);
    $otherPage = $request($customerUrl, $otherJar);
    $otherNonce = $nonce($otherPage['body'], 'karsabt_message_nonce');
    update_post_meta($orderId, '_karsabt_customer_user_id', $customerId);
    $deniedMessage = $request($baseUrl . '/wp-admin/admin-post.php', $otherJar, 'POST', array(
        'action'=>'karsabt_customer_message','order_id'=>$orderId,
        'karsabt_message_nonce'=>$otherNonce,'message'=>'پیام غیرمجاز',
    ));
    $assert('' !== $otherNonce && 403 === $deniedMessage['status'], 'مشتری دیگر با nonce معتبر خودش برای پرونده غیرمالک پاسخ ۴۰۳ می‌گیرد');
    $guestMessage = $request($baseUrl . '/wp-admin/admin-post.php', $guestJar, 'POST', array(
        'action'=>'karsabt_customer_message','order_id'=>$orderId,
        'karsabt_message_nonce'=>$messageNonce,'message'=>'پیام مهمان',
    ));
    $assert(in_array($guestMessage['status'], array(400,403), true), 'مهمان از مسیر HTTP اجازه ارسال پیام دریافت نمی‌کند');

    $paymentUrl = $baseUrl . '/wp-admin/admin.php?page=karsabt-payments';
    $paymentPage = $request($paymentUrl, $adminJar);
    $paymentNonce = $nonce($paymentPage['body'], '_wpnonce');
    if (200 !== $paymentPage['status'] || '' === $paymentNonce) {
        fwrite(STDERR, "[جزئیات پرداخت] HTTP=" . $paymentPage['status'] . " nonce=" . ('' !== $paymentNonce ? 'yes' : 'no') . " body=" . substr(strip_tags($paymentPage['body']), 0, 300) . "\\n");
    }
    $assert(200 === $paymentPage['status'] && '' !== $paymentNonce, 'مدیر صفحه ثبت پرداخت و nonce واقعی را دریافت می‌کند');
    $paymentResponse = $request($paymentUrl, $adminJar, 'POST', array(
        '_wpnonce'=>$paymentNonce,'invoice_id'=>$invoiceId,'amount'=>'60',
        'method'=>'cash','reference'=>'HTTP-'.$stamp,'note'=>'پرداخت آزمایشی',
        'karsabt_record_payment'=>'1',
    ));
    $paid = karsabt_finance_invoice_paid($invoiceId);
    $assert(200 === $paymentResponse['status'] && false !== strpos($paymentResponse['body'], 'پرداخت ثبت شد'), 'ثبت پرداخت از فرم واقعی امور مالی موفق است');
    $assert(60 === $paid, 'مبلغ پرداخت ثبت‌شده در دیتابیس دقیقاً ۶۰ است');
    $overpayment = $request($paymentUrl, $adminJar, 'POST', array(
        '_wpnonce'=>$paymentNonce,'invoice_id'=>$invoiceId,'amount'=>'50',
        'method'=>'cash','reference'=>'HTTP-OVER-'.$stamp,'note'=>'پرداخت بیش از مانده',
        'karsabt_record_payment'=>'1',
    ));
    $assert(false !== strpos($overpayment['body'], 'مبلغ از مانده فاکتور بیشتر است') && 60 === karsabt_finance_invoice_paid($invoiceId), 'پرداخت بیش از مانده از مسیر HTTP رد می‌شود');
    $parallelPayments = $parallelPost($paymentUrl, $adminJar, array(
        array('_wpnonce'=>$paymentNonce,'invoice_id'=>$invoiceId,'amount'=>'30','method'=>'cash','reference'=>'HTTP-PAR-1-'.$stamp,'note'=>'پرداخت هم‌زمان اول','karsabt_record_payment'=>'1'),
        array('_wpnonce'=>$paymentNonce,'invoice_id'=>$invoiceId,'amount'=>'30','method'=>'cash','reference'=>'HTTP-PAR-2-'.$stamp,'note'=>'پرداخت هم‌زمان دوم','karsabt_record_payment'=>'1'),
    ));
    $acceptedPayments = count(array_filter($parallelPayments, static function ($item) { return false !== strpos($item['body'], 'پرداخت ثبت شد'); }));
    $assert(1 === $acceptedPayments && 90 === karsabt_finance_invoice_paid($invoiceId), 'دو پرداخت HTTP هم‌زمان ۳۰ واحدی روی مانده ۴۰، فقط یک بار ثبت شدند');

    $pdfPath = $root . '/sample.pdf';
    file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF");
    $parallelUploads = $parallelPost($baseUrl . '/wp-admin/admin-post.php', $customerJar, array(
        array('action'=>'karsabt_document_upload','order_id'=>$orderId,'karsabt_docs_nonce'=>$docsNonce,'category'=>'identity','checklist_key'=>'','document'=>new CURLFile($pdfPath, 'application/pdf', 'http-test-a.pdf')),
        array('action'=>'karsabt_document_upload','order_id'=>$orderId,'karsabt_docs_nonce'=>$docsNonce,'category'=>'identity','checklist_key'=>'','document'=>new CURLFile($pdfPath, 'application/pdf', 'http-test-b.pdf')),
    ));
    wp_cache_delete($orderId, 'post_meta');
    $docs = get_post_meta($orderId, '_karsabt_documents', true);
    $docs = is_array($docs) ? $docs : array();
    $doc = $docs ? end($docs) : array();
    $uploadSuccess = count(array_filter($parallelUploads, static function ($item) { return in_array($item['status'], array(302,303), true); }));
    $assert(2 === $uploadSuccess, 'دو بارگذاری واقعی HTTP به‌صورت هم‌زمان پاسخ موفق گرفتند');
    $assert(2 === count($docs) && !empty($doc['id']) && is_file($privateDir . '/' . ($doc['file'] ?? '')), 'هر دو فایل و فراداده مستقل در مسیر خصوصی ثبت شدند');

    if (!empty($doc['id'])) {
        $ownerPage = $request($customerUrl, $customerJar);
        $downloadUrl = $findDownloadUrl($ownerPage['body']);
        $assert('' !== $downloadUrl, 'صفحه مشتری لینک دانلود با nonce معتبر تولید می‌کند');
        $download = $downloadUrl ? $request($downloadUrl, $customerJar) : array('status'=>0,'body'=>'');
        $assert(200 === $download['status'] && 0 === strpos($download['body'], '%PDF-1.4'), 'مالک پرونده فایل خصوصی را از مسیر دانلود واقعی دریافت می‌کند');

        update_post_meta($orderId, '_karsabt_customer_user_id', $otherId);
        $otherDocsPage = $request($customerUrl, $otherJar);
        $otherDownloadUrl = $findDownloadUrl($otherDocsPage['body']);
        update_post_meta($orderId, '_karsabt_customer_user_id', $customerId);
        $wrongOwner = $otherDownloadUrl ? $request($otherDownloadUrl, $otherJar) : array('status'=>0);
        $assert('' !== $otherDownloadUrl && 403 === $wrongOwner['status'], 'مشتری دیگر با nonce معتبر خودش اجازه دریافت مدرک پرونده غیرمالک را ندارد');
        $guestDownload = $downloadUrl ? $request($downloadUrl, $guestJar) : array('status'=>0);
        $assert(in_array($guestDownload['status'], array(400,403), true), 'مهمان از مسیر HTTP اجازه دریافت مدرک دریافت نمی‌کند');
    } else {
        $assert(false, 'شناسه مدرک برای آزمون دانلود تولید شد');
    }

    $badPath = $root . '/not-allowed.txt';
    file_put_contents($badPath, 'این فایل PDF یا تصویر نیست.');
    $invalidUpload = $request($baseUrl . '/wp-admin/admin-post.php', $customerJar, 'POST', array(
        'action'=>'karsabt_document_upload','order_id'=>$orderId,
        'karsabt_docs_nonce'=>$docsNonce,'category'=>'identity','checklist_key'=>'',
        'document'=>new CURLFile($badPath, 'text/plain', 'not-allowed.txt'),
    ));
    $assert(400 === $invalidUpload['status'], 'فایل با نوع غیرمجاز از مسیر HTTP رد می‌شود');
} finally {
    foreach ((isset($docs) && is_array($docs) ? $docs : array()) as $uploadedDoc) {
        if (!empty($uploadedDoc['file']) && $privateDir && is_file($privateDir . '/' . $uploadedDoc['file'])) {
            @unlink($privateDir . '/' . $uploadedDoc['file']);
        }
    }
    if (!empty($invoiceId)) { $wpdb->delete(karsabt_finance_table(), array('invoice_id'=>$invoiceId)); wp_delete_post($invoiceId, true); }
    if (!empty($orderId)) { wp_delete_post($orderId, true); }
    if (!empty($customerPageId)) { wp_delete_post($customerPageId, true); }
    foreach (array($adminId,$customerId,$otherId) as $userId) { if ($userId && !is_wp_error($userId)) { wp_delete_user($userId); } }
    foreach (glob($root . '/*') ?: array() as $file) { @unlink($file); }
    @rmdir($root);
    if (get_role('karsabt_http_test_admin')) { remove_role('karsabt_http_test_admin'); }
}
if ($failures) {
    fwrite(STDERR, sprintf("\n%d آزمون از %d آزمون HTTP ناموفق بود.\n", count($failures), $checks));
    exit(1);
}
fwrite(STDOUT, sprintf("\nهمه %d آزمون HTTP واقعی موفق شدند.\n", $checks));
