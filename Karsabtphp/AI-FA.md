# اتصال هوش مصنوعی کارثبت

پنل `/admin/ai-settings.php` از API سازگار با OpenAI Chat Completions پشتیبانی می‌کند. Endpoint باید HTTPS باشد و به شکل کامل شامل مسیر `chat/completions` باشد. مدل و API Key در جدول settings ذخیره می‌شوند. برای تولید پیش‌نویس، cURL و دسترسی اینترنت خروجی سرور لازم است. هیچ مقاله‌ای خودکار منتشر نمی‌شود. قبل از استفاده واقعی، مسیر دیتابیس را خارج از document root قرار دهید و دسترسی پنل را محدود کنید.

نمونه Endpoint (بسته به ارائه‌دهنده): `https://api.example/v1/chat/completions`
