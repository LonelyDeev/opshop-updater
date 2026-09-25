# AI-CONTEXT — آپدیت‌سنتر (Project Handoff Document)

> **این فایل چیست؟** سند انتقال پروژه برای هوش مصنوعی (یا توسعه‌دهنده) جدید که می‌خواهد کار روی این پروژه را ادامه دهد.
> همه‌چیز که «در جریان پروژه» بودن نیاز دارد اینجاست: معماری، قرار دادها، سرویس‌ها، جریان پرداخت، گاتچاهای مهم و روش تست.
> This document is written primarily in Persian (the project's language) with English technical terms. All UI text, validation messages and domain logic are Persian/RTL.

---

## ۱. تصویر کلی پروژه

**آپدیت‌سنتر** — پنل مدیریت «مصرف‌کننده-محور» برای فروش و توزیع پکیج‌های نرم‌افزاری لاراول به مشتریان (agency-style update center):

- **پنل مدیریت** (`/admin/*`): مدیریت پروژه‌ها، پکیج‌ها، نسخه‌ها، طرح‌های قیمت، لایسنس‌ها، خریدها، مشتریان، اشتراک‌ها، کاربران، گزارش‌ها، لاگ‌ها، تنظیمات و درگاه‌های پرداخت.
- **فروشگاه عمومی** (`/` و `/packages/{slug}`): مشتریان پکیج‌ها را می‌بینند و **با «کد آپدیت» (update_code)** بدون ثبت‌نم خرید می‌کنند؛ پکیج رایگان → لایسنس فوری؛ پکیج پرداختی → انتقال به درگاه → صفحه نتیجه.
- **API عمومی v1** (`/api/v1/*`): برای سیستم‌های لاراولیِ خودِ مشتریان — چک آپدیت، خرید، تأیید پرداخت، لایسنس، دانلود توکنی (۱۵ دقیقه). احراز هویت با همان `update_code` + هدر `X-Project-Url` (قفل دامنه).

**اصل مهم:** پنل کاملاً با **Livewire 3** بازنویسی شده (SPA با `wire:navigate`)، طراحی همه المان‌ها از صفر با Tailwind CSS 4 انجام شده و لایه API v1 دست‌نخورده مانده (backward-compat با کلاینت‌های موجود).

---

## ۲. استک و الزامات

| لایه | نسخه/ابزار |
|---|---|
| زبان | PHP **8.3+** (dev با 8.4.13) — Laravel **13** |
| فرانت‌اند | Livewire **3** + Alpine 3 + Vite 7 + Tailwind CSS **4** |
| فونت | Vazirmatn (ادمین + شاپ)، RTL کامل |
| دیتابیس | **SQLite** (`database/database.sqlite`) — قابلیت تغییر به MySQL از `.env` |
| پرداخت | `shetabit/payment` v6 **از پکیج محلی `packages/shetabit/`** (نه packagist! — psr-4 در composer.json: `Shetabit\Payment\` و `Shetabit\Multipay\` به `packages/shetabit/*/src` مپ شده) |
| تاریخ | `morilog/jalali` (helper: `verta_date()`) + `fa_num()` برای اعداد فارسی |
| UI | کامپوننت‌های Blade خودساخته (`resources/views/components/`) + رجیستری آیکون‌های Lucide inline |

**نصب:**
```bash
composer install
# .env و database.sqlite داخل بسته موجودند (APP_KEY آماده)
php artisan serve          # http://localhost:8000
# اگر خواستید دیتای دمو را از نو بسازید: php demo-seed.php
# تغییر CSS/JS: bun install && bun run build  (یا npm)
```

**⚠️ برای آپلود فایل‌های بزرگ (ZIP پکیج تا ۵۰۰MB):** علاوه بر سقف Livewire (که در `config/livewire.php` روی ۵۰۰MB است)، محدودیت‌های PHP را هم در php.ini بالا ببرید: `upload_max_filesize = 512M` ، `post_max_size = 512M` ، `memory_limit = 512M` ، `max_execution_time = 300` — (لاراگون: منوی PHP → php.ini؛ سی‌پنل: MultiPHP INI Editor). سپس وب‌سرور/PHP را ری‌استارت کنید.

**ورود پنل:** `admin@panel.test` / `secret123` — **کد آپدیت مشتری دمو:** `1F61148198FD` (علی رضایی، active)، `DDE00593DD90` (مریم حسینی)، `C28ACDCDF536` (غیرفعال — برای تست خطا).

---

## ۳. مدل داده (خلاصه — جزئیات در `database/migrations/`)

- **Customer** (`customers`): name, email, phone, website_url, **update_code (کلید احراز هویت everywhere!)**, status.boot() → تولید خودکار کد ۱۲ کاراکتری hex.
- **Project** (`projects`): name, slug, description, status.
- **Update** (`updates`): project_id, title, **version**, type(major/minor/patch), status(draft/active/archived), description (متن ساده — به API می‌رود)، download_link, file_size, release_date, is_mandatory.
- **Package** (`packages`): project_id, name, slug, category, short_description, **description (HTML از CKEditor)**, thumbnail, is_free, default_price, status, sort_order, purchases_count. Relations: `latestVersion`, `activeVersions`, `activePricingPlans`, `images`. Accessor: `thumbnail_url` (fallback به `public/images/package-default.png`).
- **PackageVersion**: package_id, version, changelog/what_added/what_changed/what_fixed, file_path, file_hash(SHA256), file_size, min_php_version, min_laravel_version, dependencies(json), is_mandatory, status.
- **PackagePricingPlan**: package_id, name, description, duration_months (0=نامحدود), price, discount, **final_price**, is_one_time, is_active.
- **PackagePurchase**: package_id, version_id, pricing_plan_id, customer_id, license_id, **transaction_id**, callback_url, amount, gateway, payment_url, status(pending/paid/failed/refunded), paid_at, meta(json). `markAsPaid/Failed()`.
- **PackageLicense**: license_key(`PKG-...`), package_id, customer_id, purchase_id, **subscription_request_id (لایسنس‌های صادرشده از تأیید اشتراک)**, renewed_from, status(active/revoked/expired), starts_at, expires_at, duration_months.
- **PackageDownloadToken**: token, license_id, version_id, customer_id, expires_at(+15m), used_at, ip.
- **Subscription**: customer_id, project_id, price, start/end/expires_at, status(active/expired/suspended), payment_status, **code** (کد آپدیت قدیمی سطح اشتراک), description.
- **SubscriptionPlan** (`subscription_plans`): name, description, **duration_months (۰=نامحدود)**, price, discount_price, is_free, **is_one_time (محدودیت یک‌بارمصرف)**, is_active, sort_order, **features (JSON آرایه‌ای از متن‌ها — قابلیت‌های طرح)**. Accessors: `final_price`, `has_discount`, `duration_label`. Relations: `packages()` (belongsToMany با pivot `duration_months` — مدت اختصاصی هر پکیج در طرح؛ null = مدت پیش‌فرض طرح). گاردها: `hasCustomerUsed`, `hasCustomerPending`.
- **SubscriptionPlanPackage** (pivot `subscription_plan_package`): subscription_plan_id, package_id, duration_months(nullable), unique(plan,package).
- **SubscriptionRequest** (`subscription_requests`): customer_id, subscription_plan_id, gateway, amount, transaction_id, payment_url, callback_url (برای خریدهای API), **payment_status(pending/paid/failed/free)**, **status(pending/approved/rejected)**, admin_note, paid_at, approved_at, rejected_at, **meta (شامل activated_licenses پس از تأیید)**. چرخه: ثبت → پرداخت درگاه (یا رایگان) → **در انتظار تأیید مدیر** → approve → لایسنس همه پکیج‌های طرح صادر/تمدید | reject → admin_note.
- **Gateway** (`gateways`): key (یکی از ۱۱ کلید `config/general.php → supported_gateways`)، name, ordering, is_active. **GatewayConfig**: gateway_id, key, value (مثلا merchantId). helper سراسری: `get_gateway_configs($key)` از bootstrap/helpers.php.
- **Setting** (`settings`): key, value, type, group (general/email). `Setting::set()`.
- **Transaction**: جدول shetabit (`transactions`).

---

## ۴. نقشه مسیرها

### وب (`routes/web.php`)
```
GET  /                              → Shop\Home            (فروشگاه)
GET  /packages/{slug}               → Shop\PackageShow     (جزئیات + checkout)
GET  /plans                         → Shop\Plans           (طرح‌های اشتراک + خرید)
GET  /subscription-requests/{request} → Shop\SubscriptionStatus (وضعیت درخواست: انتظار تأیید/فعال/رد + لایسنس‌ها)
GET  /payment/callback              → WebPaymentController (بازگشت از درگاه، GET — پکیج و اشتراک)
POST /payment/callback              → WebPaymentController (POST بانک، CSRF-exempt)
GET  /payment/return/{purchase}     → PaymentReturnController (نتیجه + شمارش ۱۰ث — هم خرید پکیج هم درخواست اشتراک)
GET  /payment/result/{purchase}     → Shop\PaymentResult   (صفحه نتیجه)
GET  /get-update/{code}             → UpdateDownloadController (دانلود مستقیم آپدیت)
     + Auth::routes() (laravel/ui)
GET  /admin/...                     → ۱۵ صفحه Livewire پنل (auth middleware)
     + /admin/subscription-plans (پلن‌ها و طرح‌ها — CRUD با features + پکیج‌های هر طرح)
     + /admin/subscription-requests (درخواست‌ها و تأییدها — تأیید/رد + لایسنس‌های صادرشده)
```

### API v1 (`routes/api/v1.php`)
```
GET  /api/v1/check-update?...                          → UpdateController@check
GET  /api/v1/download-update/{id}                      → UpdateController@download

--- طرح‌های اشتراک (جدید ۱۴۰۵/۰۷/۰۳) ---
GET  /api/v1/plans                                      → ApiSubscriptionPlanController@index (طرح‌های فعال + features + packages هر طرح + is_requested)
POST /api/v1/plans/{plan}/purchase                      → body: {callback_url, gateway?} → free: {is_free, request_id, status, message} / paid: {payment_url, transaction_id, amount, gateway, request_id}
GET  /api/v1/subscription-requests/{id}                 → وضعیت درخواست + پیام + licenses[] (پس از تأیید مدیر: license_key/expires_at/is_unlimited/days_remaining)
     (کال‌بک درگاه همان /api/v1/payments/callback و /payment/callback؛ درگاه→ payment/return → callback_url فروشگاه با status+request_id+type=subscription)
```
GET  /api/v1/packages                                  → ApiPackageController@index    (پکیج‌های پروژه‌های مشتری)
                                                        ⭐ هر آیتم: is_purchased + purchased_license {license_key, expires_at, days_remaining, is_unlimited} (+ installed_license قدیمی)
GET  /api/v1/packages/{slug}                           → show (+ is_purchased + purchased_license + installed_license)
POST /api/v1/packages/{slug}/purchase                  → purchase  body: {callback_url, pricing_plan_id} → {payment_url, transaction_id, amount, gateway}
     (اگر رایگان: مستقیم {is_free, license_key, expires_at, download_token})
POST /api/v1/payments/{transactionId}/verify           → verifyPayment → {paid, license_key, expires_at, days_remaining, download_token, signature, version}
GET|POST /api/v1/payments/callback                     → ApiPaymentCallbackController (بازگشت مرورگر؛ بدون callback_url → ریدایرکت به payment.result)
POST /api/v1/packages/{slug}/verify-license            → {valid, expires_at, days_remaining, version, signature, download_token}
GET  /api/v1/packages/{slug}/check-update?current_version= → {has_update, latest_version, changelog, ...}

--- دانلود پکیج خریداری‌شده (جدید ۱۴۰۵/۰۶/۳۱) ---
GET  /api/v1/packages/{slug}/download[?version=x.y.z]  → ⭐ دانلود مستقیم ZIP آخرین نسخه (یا نسخه خاص) با هدرهای احراز — برای proxy از سمت پروژه خریدار
POST /api/v1/packages/{slug}/download-url              → ⭐ ساخت لینک یک‌بارمصرف ۱۵دقیقه‌ای → {download_url, expires_in_seconds, version, file_size, file_hash} — لینک بدون هدر هم کار می‌کند (قابل قرار دادن در href)
GET  /api/v1/packages/download/{dlToken}               → فایل ZIP؛ ⭐ حالا بدون هدرهای احراز هم کار می‌کند (توکن خودش گواهی است) — سازگار با قبل (با هدر هم صحیح)
```
**احراز هویت API** (`PackageApiAuthService`): توکن از `Authorization: Bearer` یا `X-Project-Key` یا `?token=` → Customer با `update_code` + الزام هدر `X-Project-Url` (هوست باید با `customer.website_url` یا زیردامنه‌اش بخواند).

### ⭐ قرارداد یکپارچه‌سازی «پروژه فروشگاه» (store project مشتری)
پروژه فروشگاه مشتری باید برای هر مشتری (با update_code + website_url ثبت‌شده در پنل):
1. **لیست پکیج‌ها**: `GET /api/v1/packages` با هدرهای `Authorization: Bearer {update_code}` + `X-Project-Url: https://{مشتری-دامنه}`.
2. **نمایش دکمه**: برای هر آیتم اگر `is_purchased == true` → دکمه «دانلود» (نه «خرید»); `purchased_license.days_remaining` هم برای هشدار انقضای نزدیک. (طرح‌ها در `active_pricing_plans[]` با فیلدهای `price`/`discount_price` — `final_price` در JSON نیست.)
3. **دانلود**: دو راه —
   - ساده: `POST /api/v1/packages/{slug}/download-url` → بگذار `download_url` را مستقیم در `href` دکمه (مرورگر مشتری بدون هدر دانلود می‌کند؛ ۱۵ دقیقه/یک‌بار).
   - یا: `GET /api/v1/packages/{slug}/download` با هدرها از بک‌اند خودتان proxy کنید (برای شمارش/IP یا هر منطق دیگر).
4. **خرید/تمدید**: `POST /packages/{slug}/purchase` با Body: `{callback_url, pricing_plan_id, gateway?}`:
   - `gateway` اختیاری — درگاه فعال پنل (پیش‌فرض zarinpal)؛ برای تست کل جریان بدون پول واقعی: `local`.
   - پاسخ: `{payment_url, transaction_id, amount, gateway, purchase_id}` — **payment_url را فقط ریدایرکت کنید**: برای درایورهای URL (زرین‌پال) مستقیمِ بانک است؛ برای درایورهای فرم‌محور (به‌پرداخت و…) مسیر امضادار `payment/form/{id}` روی پنل است که فرم خود-ارسال را رندر می‌کند (نیازی به منطق اضافه سمت کلاینت نیست).
   - رایگان (is_free یا final_price≤0): `{is_free: true, license_key, expires_at, download_token}` — بدون درگاه.
   - در بازگشت درگاه (GET/POST به callback_url شما با `transactionId`/`transaction_id`/`Authority`): سمت سرور `POST /api/v1/payments/{trx}/verify` را با هدرهای احراز صدا بزنید → `{paid, license_key, expires_at, days_remaining, download_token, signature, version}` → ذخیره در `installed_modules` (slug + license_key + license_expires_at + last_verified_at — همان چیزی که LicenseGuard مشتری می‌خواند). اگر لایسنس منقضی شده → همان خرید = تمدید.
   - ⚠️ برای درایور آزمایشی `local`: در فراخوانی verify پارامتر `?transactionId={trx}` را هم در query بفرستید (درایور local آن را از request می‌خواند؛ برای درگاه‌های واقعی بی‌اثر).
5. **کیت آماده**: پوشه `download/shop-payment-kit/` (۵ part + README-FA) برای پروژه فروشگاه مشتری + مرجع زنده `mini-services/shop-sim` (port 3001).
همه پاسخ‌های خطا فارسی‌اند: 403 «شما این پکیج را نخریده‌اید یا لایسنس شما فعال نیست.» وقتی لایسنس فعال نیست.

---

## ۵. جریان پرداخت و اشتراک‌ها

### ⭐ جریان طرح‌های اشتراک (۱۴۰۵/۰۷/۰۳)
- **مدیر** در «طرح‌های اشتراک» (admin/subscription-plans) طرح می‌سازد: مدت (۰=نامحدود)، قیمت/تخفیف، رایگان/پولی، یک‌بارمصرف، **features** (آرایه JSON)، **پکیج‌های طرح با مدت اختصاصی** (pivot duration_months؛ null = مدت پیش‌فرض طرح).
- **مشتری** در فرانت `/plans` (یا API) طرح را می‌خرد → `SubscriptionService::createRequest` → درخواست pending: پولی → payment_url درگاه؛ رایگان → مستقیم «در انتظار تأیید مدیر».
- پرداخت موفق (`PaymentService::verifyPayment` → شاخه SubscriptionRequest): payment_status=paid؛ **لایسنس صادر نمی‌شود**.
- **مدیر** در «درخواست‌ها و تأییدها» (admin/subscription-requests) تأیید می‌کند → `SubscriptionService::approve`: برای هر پکیج طرح `LicenseService::issueOrRenewForSubscription` (مدت = pivot ?? پیش‌فرض طرح؛ لایسنس فعال قبلی → تمدید از انقضای فعلی + revoked قدیمی)؛ کلیدها در meta.activated_licenses. رد → admin_note.
- مشتری در `/subscription-requests/{id}` وضعیت + لایسنس‌ها را می‌بیند (wire:poll 60s هنگام انتظار).
- گاردها: یک‌بارمصرف (`hasCustomerUsed`)، درخواست در جریان (`hasCustomerPending`)، طرح غیرفعال، طرح بدون پکیج — همه پیام فارسی.
- **نکته payment/form**: درایورهای فرم‌محور/ local برای درخواست‌های اشتراک query `t=sub` دارند (جزء امضای URL) چون شناسه‌های purchase و subscription_request توالی‌های جدا دارند؛ PaymentFormController با آن رکورد درست را پیدا می‌کند.

### سرویس‌ها
- `PaymentService::createPayment(PackagePurchase|SubscriptionRequest $payable, ?string $gateway = null)`:
  gateway پیش‌فرض `zarinpal`؛ `get_gateway_configs($gateway)` تنظیمات را از جدول gateways می‌خواند؛ callbackUrl = `$purchase->callback_url`؛ بعد از purchase با `transaction_id`/`gateway` آپدیت می‌شود؛ خروجی `payment_url`.
- `PaymentService::verifyPayment(string $transactionId, bool $renew = false)`:
  **اول جدول subscription_requests را چک می‌کند** (شاخه اشتراک → `verifySubscriptionPayment`: markAsPaid، بدون صدور لایسنس)؛ سپس خرید پکیج. اگر pending → `Payment::via(...)->verify()`؛ موفق → `markAsPaid` + صدور لایسنس:
  `$renew` فقط از مسیر **وب** true می‌شود → `LicenseService::issueOrRenew` (اگر مشتری برای همین پکیج لایسنس active/expired دارد → `renewLicense`: انقضا جدید = انقضای قبلی + ماه‌های طرح، لایسنس قدیمی revoked، `renewed_from` ثبت می‌شود)؛ در API همان `issueLicense` مستقیم.
- مسیر رایگان (is_free یا final_price=0): بدون درگاه، خرید paid + لایسنس فوری.

### مسیر API (کلاینت لاراولی مشتری)
purchase با `callback_url` سایت خود مشتری → مشتری به درگاه می‌رود → درگاه برمی‌گردد به سایت مشتری → سایت مشتری `POST /payments/{trx}/verify` را صدا می‌زند → لایسنس/توکن می‌گیرد. (کال‌بک داخلی `/api/v1/payments/callback` فقط برای حالت بدون callback_url → به `payment.result` ریدایرکت می‌شود.)

### مسیر وب (فروشگاه)
checkout در `Shop\PackageShow::buy()`: کد آپدیت → Customer → گارد one_time (`hasCustomerUsed`) → رایگان؟ → لایسنس فوری + ریدایرکت به `payment.result` : خرید pending با `callback_url = route('payment.callback')` + gateway انتخابی → `createPayment($purchase, $gateway)` → ریدایرکت به `payment_url` (بانک) → بازگشت به `/payment/callback` (GET/POST، بدون CSRF) → `verifyPayment($trx, renew: true)` → ریدایرکت `/payment/result/{purchase}`:
- paid: پیام «پکیج «X» در پروژه شما تمدید شد (یا برای پروژه شما فعال شد) و می‌توانید از صفحه پکیج‌ها آن را دانلود کنید» + کلید لایسنس + انقضا (دکمه دانلود حذف شده — دانلود از صفحه پکیج‌های پروژه فروشگاه مشتری انجام می‌شود)
- failed: دلیل (`meta.fail_reason`) + تلاش مجدد؛ pending: دکمه «بررسی مجدد» → `recheck()`.

> **نکته:** درگاه‌ها در جدول `gateways` به‌صورت پیش‌فرض ۱۱ ردیف `is_active=0` دارند؛ برای فعال‌شدن خریدِ پرداختی باید در `/admin/settings/gateways` فعال + پیکربندی شوند. (در دمو فعال نیست — صفحه checkout پیام آمبر نشان می‌دهد.)
> درگateway «toman» داخل `get_gateway_configs` کد legacy به `auth()->user()->orders` دارد (از پروژه قبلی) — اگر فعالش کنید خطا می‌دهد؛ بقیه درگاه‌ها ساده‌اند.

---

## ۶. معماری فرانت‌اند

- **Vite** ورودی‌ها: `resources/css/app.css` + `resources/js/app.js` → خروجی `public/build` (هش‌دار، با manifest). بعد از هر تغییر JS/CSS: `bun run build`.
- **app.js** ماژول‌ها: `livewire-config` (CSRF + uri + progressBar)، `ckeditor`، `loading`؛ بعد `Alpine.data`ها (tabs/shell/toasts store) + eventهای `livewire:navigating/navigated` (نوار پیشرفت + initDarkMode) + `Livewire.start()`.
- **Layoutها**: `components/layouts/app` (پنل: سایدبار + هدر + دراور موبایل)، `components/layouts/guest` (لاگین/ریجیستر)، `components/layouts/shop` (فروشگاه: هدر sticky + فوتر + توست type-aware + گرادیان‌های `.shop-*` داخل بلاک style خودش).
- **کامپوننت‌های UI** (`components/`): btn (variants + **:loading** → wire:loading)، input، select، textarea، field (label+error)، modal (entangle)، tabs، badge، card، stat، avatar، empty، toggle، chart (SVG)، icon.
- **آیکون‌ها**: فقط از رجیستری `app/View/Components/Icon.php` (inline lucide). فهرست کامل در آن فایل؛ آیکون جدید = افزودن به مپ.
- **صفحه‌بندی RTL**: override در `resources/views/vendor/livewire/tailwind.blade.php` (نام‌گذاری مهم! Livewire 3 آن را با نام `livewire::tailwind` resolve می‌کند) → `$records->links()` خودکار RTL فارسی است.
- **توست**: trait `WithToasts` → `$this->toast('msg', 'error|warning|success')` → event → Alpine store → نمایش؛ در layoutهای guest/shop هم پشتیبانی می‌شود.
- **دارک‌مود**: کلاس `dark` روی html + localStorage؛ `initDarkMode()` در head برای جلوگیری از فلش.
- **SPA**: همه لینک‌های داخلی `wire:navigate` (به‌جز خروجی به بانک و دانلود).
- **CKEditor 4.25.1-lts** در `public/assets/ckeditor/` (self-hosted):
  - لودر lazy: `window.loadCkeditor()` (فقط با اولین باز شدن مودال اسکریپت تزریق می‌شود).
  - الگوی sync: `<x-ckeditor model="form.description" :value="..." />` → marker div بیرون از `wire:ignore` (با `data-editor-value` — Livewire آن را morph می‌کند) + textarea مخفی `wire:model` داخل `wire:ignore`؛ change → textarea.value + event `input` (Livewire property آپدیت می‌شود)؛ `Livewire.hook('morphed')` + MutationObserver روی marker → `setData` (server→ادیتور هنگام openEdit).
  - زبان fa، RTL، toolbar سفارشی (داخل `resources/js/ckeditor.js`).
  - ⚠️ **لایسنس**: نسخه-lts بدون کلید معتبر خودش را نابود می‌کند؛ در `ckeditor.js` یک placeholder key با کامنت گذاشته شده — برای پروداکشن کلید واقعی بخرید یا به 4.22.x متن‌باز سوئیچ کنید.
- **پیش‌نمایش آپلود تصویر**: Alpine `filePreview({multiple})` روی input فایل (فقط مودال ویرایش پکیج: thumbnail + gallery) — FileReader → data-URL.
- **لودینگ (جمع‌بندی UX):**
  - `resources/js/loading.js`: نوار پیشرفت (`.livewire-progress` در `document.head` — برای مقاومت به morph) روی **هر** commit (hook `Livewire.hook('commit')` + eventهای window `livewire:busy/idle`)؛ + auto-spinner/disabled روی هر دکمه wire:click/submit که :loading ندارد (رد دکمه‌های دارای `wire:loading.attr`).
  - در CSS: `[wire\:loading] { display:none }` چون باندل ESM لایووایر ۳ این استایل را تزریق نمی‌کند (bg مهم — بدون آن اسپینرها در حالت idle دیده می‌شوند).
  - دکمه‌های `x-btn :loading`: اسپینر `wire:loading` (مخفی پیش‌فرض) + آیکون عادی `wire:loading.remove`.

---

## ۷. گاتچاهای حیاتی (خلاصه تجربه — حتماً رعایت شود)

1. **Blade**: کلاس bare ممنوع (`Route::` → `\Illuminate\Support\Facades\Route::`)؛ `@php(...)` inline را با بلاک `@php @endphp` قاطی نکنید؛ interpolation `{{ }}` **داخل تگ کامپوننت** (`<x-icon ... {{ $x }} />`) تگ را غیرقابل‌کامپایل می‌کند → با `@if` شاخه‌بندی کنید.
2. **Livewire 3**: event `livewire:morphed` روی document **وجود ندارد** (فقط `Livewire.hook('morphed')`)؛ `wire:loading` بدون CSS خودتان در حالت اولیه مخفی نیست؛ id های SQLite AUTOINCREMENT بعد از rollback هم مصرف‌شده می‌مانند (در تست id را از DB بخوانید)؛ صفات فرم آرایه‌ای (`form[description]`) با wire:model خط‌نقطه‌ای (`form.description`) کار می‌کنند ولی فایل آپلودی باید null بماند نه [].
3. **tinker**: فایل تعاملی هنگ می‌کند → فقط `artisan tinker --execute="..."` با timeout.
4. **Carbon 3**: `diffInDays/Months` علامت‌دار است (آینده = مثبت)؛ جهت آرگومان‌ها را با abs تست کنید.
5. **curl**: `-L -X POST` متد را روی ریدایرکت نگه می‌دارد (405) → برای POST redirect-test از `--data` بدون `-X`.
6. **CSRF**: POST بانکی به `/payment/callback` بدون توکن می‌آید → در `bootstrap/app.php` با `validateCsrfTokens(except: ['payment/callback'])` مستثنا شده.
7. **سرویس مشترک**: `storage/logs/laravel.log` بین همه پروسه‌ها مشترک است — قبل از تست truncate کنید (`: > storage/logs/laravel.log`) و بعد چک کنید ۰ بایت است.
8. **PHP در این sandbox**: `/home/z/php-runtime/php` (استاتیک 8.4.13 با gd/sqlite3/zip...) — کامند `php` خالی موجود نیست.
9. **تست بدون رد гряз DB**: همیشه `DB::beginTransaction()` … `rollBack()` (در Livewire::test هم همین).
10. **route:cache** روی این اپ کار نمی‌کرد (closure route) — بعد از حذف closure `/` احتمالاً کار می‌کند ولی در dev نیازی نیست.
11. **agent-browser**: eval با $wire را در کوتیشن تکی بدهید (bash $wire را می‌خورد)؛ اسکرین‌شات full-page مودال‌های fixed را برش می‌زند → `scrollintoview` + viewport screenshot.
12. **rebuild فرانت**: بعد از هر تغییر `resources/js` یا `resources/css` → `bun run build` وگرنه `public/build` قدیمی سرو می‌شود.
13. **`$errors` همیشه باید share شود**: `AppServiceProvider::boot()` یک `View::share('errors', new ViewErrorBag)` پایه ست می‌کند. دلیل: Livewire در هر رندر `shareWithViews('errors', …)` می‌زند و اگر قبلاً چیزی share نشده باشد ('notfound')، پس از رندر آن را **unset** می‌کند → در رندرهای بعدی همان request «Undefined variable $errors» می‌شود (روی هاست‌هایی که middleware وب کامل اجرا نمی‌شود دیده شد). علاوه بر این، فایل‌های کلیدی (field/ckeditor/settings/packages-show) با الگوی `$errors ?? null` + `?->has` هم مقاوم شده‌اند.
14. **x-field کلید خطا**: کلیدهای اعتبارسنجی Livewire با پیشوند کامل ذخیره می‌شوند (`form.site_name`) ولی x-field فقط `for` ساده را می‌شناخت → حالا کاندیدها: `$error` صریح، `$for`، و `form.$for`.
15. **استقرار روی هاست اشتراکی (cPanel)**: اگر استایل‌ها نصفه/بدون padding لود شدند، CSS ساخته‌شده را چک کنید: docroot دامنه باید **روی پوشه `public`** باشد (نه ریشه پروژه) وگرنه `/build/assets/*` که URL مطلق است 404 می‌شود؛ بعد از تغییر فایل‌های ساخت، `php artisan view:clear` + هارد‌ریفرش (Ctrl+Shift+R) مرورگر.
16. **shetabit Multipay این نسخه (v6.4):**
    - `pay()` یک `RedirectionForm` برمی‌گرداند؛ متد `getPaymentUrl()` **وجود ندارد** → قبلاً createPayment با هر درگاه واقعی کرش می‌کرد. حالا: درایورهای GET بدون-ورودی (زرین‌پال) → payment_url = اکشن فرم؛ درایورهای POST → HTML فرم در `meta->payment_form` ذخیره + ریدایرکت به مسیر امضادار `payment.form` (روت وب + middleware signed، ۳۰ دقیقه)؛ درایور `local` → صفحه شبیه‌ساز `payment.fake-gateway` (داده‌ها از رکورد خرید ساخته می‌شوند، نیازی به فرم نیست).
    - کال‌بک `purchase($invoice, fn($driver, $trx))` — `$driver` **آبجکت درایور است نه رشته** → همیشه کلید gateway را ذخیره کنید.
    - `verify()` برگرداندن Receipt = موفق؛ ناموفق = `InvalidPaymentException` → متد `isPaid()` وجود ندارد (حذف شد؛ منطق بر اساس رسید/Exception).
    - درایور `local`: ① باید در `config/payment.php` بخش `drivers` (نه فقط `map`) ثبت شود وگرنه `Driver not found`؛ ② `verify()` درایور `Request::input('transactionId')` را از **درخواست جاری** می‌خواند → فراخوانِ verify باید `?transactionId=` در query بفرستد؛ ③ بازگشت درگاه با پارامتر camelCase `transactionId` است → کال‌بک‌های پنل (web+api) این کلید را هم می‌خوانند.
    - درایور local تنظیمات فرم (title/description/payButton/...) را از `get_gateway_configs` می‌گیرد (case «local» در helper با fallback‌ها)؛ ردیف gateway با key=local هم seed شده (پیش‌فرض غیرفعال).
17. **`latestVersion()` رابطه است** — `$package->latestVersion()` خودِ HasOne را برمی‌گرداند (truthy!)؛ همیشه `->first()` صدا بزنید. باگ API verifyPayment همین بود (TypeError در createDownloadToken).
18. **رکورد pending یتیم**: اگر `createPayment` بعد از `PackagePurchase::create` شکست بخورد، رکورد pending می‌ماند → در purchase API حالا `$purchase->delete()` در catch.
19. **آپلود تصویر با Livewire — هرگز `$file->move()` نزنید**: فایل‌های آپلودی Livewire (TemporaryUploadedFile) در درخواست *قبلی* (POST به `/livewire/upload-file`) آپلود شده‌اند؛ `move()` در پس‌زمینه `move_uploaded_file()` صدا می‌زند که فقط روی فایل‌های *همین درخواست* کار می‌کند → خطای `Could not move the file "…livewire-tmp/…" to "…public/uploads/…"` (روی ویندوز/لاراگون کاربر دیده شد). راه‌حل در `ImageUploadService::persistFile()`: `rename()` معمولی + fallback کپی استریمی (`stream_copy_to_stream`) + حذف مبدأ — هم برای فایل موقت Livewire هم آپلود معمولی، روی هر OS.
20. **CKEditor 4 حالت Source رویداد change نمی‌دهد**: وقتی کاربر روی دکمه «منبع» می‌زند و HTML خام تایپ می‌کند، رویداد `change` ادیتور خاموش است → textarea مخفی (wire:model) هیچ‌وقت آپدیت نمی‌شود و سمت سرر **خالی** می‌رسد. راه‌حل در `resources/js/ckeditor.js` (richEditor): شنونده `mode` → در حالت source روی `textarea.cke_source` مستقیماً input/change/blur بسته می‌شود و sync() اجرا می‌شود؛ برگشت به wysiwyg هم یک sync اجباری دارد. علاوه بر این `allowedContent: true` ست شده تا HTML خام (کلاس/دیتا-اتربیوت‌های سفارشی) در رفت‌وبرگشت wysiwyg⇄source حذف نشود.
22. **inline `@php(...)` در Blade — خطرناک در فایل‌های بزرگ**: رگکس `storePhpBlocks` هر `@php(` را با *نزدیک‌ترین* `@endphp` بعدی جفت می‌کند؛ اگر در همان فایل `@php ... @endphp` بلوکی هم باشد، ممکن است صدها خط به‌عنوان PHP خام بلعیده شود و رندر خراب/خالی شود (در subscription-requests دیده شد). قانون: در blade، فقط فرم بلوکی `@php\n ... \n@endphp` استفاده کنید، نه فرم inline یک‌خطی.
23. **payment/form و اشتراک‌ها**: شناسه‌های `package_purchases` و `subscription_requests` توالی‌های جداگانه دارند (collision قطعی) → URL امضادار درگاه‌های فرم‌محور برای اشتراک‌ها query `t=sub` دارد که **جزء امضاست**؛ PaymentFormController با آن جدول درست را انتخاب می‌کند. اگر روزی مسیر دیگری ساختید همین الگو را نگه دارید.
24. **بسته‌بندی: هرگز از baseline قدیمی بیلد نکنید** — در بسته‌بندی قبلی، staging از «استخراج zip قدیمی + overlay چند فایل» ساخته شد و بخش‌هایی که در همان جلسه اضافه شده بودند (طرح‌ها/درخواست‌ها) گم شدند و کاربر فکر کرد حذف شده‌اند. قانون: staging باید از **دایرکتوری زنده کامل پروژه** با rsync excludeهای anchored (مثل `/vendor` نه `vendor` تا resources/views/vendor حذف نشود) ساخته شود.

21. **سقف پیش‌فرض آپلود موقت Livewire فقط ۱۲MB است**: `livewire.temporary_file_upload.rules` وقتی null باشد → `['required','file','max:12288']` → آپلود فایل‌های ZIP بزرگ پکیج با پیام «The versionFile failed to upload.» می‌شکند. حالا در `config/livewire.php` سقف ۵۰۰MB است. **محدودیت‌های PHP باید جداگانه بالا برود** (درگاه آپلود تک‌پارچه است، نه chunked): `upload_max_filesize` و `post_max_size` و `memory_limit` — لاراگون: php.ini از منوی PHP؛ سی‌پنل: MultiPHP INI Editor؛ اگر PHP قبول نکند، همان پیام failed to upload دوباره ظاهر می‌شود.


---

## ۸. روش تست استاندارد (playbook)

```bash
PHP=/home/z/php-runtime/php
# لاگین curl:
rm -f /tmp/cj.txt
T=$(curl -s -c /tmp/cj.txt http://localhost:8000/login | grep -o 'name="_token" value="[^"]*"' | sed 's/.*value="//;s/"//')
curl -s -b /tmp/cj.txt -c /tmp/cj.txt -X POST http://localhost:8000/login -d "_token=$T&email=admin@panel.test&password=secret123" -o /dev/null
curl -s -b /tmp/cj.txt http://localhost:8000/admin/projects | grep ...

# منطق بدون کثیف‌کردن DB:
$PHP artisan tinker --execute="
\ Livewire\Livewire::test(\App\Livewire\Shop\PackageShow::class, ['slug' => 'modern-shop-theme'])
  ->set('updateCode', '1F61148198FD') ->call('buy') ->assertRedirect(...);
"   # برای DB-write ها داخل beginTransaction/rollBack بگذارید
```
مرورگر: `agent-browser open/snapshot -i/fill/click/eval` (سشن نام‌دار برای جداسازی: `--session x`).

---

## ۹. وضعیت فعلی و دیتای دمو

- همه ۱۳ بخش ادمین + فروشگاه + پرداخت + CKEditor + لودینگ **کامل و تست‌شده** (curl + Livewire::test + browser E2E)؛ `laravel.log` پاک.
- دیتای دمو (seed با `demo-seed.php`): ۳ پروژه، ۵ پکیج (۴ فعال؛ `modern-shop-theme` رایگان)، ۱۵ نسخه، ۸ پلن، ۸ مشتری، ۶ لایسنس، ۶ خرید، ۵ اشتراک، ۴ آپدیت، ۱۱ درگاه (غیرفعال).
- فایل‌های ZIP دموِ کوچک (README داخل هر zip) در `storage/app/private/packages/*.zip` موجودند تا مسیر دانلود کاملاً تست‌شودنی باشد؛ در محیط واقعی فایل‌های ZIP واقعی هر نسخه را از پنل ادمین آپلود کنید (فایل دقیقاً در `file_path` ثبت‌شده ذخیره می‌شود).

### محدودیت‌ها / پیشنهادهای ادامه کار
1. **تنظیم درگاه واقعی** لازم است تا خریدِ پرداختی وب/API کار کند (فعال‌سازی + merchantId در `/admin/settings/gateways`).
2. کلید لایسنس CKEditor (یا سوئیچ به 4.22.x) برای پروداکشن.
3. ایمیل‌ها `MAIL_MAILER=log` — برای ریست پسورد واقعی تنظیم شود.
4. `verify-license` فقط برای همان customer است؛ خرید repeat در API همیشه لایسنس جدید صادر می‌کند (تمدید فقط مسیر وب) — اگر API هم renewal بخواهد: `verifyPayment($trx, renew: true)` در ApiPackageController.
5. آپلود فایل نسخه/آپدیت به ۱۲MB محدود (config/livewire.php قابل تغییر).
6. صف/وب‌هوک بانکی (IPN) پیاده نشده — فقط بازگشت مرورگر + verify کشیده‌شده.
7. تست‌های خودکار PHPUnit برای جریان خرید اضافه نشده (تست‌ها دستی/Livewire::test در کارهای قبلی بوده).

---

## ۱۰. درخت کلیدی فایل‌ها

```
admin-panel/
├─ app/
│  ├─ Livewire/            Dashboard, Projects/, Updates/, Packages/(Index+Show+…), Licenses/,
│  │                       Purchases/, Customers/, Subscriptions/, Users/, Reports/, Logs/,
│  │                       Settings/, Gateways, Shop/(Home+PackageShow+PaymentResult), Concerns/WithToasts
│  ├─ Http/Controllers/    Api/(ApiPackageController, ApiPaymentCallbackController, UpdateController,
│  │                       ApiPackageDownloadController), Front/(WebPaymentController, UpdateDownloadController)
│  ├─ Services/            PaymentService, LicenseService, PackageApiAuthService, ImageUploadService
│  ├─ Models/              (بخش ۳)
│  └─ View/Components/     Icon.php (رجیستری آیکون)
├─ bootstrap/              app.php (CSRF except) + helpers.php (fa_num, money, verta_date, get_gateway_configs…)
├─ config/general.php      supported_gateways (۱۱ درگاه ایرانی)
├─ database/               migrations/ + database.sqlite (دمو) + demo-seed.php در ریشه پروژه
├─ packages/shetabit/      پکیج محلی پرداخت (source of truth — با composer نصب نمی‌شود دوباره)
├─ public/assets/ckeditor/ CKEditor 4.25.1-lts (self-hosted)
├─ public/images/          placeholder های پکیج
├─ resources/
│  ├─ css/app.css          Tailwind 4 + توکن‌ها + .rich-content + [wire:loading] + .livewire-progress
│  ├─ js/{app.js, livewire-config.js, ckeditor.js, loading.js}
│  └─ views/
│     ├─ components/       (UI + layouts/(app|guest|shop) + ckeditor)
│     ├─ livewire/         (صفحات پنل + shop/)
│     ├─ auth/             (login/register/passwords — طراحی جدید)
│     └─ vendor/livewire/tailwind.blade.php   (صفحه‌بندی RTL)
├─ routes/                 web.php + api/v1.php
└─ AI-CONTEXT.md           (همین فایل)
```

**سابقه کامل کارها**: `/home/z/my-project/worklog.md` (در محیط توسعه‌ی sandbox) — هر تسک با ID + جزئیات + گاتچاها ثبت شده.

---

*آخرین به‌روزرسانی: بعد از فاز ۶ (CKEditor + فروشگاه + پرداخت + لودینگ). همه‌چیز در این لحظه تست‌شده و سبز است.*
