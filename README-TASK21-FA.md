# 📦 Task 21 — آپلود کپی‌محور + ریشه‌یابی کامل درگاه سامان + پیامک مدیر + انتخابگر زیبای درگاه

این بسته ۴ مشکل گزارش‌شده شما را ریشه‌یابی و رفع می‌کند + ۴ قابلیت جدید اضافه می‌کند.

---

## ۱) ✅ رفع قطعی ارور «Could not move the file …»

**دو ریشه داشت:**

1. **مسیر تکراری:** در نسخه قبلی، مسیر جایگزین (fallback) به‌اشتباه `uploads` را دوبار تکرار می‌کرد:
   `storage/app/public/uploads/uploads/packages/thumbnails` (دابل!). اصلاح شد → `storage/app/public/uploads/…`
2. **استراتژی move → copy:** طبق پیشنهاد خودتان، به‌جای `move()`/`rename()` (که روی بعضی هاست‌ها بین `livewire-tmp` و مقصد شکست می‌خورد) از **`copy()` + `unlink()`** استفاده می‌شود. فایل موقت livewire خودکار هم پاک می‌شود.
   - ساخت دایرکتوری بازگشتی با **آزمون واقعی نوشتن** (probe file) — `is_writable()` روی بعضی هاست‌ها گمراه‌کننده است
   - پیام خطای فارسی دقیق با علت واقعی (اگر باز هم شکست بخورد، علت دقیق در متن خطا هست)

**فایل:** `app/Services/ImageUploadService.php` (بازنویسی کامل)

> اگر باز هم خطا داد: متن جدید خطا علت دقیق را می‌گوید (پرمیژن یا مسیر). پوشه‌های `public/uploads` و `storage/app/public` باید **مالکِ همان کاربری** باشند که PHP با آن اجرا می‌شود (در cPanel معمولاً خودتان) — `chmod 755` و `chown` درست کافی است.

---

## ۲) ✅ ریشه‌یابی خطای درگاه سامان — «WSDL دانلود نشد»

### 🔴 علت واقعی (تست شد): محدودیت جغرافیایی شاپرک

شاپرک (همه درگاه‌های `*.shaparak.ir` شامل سامان/سپ/سپهر/سداد) **فقط به IPهای داخل ایران پاسخ می‌دهد**. اتصال TCP از سرورهای خارج ایران **Timeout** می‌شود — دقیقاً همان خطای شما:

```
Connection timed out after 10001 milliseconds — HTTP 0
```

این را با تست مستقیم تأیید کردیم: از یک سرور خارج ایران، `sep.shaparak.ir` (هم پورت ۴۴۳ هم ۸۰) هیچ پاسخی نمی‌دهد اما سایت‌های دیگر همان سرور درست باز می‌شوند.

> ⚠️ **نکته مهم:** درگاه شما `01a0be94-e09f-736a-b416-df430d0a90a6` فرمت **UUID** دارد — این ترمینال‌های **نسل جدید سپ** هستند و درگاه درست برایش «سامان SEP (REST جدید)» است نه «سامان کلاسیک». درایور SEP حالا هر دو را پشتیبانی می‌کند (خودکار تشخیص می‌دهد).

### راه‌حل‌ها (به ترتیب):

**راه ۱ — پروکسی/سرور واسط ایرانی (توصیه‌شده):**
1. یک پروکسی HTTP/SOCKS5 روی یک **سرور ایرانی** تهیه کنید (VPS ارزان ایرانی کافی است — با `ssh -D` یا `gost` یا `3proxy` می‌شود ساخت)
2. در پنل: **تنظیمات → تنظیمات پرداخت → «پروکسی شاپرک»** آدرسش را وارد کنید:
   `http://IP:PORT` یا `socks5://IP:PORT` یا `http://user:pass@IP:PORT`
