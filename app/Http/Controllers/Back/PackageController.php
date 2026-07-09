<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageImage;
use App\Models\Project;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public function __construct(private ImageUploadService $imageService) {}

    public function index(Request $request)
    {
        $query = Package::with(['project', 'versions' => fn ($q) => $q->where('status', 'active')->latest()]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $packages = $query->latest()->paginate(15)->appends($request->all());
        $projects = Project::orderBy('name')->get();

        return view('back.packages.index', compact('packages', 'projects'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        $statuses = $this->getStatuses();
        $categories = $this->getCategories();

        return view('back.packages.create', compact('projects', 'statuses', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id'        => 'required|exists:projects,id',
            'name'              => 'required|string|max:255',
            'slug'              => 'nullable|string|max:100|unique:packages,slug',
            'short_description' => 'nullable|string|max:255',
            'description'       => 'nullable|string',
            'author'            => 'nullable|string|max:100',
            'category'          => 'nullable|string|max:50',
            'thumbnail_url'     => 'nullable|url|max:255',
            'thumbnail_file'    => 'nullable|image|mimes:jpeg,png,webp,gif|max:3072',
            'gallery'           => 'nullable|array|max:10',
            'gallery.*'         => 'image|mimes:jpeg,png,webp,gif|max:3072',
            'is_free'           => 'boolean',
            'default_price'     => 'nullable|integer|min:0',
            'status'            => 'required|in:draft,active,archived',
            'module_name'       => 'nullable|string|max:100',
            'sort_order'        => 'nullable|integer|min:0',
        ]);

        // تولید slug اگر خالی باشد
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // تولید module_name اگر خالی باشد
        if (empty($validated['module_name'])) {
            $validated['module_name'] = ucfirst(Str::camel($validated['slug']));
        }

        // تنظیم مقادیر پیش‌فرض
        $validated['is_free'] = $request->has('is_free');
        $validated['default_price'] = $validated['default_price'] ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        // مدیریت thumbnail
        $validated['thumbnail'] = null;
        if ($request->hasFile('thumbnail_file')) {
            $validated['thumbnail'] = $this->imageService->uploadThumbnail(
                $request->file('thumbnail_file'),
                $validated['slug']
            );
        } elseif (!empty($validated['thumbnail_url'])) {
            $validated['thumbnail'] = $validated['thumbnail_url'];
        }

        // حذف فیلدهای اضافی
        unset($validated['thumbnail_file'], $validated['thumbnail_url'], $validated['gallery']);

        // ایجاد پکیج
        $package = Package::create($validated);

        // آپلود گالری
        if ($request->hasFile('gallery')) {
            $this->imageService->uploadGalleryImages($request->file('gallery'), $package);
        }

        return redirect()->route('admin.packages.show', $package)
            ->with('success', 'پکیج با موفقیت ایجاد شد.');
    }

    public function show(Package $package)
    {
        $package->load(['project', 'versions' => fn ($q) => $q->latest(), 'pricingPlans', 'licenses.customer', 'images']);

        return view('back.packages.show', compact('package'));
    }

    public function edit(Package $package)
    {
        $package->load('images');
        $projects = Project::orderBy('name')->get();
        $statuses = $this->getStatuses();
        $categories = $this->getCategories();

        return view('back.packages.edit', compact('package', 'projects', 'statuses', 'categories'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'project_id'        => 'required|exists:projects,id',
            'name'              => 'required|string|max:255',
            'slug'              => 'required|string|max:100|unique:packages,slug,' . $package->id,
            'short_description' => 'nullable|string|max:255',
            'description'       => 'nullable|string',
            'author'            => 'nullable|string|max:100',
            'category'          => 'nullable|string|max:50',
            'thumbnail_url'     => 'nullable|url|max:255',
            'thumbnail_file'    => 'nullable|image|mimes:jpeg,png,webp,gif|max:3072',
            'remove_thumbnail'  => 'boolean',
            'gallery'           => 'nullable|array|max:10',
            'gallery.*'         => 'image|mimes:jpeg,png,webp,gif|max:3072',
            'is_free'           => 'boolean',
            'default_price'     => 'nullable|integer|min:0',
            'status'            => 'required|in:draft,active,archived',
            'module_name'       => 'nullable|string|max:100',
            'sort_order'        => 'nullable|integer|min:0',
        ]);

        $validated['is_free'] = $request->has('is_free');

        // مدیریت تصویر شاخص
        $removeThumbnail = $request->boolean('remove_thumbnail');

        if ($removeThumbnail) {
            // حذف تصویر فعلی
            $this->imageService->deleteThumbnail($package);
            $validated['thumbnail'] = null;
        } elseif ($request->hasFile('thumbnail_file')) {
            // حذف تصویر قبلی و جایگزینی با فایل جدید
            $this->imageService->deleteThumbnail($package);
            $validated['thumbnail'] = $this->imageService->uploadThumbnail(
                $request->file('thumbnail_file'),
                $package->slug
            );
        } elseif (!empty($validated['thumbnail_url'])) {
            // حذف تصویر قبلی و استفاده از URL
            $this->imageService->deleteThumbnail($package);
            $validated['thumbnail'] = $validated['thumbnail_url'];
        } else {
            // نگه داشتن مقدار فعلی
            unset($validated['thumbnail']);
        }

        // حذف فیلدهای اضافی
        unset($validated['thumbnail_file'], $validated['thumbnail_url'], $validated['remove_thumbnail'], $validated['gallery']);

        // بروزرسانی پکیج
        $package->update($validated);

        // آپلود گالری جدید (به گالری موجود اضافه می‌شود)
        if ($request->hasFile('gallery')) {
            $this->imageService->uploadGalleryImages($request->file('gallery'), $package);
        }

        return redirect()->route('admin.packages.show', $package)
            ->with('success', 'پکیج با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Package $package)
    {
        // حذف تصویر شاخص و گالری
        $this->imageService->deleteThumbnail($package);
        $this->imageService->deleteAllGalleryImages($package);

        // حذف فایل‌های ZIP نسخه‌ها
        foreach ($package->versions as $version) {
            if ($version->file_path && file_exists(storage_path('app/' . $version->file_path))) {
                unlink(storage_path('app/' . $version->file_path));
            }
        }

        $package->delete();

        return redirect()->route('admin.packages.index')
            ->with('success', 'پکیج و تمام نسخه‌های آن حذف شد.');
    }

    /* ===================================================================
     *  AJAX: حذف یک تصویر گالری
     * =================================================================== */
    public function deleteImage(Package $package, PackageImage $image)
    {
        if ($image->package_id !== $package->id) {
            return response()->json(['success' => false, 'message' => 'نامعتبر.'], 422);
        }

        $this->imageService->deleteGalleryImage($image);

        return response()->json(['success' => true, 'message' => 'تصویر حذف شد.']);
    }

    /* ===================================================================
     *  AJAX: تغییر ترتیب گالری
     *  Body: { ordered_ids: [3, 1, 2, ...] }
     * =================================================================== */
    public function reorderImages(Request $request, Package $package)
    {
        $validated = $request->validate([
            'ordered_ids'   => 'required|array',
            'ordered_ids.*' => 'exists:package_images,id',
        ]);

        // مطمئن شویم همه‌ی IDها متعلق به همین پکیج هستند
        $validIds = $package->images()->pluck('id')->toArray();
        $orderedIds = array_filter($validated['ordered_ids'], fn ($id) => in_array($id, $validIds));

        $this->imageService->updateOrder(array_values($orderedIds));

        return response()->json(['success' => true]);
    }

    private function getStatuses(): array
    {
        return [
            'draft'    => 'پیش‌نویس',
            'active'   => 'منتشر شده',
            'archived' => 'آرشیو شده',
        ];
    }

    private function getCategories(): array
    {
        return [
            'shop'         => 'فروشگاه',
            'payment'      => 'پرداخت',
            'notification' => 'اعلان',
            'seo'          => 'سئو',
            'blog'         => 'بلاگ',
            'utility'      => 'ابزار',
            'theme'        => 'قالب',
            'other'        => 'سایر',
        ];
    }
}
