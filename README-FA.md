# بسته رفع ۴ مشکل گزارش‌شده (آپلود تصویر / CKEditor منبع / آپلود ZIP / mt-8)

این بسته فقط فایل‌های تغییر‌یافته است. روی **ریشه پروژه‌تان** (همان‌جا که `artisan` و `app/` هستند) استخراج کنید تا جایگزین شوند. دیتابیس و `.env` شما دست‌نخورده می‌ماند.

## چه مشکلی رفع شد؟

### ۱) خطای آپلود تصویر شاخص/گالری — `Could not move the file "…livewire-tmp…" to "…public/uploads/…"`
**علت:** فایل‌های آپلودی Livewire در درخواستِ *قبلی* آپلود می‌شوند؛ متد `move()` در پس‌زمینه از `move_uploaded_file()` استفاده می‌کند که فقط روی فایل‌های *همین درخواست* کار می‌کند → همیشه شکست می‌خورد (روی هر سیستم‌عاملی).
**رفع:** `app/Services/ImageUploadService.php` — متد جدید `persistFile()`: انتقال با `rename()` معمولی + fallback کپی استریمی. پوشه مقصد هم در صورت نبود ساخته می‌شود.

### ۲) CKEditor — کد HTML در حالت «منبع» ذخیره نمی‌شد و سمت کنترلر خالی می‌رسید
**علت:** CKEditor 4 در حالت Source رویداد `change` نمی‌دهد؛ بنابراین textarea مخفی (wire:model) هرگز آپدیت نمی‌شد.
**رفع:** `resources/js/ckeditor.js` — با رویداد `mode`، در حالت Source شنونده‌های `input/change/blur` مستقیماً روی textarea منبع (`.cke_source`) بسته می‌شوند و هر تایپی فوراً به Livewire سینک می‌شود. برگشت به WYSIWYG هم یک سینک اجباری دارد.
**بهبود جانبی:** `allowedContent: true` — HTML خام (کلاس‌ها و data-attributes سفارشی) هنگام رفت‌وبرگشت منبع⇄گرافیکی دیگر حذف نمی‌شود.

### ۳) خطای `The versionFile failed to upload.` در آپلود فایل ZIP نسخه
**علت:** سقف پیش‌فرض آپلود موقت Livewire فقط **۱۲ مگابایت** است.
**رفع:** `config/livewire.php` — سقف به **۵۰۰MB** افزایش یافت.
**⚠️ الزامی — محدودیت‌های PHP را هم بالا ببرید** وگرنه همین خطا باقی می‌ماند (آپلود Livewire یک‌تکه است):
```ini
upload_max_filesize = 512M
post_max_size = 512M
memory_limit = 512M
max_execution_time = 300
```
- **لاراگون:** منوی Laragon → PHP → php.ini (یا فایل `C:\laragon\bin\php\...\php.ini`) → مقادیر بالا → ذخیره → ری‌استارت Apache/Nginx.
- **سی‌پنل:** MultiPHP INI Editor → همان چهار مقدار.

### ۴) حذف `-mt-8` از سکشن آمار فروشگاه
`resources/views/livewire/shop/home.blade.php` — کلاس منفی حذف شد (`mx-auto flex w-full max-w-7xl …`).

## نحوه اعمال

**گزینه الف (سریع‌ترین):** محتویات پوشه `admin-panel/` این بسته را روی ریشه پروژه کپی/جایگزین کنید. چون `public/build/` (باندل ساخته‌شده) داخل بسته هست، **نیازی به اجرای build ندارید**. سپس:
```bash
php artisan optimize:clear
```
و یک بار Ctrl+Shift+R (هارد‌ریفرش) در مرورگر.

**گزینه ب (اگر خودتان بیلد می‌کنید):** فقط ۴ فایل سورس (`app/…`، `config/…`، `resources/js/ckeditor.js`، `resources/views/…`) را جایگزین کنید و:
```bash
npm run build     # یا bun run build
php artisan optimize:clear
```

## فهرست فایل‌ها
```
admin-panel/app/Services/ImageUploadService.php          ← رفع ۱
admin-panel/resources/js/ckeditor.js                     ← رفع ۲ (بعد از بیلد لازم دارد، مگر گزینه الف)
admin-panel/config/livewire.php                          ← رفع ۳ (+ راهنمای php.ini در کامنت)
admin-panel/resources/views/livewire/shop/home.blade.php ← رفع ۴
admin-panel/public/build/manifest.json                   ← باندل آماده (گزینه الف)
admin-panel/public/build/assets/app-*.js                 ← باندل آماده
admin-panel/public/build/assets/app-*.css
admin-panel/AI-CONTEXT.md                                ← سند به‌روزشده (گاتچاهای ۱۹ تا ۲۱)
```
