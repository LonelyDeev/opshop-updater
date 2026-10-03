# 📦 بسته به‌روزرسانی Task 20 — پیامک کامل + رفع درگاه سامان + آپلود

این بسته شامل **همه فایل‌های تغییر یافته/جدید** از چهار درخواست شماست:

---

## ✨ ۱) رفع ارور آپلود تصویر (`Could not move the file ... livewire-tmp`)

**علت:** روی هاست شما پوشه `public/uploads/packages/thumbnails` وجود ندارد یا برای PHP قابل نوشتن نیست و `mkdir` بی‌صدا شکست می‌خورد.

**راه‌حل (خودترمیم‌شده):**
- `app/Services/ImageUploadService.php` — ساخت مطمئن پوشه + chmod خودکار + **مسیر جایگزین**: اگر `public` قابل نوشتن نبود، فایل در `storage/app/public/uploads/...` ذخیره و از طریق روت `uploads/{path}` سرو می‌شود (فایل‌های موجود در public مستقیم سرو می‌شوند).
- `app/Http/Controllers/Front/UploadsServeController.php` + مسیر `routes/web.php` — سرو فایل‌های مسیر جایگزین.
- `public/.user.ini` — **رفع ارور `The versionFile failed to upload`**: روی هاست‌های CGI محدودیت آپلود را تا ۳۲۰MB بالا می‌برد.

## ✨ ۲) توضیح کوتاه (short_description): متنی و ۱۰۰۰+ کلمه

- `database/migrations/2026_10_03_000001_...` — ستون `short_description` از `varchar(255)` به `TEXT`.
- فرم پکیج‌ها (ایجاد + ویرایش): فیلد **textarea چندخطی** با ظرفیت ۱۰,۰۰۰ کاراکتر (کادر «توضیح کوتاه»).
- نمایش با line-break واقعی (`whitespace-pre-line`) در فروشگاه و صفحه جزئیات.

## ✨ ۳) سیستم پیامک کامل (۵ درایور ایرانی)

- **درایورها:** کاوه‌نگار، ملی‌پیامک (REST بدون SOAP)، آی‌پی‌پنل، فراز اس‌ام‌اس، ایده پردازان.
- **پیامک‌های خودکار:** خرید پکیج، پرداخت اشتراک، فعال‌شدن اشتراک (تأیید مدیر)، تمدید دسترسی، روبه‌انقضای اشتراک، انقضای اشتراک + اطلاع‌رسانی اختیاری به مدیر.
- **تنظیمات پیامک:** پنل → تنظیمات → بخش «پیامک» (فعال‌سازی، انتخاب درایور، اطلاعات پنل پیامکی، شماره مدیر، روزهای هشدار انقضا).
- **قالب‌ها/پترن‌ها:** پنل → اطلاع‌رسانی → «قالب‌های پیامک» — کارت‌های قابل toggle؛ متن با متغیرهای `{customer_name}` و **کد پترن هر درایور** داخل هر قالب؛ دکمه ارسال آزمایشی؛ لاگ کامل ارسال در «لاگ پیامک‌ها».

## ✨ ۴) رفع خطای درگاه سامان (SOAP-ERROR: Parsing WSDL)

- **درایور سامان (کلاسیک)** حالا WSDL را با cURL (TLS 1.2) دانلود و در `storage/app/wsdl-cache` کش می‌کند → SoapClient با فایل لوکل ساخته می‌شود → دیگر خطای «Couldn't load from …WSDL» نمی‌گیرید. (اولین پرداخت چند ثانیه بیشتر طول می‌کشد؛ بعدی‌ها از کش.)
- **درایور جدید «سامان SEP (REST)»** — کاملاً بدون SOAP؛ اگر سرورتان اصلاً SOAP ندارد از همین استفاده کنید (همان TerminalID پذیرندگی سامان).

---

## 🚀 نصب روی هاست

```bash
# ۱) فایل‌های این بسته را روی پروژه کپی کنید (ساختار مسیرها رعایت شده)

# ۲) مایگریشن‌ها (جداول پیامک + تغییر ستون توضیح کوتاه)
php artisan migrate --force

# ۳) کش‌ها را نو کنید
php artisan config:clear && php artisan route:clear && php artisan view:clear

# ۴) (اگر PHP به صورت CGI/FastCGI است) public/.user.ini خودش اعمال می‌شود؛
#    در غیر این صورت در php.ini هاست: upload_max_filesize=320M, post_max_size=330M, allow_url_fopen=On

# ۵) کران‌جاب روزانه برای پیامک روبه‌انقضا/منقضی (cPanel → Cron):
#    0 9 * * * cd /home/USER/repositories/update-shop-pro && php artisan schedule:run
```

## 📋 فهرست فایل‌های تغییر یافته/جدید

**جدید:**
```
app/Console/Commands/CheckSubscriptionSms.php
app/Http/Controllers/Front/UploadsServeController.php
app/Livewire/Sms/Templates.php
app/Livewire/Sms/Logs.php
app/Models/SmsTemplate.php
app/Models/SmsLog.php
app/Services/Sms/Contracts/SmsDriver.php
app/Services/Sms/Drivers/KavenegarDriver.php
app/Services/Sms/Drivers/MelipayamakDriver.php
app/Services/Sms/Drivers/IppanelDriver.php
app/Services/Sms/Drivers/FarazSmsDriver.php
app/Services/Sms/Drivers/IdehPardazanDriver.php
app/Services/Sms/SmsManager.php
app/Services/Payments/WsdlCache.php
app/Services/Payments/SamanCached.php
app/Services/Payments/SepRest.php
database/migrations/2026_10_03_000001_change_packages_short_description_to_text.php
database/migrations/2026_10_03_000002_create_sms_tables.php
resources/views/livewire/sms/templates.blade.php
resources/views/livewire/sms/logs.blade.php
public/.user.ini
```

**تغییر یافته:**
```
app/Services/ImageUploadService.php
app/Services/SubscriptionService.php
app/Livewire/Packages/Index.php
app/Livewire/Packages/Show.php
app/Livewire/Settings/Index.php
app/Livewire/Gateways.php
app/Http/Controllers/Front/WebPaymentController.php
app/Http/Controllers/Api/ApiPaymentCallbackController.php
app/View/Components/Icon.php
bootstrap/helpers.php
routes/web.php
routes/console.php
config/payment.php
config/general.php
resources/views/livewire/packages/index.blade.php
resources/views/livewire/packages/show.blade.php
resources/views/livewire/shop/package-show.blade.php
resources/views/livewire/settings/index.blade.php
resources/views/components/layouts/app.blade.php
AI-CONTEXT.md
```

> **تست محلی:** با درگاه آزمایشی local همه‌ی ۶ سناریوی پیامک + آپلود پوشش داده و سبز است.
