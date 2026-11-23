<?php
http_response_code(404);
require __DIR__ . '/includes/bootstrap.php';
$contact = contact_links();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,follow">
<title>صفحه پیدا نشد | کارثبت</title>
<style>
:root{font-family:Tahoma,Arial,sans-serif;color:#132d45;background:#f5f8fa}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px}.box{max-width:560px;width:100%;background:white;border:1px solid #e5edf1;border-radius:24px;padding:36px;text-align:center;box-shadow:0 12px 40px #1232}.num{font-size:clamp(64px,15vw,112px);font-weight:900;color:#39b7c9;line-height:1}h1{margin:12px 0;font-size:24px}p{line-height:2;color:#526577}.actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:center;margin-top:24px}a{padding:12px 18px;border-radius:12px;background:#123149;color:#fff;text-decoration:none;font-weight:700}a.secondary{background:#e9f6f8;color:#123149}
</style>
</head>
<body>
<main class="box">
<div class="num">404</div>
<h1>صفحه موردنظر پیدا نشد</h1>
<p>ممکن است نشانی تغییر کرده باشد یا صفحه حذف شده باشد. می‌توانید به صفحه اصلی برگردید یا مستقیماً با ما ارتباط بگیرید.</p>
<div class="actions"><a href="/">بازگشت به صفحه اصلی</a><a class="secondary" href="<?=e($contact['tel'])?>">تماس با کارثبت</a></div>
</main>
</body>
</html>
