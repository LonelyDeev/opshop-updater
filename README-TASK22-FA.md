# 📦 Task 22 — رفع TypeError در پاسخ JSON + بازنویسی کامپوننت Card

## ۱) خطای جدید بعد از آخرین تغییرات چی بود؟

```
Symfony\Component\HttpFoundation\JsonResponse::__construct():
Argument #2 ($status) must be of type int, string given
```

### 🔍 ریشه‌یابی (قطعی):

در کنترلرهای API این الگو وجود داشت:

```php
} catch (RuntimeException $e) {
    return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 403);
}
```

**نکته‌ی مخرب:** `PDOException` در PHP خودش فرزندِ `RuntimeException` است و متد
`getCode()` آن به‌جای عدد، **رشته‌ی SQLSTATE** برمی‌گرداند؛ مثل `"HY000"` یا `"23000"`.

- رشته‌ی `"HY000"` غیرخالی است → عبارت `?: 403` همان رشته را پاس می‌دهد
  (چون رشته‌ی غیرخالی falsy نیست!)
- نتیجه: `response()->json($data, "HY000")` → **TypeError** (کد وضعیت HTTP باید int باشد)

### چرا بعد از تغییرات قبلی ظاهر شد؟

هر خطای دیتابیسی (مثلاً **اجر نشدن مایگریشن‌های تسک ۲۱**) داخل جریان API باعث
PDOException با کد رشته‌ای می‌شود و این باگ پنهان فعال می‌شد.
مثال: اگر ستون `gateways.logo` (مایگریشن 000004 تسک ۲۱) ساخته نشده باشد،
هر درخواستی که لیست درگاه‌ها را بخواند PDOException می‌گیرد → این خطا.

## ۲) راه‌حل اعمال‌شده

### ✅ هِلپر امن `exception_status()` — `bootstrap/helpers.php`

```php
exception_status(\Throwable $e, int $fallback = 500): int
```

- فقط کدهای **int معتبر ۱۰۰ تا ۵۹۹** را برمی‌گرداند
- رشته‌های عددی مثل `"404"` را هم قبول می‌کند و به int تبدیل می‌کند
- SQLSTATE مثل `"HY000"` → مقدار fallback (403/422/500) → **دیگر TypeError ممکن نیست**

### ✅ ۱۲ نقطه‌ی آسیب‌پذیر اصلاح شد

- `app/Http/Controllers/Api/ApiPackageController.php` (۵ مورد)
- `app/Http/Controllers/Api/ApiSubscriptionController.php` (۴ مورد)
- `app/Http/Controllers/Api/ApiPackageDownloadController.php` (۳ مورد)

همه به این شکل تبدیل شدند:

```php
return response()->json(['error' => $e->getMessage()], exception_status($e, 403));
```

> 📌 پیام اصلی خطا همچنان به مشتری می‌رسد؛ فقط کد HTTP حالا همیشه صحیح است.

## ۳) کامپوننت Card — `resources/views/components/card.blade.php`

بدنه‌ی کارت طبق الگوی استاندارد Blade بازنویسی شد:

```blade
<div {{ $attributes->class([$padding, $attributes->get('body-class')])->except('body-class') }}>
    {{ $slot }}
</div>
```

- `padding` همچنان همان prop با پیش‌فرض `p-5 sm:p-6` است (از `@props` بالای فایل)
- `body-class` حالا واقعاً کار می‌کند: با class بدنه **merge** می‌شود و دیگر به‌صورت
  اتریبیوت HTML نامعتبر روی `<section>` رندر نمی‌شود (از ریشه هم `except` شد)
- اگر `padding` خالی باشد، `body-class` دیگر گم نمی‌شود (باگ قبلی)

## ۴) نصب روی هاست

```bash
# ۱) فایل‌ها را روی پروژه باز کنید (پوشه‌های app/bootstrap/resources)
unzip task22-json-status-card-fix.zip -d /home3/webtproi/repositories/opshop-updater

cd /home3/webtproi/repositories/opshop-updater

# ۲) ⚠️ مهم: اگر مایگریشن‌های تسک ۲۱ را هنوز اجرا نکرده‌اید حتماً اجرا کنید
#    (نصب نشدنِ ستون gateways.logo خودش عامل PDOException و این TypeError بود)
php artisan migrate --force

# ۳) پاک‌سازی کش‌ها (blade کامپایل‌شده‌ی قدیمی card باید تازه شود)
php artisan view:clear
php artisan optimize:clear
```

## ۵) فایل‌های تغییرکرده

| فایل | تغییر |
|---|---|
| `bootstrap/helpers.php` | هِلپر جدید `exception_status()` |
| `app/Http/Controllers/Api/ApiPackageController.php` | ۵ catch اصلاح شد |
| `app/Http/Controllers/Api/ApiSubscriptionController.php` | ۴ catch اصلاح شد |
| `app/Http/Controllers/Api/ApiPackageDownloadController.php` | ۳ catch اصلاح شد |
| `resources/views/components/card.blade.php` | الگوی `class()/except()` + رفع نشت `body-class` |

## ۶) تست‌های انجام‌شده (E2E)

- `exception_status(PDOException با "HY000")` → `int(403)` ✅ (قبلاً TypeError!)
- `exception_status(RuntimeException با code=404)` → `int(404)` ✅
- رندر داشبورد/گزارش‌ها/تنظیمات/فروشگاه → ۲۰۰، پدینگ کارت‌ها سالم (بررسی تصویری) ✅
- `GET /api/v1/packages` با هدرهای صحیح → ۲۰۰ + بلوک gateways/subscription ✅
- توکن نامعتبر → `{"error":"مشتری نامعتبر است."}` با status عددی 403 ✅
- صفر خطای کنسول مرورگر ✅