3. دکمه **«تست اتصال به شاپرک»** را بزنید — نتیجه مرحله‌ای (DNS/TCP/HTTP) با توضیح فارسی نشان می‌دهد
4. همه درخواست‌های درگاه‌های شاپرکی (سامان کلاسیک، SEP، سپهر) به‌صورت خودکار از پروکسی عبور می‌کنند (REST + SOAP + دانلود WSDL)

**راه ۲ — انتقال پنل به هاست ایرانی** (اگر امکانش هست، سالم‌ترین راه است).

**راه ۳ — درگاه‌های غیرشاپرکی:** زرین‌پال، زیبال، pay.ir و… محدودیت جغرافیایی ندارند و از هاست خارج ایران کار می‌کنند.

### درایور جدید SEP (REST — بدون SOAP)

- **ترمینال UUID (نسل جدید):** `POST /api/v1/payment/init` → توکن → ریدایرکت به `https://sep.shaparak.ir/Payment/{token}` → تأیید با `/api/v1/payment/verify`
- **ترمینال عددی (آسان‌پرداخت):** `action=token` → `/onlinepg/onlinepg` → `OnlinePG/SendToken` → تأیید `VerifyTransaction`
- تشخیص خودکار بر اساس فرمت ترمینال (یا فیلد `mode` در تنظیمات درگاه: `v1` / `onlinepg` / خالی=خودکار)
- پارس انعطاف‌پذیر پاسخ‌ها + پیام خطای فارسی با کدهای معنی‌دار
- خطای اتصال، **دلیل شاپرک را صریح می‌گوید** (نه فقط SOAP-ERROR خام)

**فایل‌ها:** `app/Services/Payments/SepRest.php` (بازنویسی)، `SamanCached.php` و `WsdlCache.php` (پروکسی + اجبار IPv4 + پیام‌های گویا)، `config/payment.php`

---

## ۳) ✅ قالب پیامک مدیر + ارسال از همه مسیرها

### ۵ قالب جدید (مهاجرت خودکار اضافه می‌شود):

| کلید | مخاطب | موضوع |
|---|---|---|
| `admin_purchase_paid` | مدیر | پرداخت موفق خرید پکیج |
| `admin_subscription_request` | مدیر | درخواست اشتراک جدید (رایگان) — فوراً |
| `admin_subscription_paid` | مدیر | پرداخت اشتراک (در انتظار تأیید) |
| `subscription_request_ack` | مشتری | تأیید ثبت درخواست رایگان |
| `subscription_rejected` | مشتری | رد درخواست (با متن دلیل) |

در صفحه **قالب‌های پیامک** کارت‌ها با نشان «مدیر / مشتری» تفکیک شدند.

### پیامک حالا از **تمام** مسیرهای خرید ارسال می‌شود:

- ✅ خرید پکیج از **سایت** (کال‌بک وب)
- ✅ خرید پکیج از **API** (کال‌بک API **و** endpoint تأیید `POST /payments/{trx}/verify`)
- ✅ خرید اشتراک پولی از **سایت و API** (کال‌بک + تأیید)
- ✅ درخواست اشتراک **رایگان** (ثبت لحظه‌ای + اطلاع فوری مدیر)
- ✅ **رد** درخواست توسط مدیر (با دلیل به مشتری)
- همه با `once: true` — تکرار کال‌بک → بدون پیامک مجدد

**فایل‌ها:** migration جدید، `SubscriptionService.php`، `WebPaymentController.php`، `ApiPaymentCallbackController.php`، `ApiPackageController.php`، `ApiSubscriptionController.php`

---

## ۴) ✅ قابلیت‌های درگاه‌ها

