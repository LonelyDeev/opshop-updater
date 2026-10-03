<?php

namespace App\Services;

use App\Models\Package;
use App\Models\PackageImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * سرویس آپلود تصاویر پکیج‌ها — مقاوم‌سازی‌شده برای هاست اشتراکی:
 *
 *  ۱) مسیر اصلی: public/uploads/… (سرو مستقیم توسط وب‌سرور)
 *  ۲) مسیر جایگزین (fallback): storage/app/public/uploads/…
 *     اگر public قابل نوشتن نباشد — از طریق روت /uploads/{path} سرو می‌شود.
 *
 *  در هر دو حالت مسیرِ ذخیره‌شده در دیتابیس یکسان است: uploads/…
 *  پس هیچ تغییری در فرانت/API لازم نیست.
 */
class ImageUploadService
{
    private const THUMBNAIL_DIR = 'uploads/packages/thumbnails';
    private const GALLERY_DIR   = 'uploads/packages/gallery';

    /** دایرکتوری جایگزین وقتی public قابل نوشتن نیست */
    private const FALLBACK_ROOT = 'public/uploads'; // داخل storage/app

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_SIZE_KB   = 3072; // 3MB

    /* ===================================================================
     *  آپلود تصویر شاخص
     *  Returns: مسیر نسبی (uploads/packages/thumbnails/xxx.png)
     * =================================================================== */
    public function uploadThumbnail(UploadedFile $file, string $slug): string
    {
        $this->validateImage($file);

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename  = $slug . '_thumb_' . time() . '_' . Str::random(6) . '.' . $extension;

        return $this->storeFile($file, self::THUMBNAIL_DIR, $filename);
    }

    /* ===================================================================
     *  آپلود چندین تصویر گالری
     *  Returns: array of PackageImage
     * =================================================================== */
    public function uploadGalleryImages(array $files, Package $package): array
    {
        $uploaded = [];

        DB::transaction(function () use ($files, $package, &$uploaded) {
            $nextOrder = $package->images()->max('sort_order') ?: 0;

            foreach ($files as $file) {
                if (!$file instanceof UploadedFile || !$file->isValid()) {
                    continue;
                }

                $this->validateImage($file);

                $extension    = $file->getClientOriginalExtension() ?: 'jpg';
                $filename     = $package->slug . '_gallery_' . time() . '_' . Str::random(6) . '.' . $extension;

                // ذخیره اطلاعات قبل از انتقال
                $originalName = $file->getClientOriginalName();
                $fileSize     = $file->getSize();

                $path = $this->storeFile($file, self::GALLERY_DIR, $filename);

                $nextOrder++;

                $image = PackageImage::create([
                    'package_id'    => $package->id,
                    'path'          => $path,
                    'original_name' => $originalName,
                    'size'          => $fileSize,
                    'sort_order'    => $nextOrder,
                    'is_active'     => true,
                ]);

                $uploaded[] = $image;
            }
        });

        return $uploaded;
    }

    /* ===================================================================
     *  هسته ذخیره‌سازی: public → در صورت عدم امکان، storage fallback
     * =================================================================== */
    private function storeFile(UploadedFile $file, string $relativeDir, string $filename): string
    {
        $publicDir = public_path($relativeDir);

        // ۱) تلاش برای نوشتن در public
        if ($this->ensureDirectory($publicDir)) {
            try {
                $file->move($publicDir, $filename);
                @chmod($publicDir . '/' . $filename, 0644);

                return $relativeDir . '/' . $filename;
            } catch (\Throwable $e) {
                Log::warning('Upload to public dir failed, falling back to storage', [
                    'dir'   => $relativeDir,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('Public upload dir is not writable — using storage fallback', [
                'dir' => $relativeDir,
                'hint' => 'دسترسی پوشه public/uploads را بررسی کنید (۷۷۵ یا مالکیت کاربر وب‌سرور).',
            ]);
        }

        // ۲) مسیر جایگزین: storage/app/public/uploads/…
        $fallbackDir = storage_path('app/' . self::FALLBACK_ROOT . '/' . $relativeDir);

        if (!$this->ensureDirectory($fallbackDir, 0775)) {
            throw new RuntimeException(
                'ذخیره تصویر ناموفق بود؛ نه public و نه storage قابل نوشتن هستند. ' .
                'لطفاً دسترسی پوشه‌های public/uploads و storage/app را بررسی کنید (۷۷۵).'
            );
        }

        $file->move($fallbackDir, $filename);
        @chmod($fallbackDir . '/' . $filename, 0644);

        return $relativeDir . '/' . $filename;
    }

    /**
     * ساخت مطمئن دایرکتوری + اطمینان از قابل نوشتن بودن
     */
    private function ensureDirectory(string $path, int $mode = 0775): bool
    {
        if (!is_dir($path)) {
            @mkdir($path, $mode, true);
        }

        // اگر وجود دارد ولی قابل نوشتن نیست → تلاش برای اصلاح دسترسی
        if (is_dir($path) && !is_writable($path)) {
            @chmod($path, $mode);
            // والد را هم اصلاح می‌کنیم (مثلاً خود uploads)
            @chmod(dirname($path), $mode);
        }

        return is_dir($path) && is_writable($path);
    }

    /* ===================================================================
     *  حذف تصاویر (هر دو مسیر پاک می‌شوند)
     * =================================================================== */
    public function deleteThumbnail(Package $package): void
    {
        if ($package->thumbnail) {
            $this->deleteFile($package->thumbnail);
            $package->update(['thumbnail' => null]);
        }
    }

    public function deleteGalleryImage(PackageImage $image): void
    {
        $this->deleteFile($image->path);
        $image->delete();
    }

    public function deleteAllGalleryImages(Package $package): int
    {
        $count = 0;
        foreach ($package->images as $image) {
            $this->deleteFile($image->path);
            $image->delete();
            $count++;
        }
        return $count;
    }

    /** حذف فایل از public و در صورت نبود، از storage fallback */
    private function deleteFile(string $relativePath): void
    {
        $publicPath   = public_path($relativePath);
        $fallbackPath = storage_path('app/' . self::FALLBACK_ROOT . '/' . $relativePath);

        foreach ([$publicPath, $fallbackPath] as $candidate) {
            if (is_file($candidate)) {
                @unlink($candidate);
            }
        }
    }

    /* ===================================================================
     *  آپدیت ترتیب تصاویر گالری
     * =================================================================== */
    public function updateOrder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $order => $id) {
                PackageImage::where('id', $id)->update(['sort_order' => $order + 1]);
            }
        });
    }

    /* ===================================================================
     *  Validation
     * =================================================================== */
    private function validateImage(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
            throw new RuntimeException(
                'فرمت فایل مجاز نیست. فقط JPG, PNG, WEBP, GIF مجاز است.'
            );
        }

        if ($file->getSize() > self::MAX_SIZE_KB * 1024) {
            throw new RuntimeException(
                'حجم فایل نباید بیشتر از ' . (self::MAX_SIZE_KB / 1024) . ' مگابایت باشد.'
            );
        }
    }
}
