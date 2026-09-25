<?php

namespace App\Services;

use App\Models\Package;
use App\Models\PackageImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageUploadService
{
    private const THUMBNAIL_DIR = 'uploads/packages/thumbnails'; // تغییر مسیر
    private const GALLERY_DIR   = 'uploads/packages/gallery'; // تغییر مسیر

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_SIZE_KB   = 3072; // 3MB

    /* ===================================================================
     *  آپلود تصویر شاخص
     *  Returns: مسیر فایل در public/uploads/packages/thumbnails
     * =================================================================== */
    public function uploadThumbnail(UploadedFile $file, string $slug): string
    {
        $this->validateImage($file);

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = $slug . '_thumb_' . time() . '_' . Str::random(6) . '.' . $extension;

        // مسیر کامل در پوشه public
        $destinationPath = public_path(self::THUMBNAIL_DIR);

        // ایجاد پوشه اگر وجود ندارد
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $target = $destinationPath . DIRECTORY_SEPARATOR . $filename;

        if (!@copy($file->getRealPath(), $target)) {
            throw new RuntimeException('خطا در ذخیره تصویر شاخص.');
        }


        // مسیر نسبی برای ذخیره در دیتابیس
        $path = self::THUMBNAIL_DIR . '/' . $filename;

        if (!$path) {
            throw new RuntimeException('خطا در ذخیره تصویر شاخص.');
        }

        return $path;
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

                $extension = $file->getClientOriginalExtension() ?: 'jpg';
                $filename = $package->slug . '_gallery_' . time() . '_' . Str::random(6) . '.' . $extension;

                // ذخیره اطلاعات قبل از انتقال
                $originalName = $file->getClientOriginalName();
                $fileSize = $file->getSize(); // <-- ذخیره قبل از انتقال

                $destinationPath = public_path(self::GALLERY_DIR);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $filename);
                $path = self::GALLERY_DIR . '/' . $filename;

                $nextOrder++;

                $image = PackageImage::create([
                    'package_id'    => $package->id,
                    'path'          => $path,
                    'original_name' => $originalName, // استفاده از متغیر ذخیره شده
                    'size'          => $fileSize,     // استفاده از متغیر ذخیره شده
                    'sort_order'    => $nextOrder,
                    'is_active'     => true,
                ]);

                $uploaded[] = $image;
            }
        });

        return $uploaded;
    }
    /* ===================================================================
     *  حذف تصویر شاخص
     * =================================================================== */
    public function deleteThumbnail(Package $package): void
    {
        if ($package->thumbnail && file_exists(public_path($package->thumbnail))) {
            unlink(public_path($package->thumbnail));
            $package->update(['thumbnail' => null]);
        }
    }

    /* ===================================================================
     *  حذف یک تصویر گالری
     * =================================================================== */
    public function deleteGalleryImage(PackageImage $image): void
    {
        if (file_exists(public_path($image->path))) {
            unlink(public_path($image->path));
        }
        $image->delete();
    }

    /* ===================================================================
     *  حذف همه‌ی گالری
     * =================================================================== */
    public function deleteAllGalleryImages(Package $package): int
    {
        $count = 0;
        foreach ($package->images as $image) {
            if (file_exists(public_path($image->path))) {
                unlink(public_path($image->path));
            }
            $image->delete();
            $count++;
        }
        return $count;
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