- **غیرفعال‌کردن درگاه آزمایشی:** حالا در «درگاه‌های پرداخت» دیده می‌شود و با کلید فعال/غیرفعال خاموش می‌شود + هشدار زرد «درگاه تست»
- **لوگو و آیکون درگاه‌ها:** ۱۳ لوگوی پیش‌فرض تمیز (SVG) داخل `public/uploads/gateways/logos/` + آپلود **لوگوی اختصاصی** برای هر درگاه در همان صفحه (JPG/PNG/WEBP/SVG)
- **انتخابگر زیبای درگاه برای مشتری:**
  - خرید از **سایت** (طرح اشتراک + صفحه پکیج): کارت‌های رادیویی با لوگو + عنوان + تیک انتخاب (جای `<select>`)
  - خرید از **API**: بلوک `gateways` در پاسخ `GET /api/v1/packages` و `GET /api/v1/subscription-plans`:
    ```json
    "gateways": [{ "key": "sep", "title": "سامان SEP", "logo": "https://…/sep.svg", "is_test": false }]
    ```
  - پوشه `shop-sim-reference/`: پیاده‌سازی مرجع صفحه «انتخاب درگاه» (چک‌اوت) برای فروشگاه شما — با همان کارت‌های لوگودار

---

## نصب روی هاست (مهم — به ترتیب)

```bash
# ۱) فایل‌ها را کپی کنید (ساختار پوشه‌ها رعایت شده)

# ۲) مهاجرت‌ها (۲ مهاجرت جدید: قالب‌های پیامک + ستون لوگو)
php artisan migrate --force

# ۳) کش‌ها را پاک کنید
php artisan view:clear
php artisan config:clear

# ۴) در صورت استفاده از WSDL کش‌شده، کش قدیمی SAM را پاک کنید
#    (فایل‌های storage/app/wsdl-cache/saman-*)
rm -f storage/app/wsdl-cache/saman-*
```

**سپس:**
1. تنظیمات → پرداخت → پروکسی شاپرک را (اگر هاست خارج از ایران است) تنظیم + تست اتصال
2. درگاه‌های پرداخت → برای ترمینال UUID خودتان درگاه **«سامان SEP (REST جدید)»** را فعال کنید و TerminalId را وارد کنید
3. قالب‌های پیامک → قالب‌های «مدیر» را ویرایش و پترن‌ها را ثبت کنید

---

## تغییرات فایل‌ها

**بازنویسی/ویرایش (۱۴):**
`app/Services/ImageUploadService.php` · `app/Services/Payments/SepRest.php` · `app/Services/Payments/SamanCached.php` · `app/Services/Payments/WsdlCache.php` · `app/Services/SubscriptionService.php` · `app/Livewire/Settings/Index.php` · `app/Livewire/Gateways.php` · `app/Http/Controllers/Front/WebPaymentController.php` · `app/Http/Controllers/Api/ApiPaymentCallbackController.php` · `app/Http/Controllers/Api/ApiPackageController.php` · `app/Http/Controllers/Api/ApiSubscriptionController.php` · `bootstrap/helpers.php` · `config/payment.php` · `app/View/Components/Icon.php`

**ویوها (۶):** `settings/index` · `gateways` · `sms/templates` · `shop/subscriptions-index` · `shop/package-show` · `shop/partials/gateway-picker` (جدید)

**جدید (۱۶+):** ۲ migration · ۱۳ لوگوی SVG · `generate-gateway-logos.php` · پوشه `shop-sim-reference/`

---

## تست‌های انجام‌شده (E2E — مرورگر واقعی)

- آپلود تصویر شاخص و گالری از مرورگر → ذخیره در `public/uploads/...` ✓ (بدون هیچ خطای move)
- غیرفعال/فعال‌کردن درگاه آزمایشی از تنظیمات → ماندگار در DB ✓
- تست اتصال شاپرک → تشخیص Timeout + راهنمای فارسی ✓
- خرید اشتراک پولی از سایت (درگاه آزمایشی) → پیامک مشتری + مدیر ✓
- درخواست اشتراک رایگان → پیامک تأیید مشتری + اطلاع فوری مدیر ✓
- رد درخواست با دلیل → پیامک رد به مشتری ✓
- خرید پکیج از مسیر API (shop-sim → checkout → درگاه → لایسنس) → پیامک مشتری + مدیر ✓
- انتخابگر کارت‌های درگاه (لوگو + عنوان) در سایت و فروشگاه API ✓
