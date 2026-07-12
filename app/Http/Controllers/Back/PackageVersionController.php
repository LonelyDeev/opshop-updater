<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PackageVersionController extends Controller
{
    public function index(Package $package)
    {
        $versions = $package->versions()->latest()->paginate(15);
        return view('back.packages.versions.index', compact('package', 'versions'));
    }

    public function create(Package $package)
    {
        $types = $this->getTypes();
        $statuses = $this->getStatuses();
        return view('back.packages.versions.create', compact('package', 'types', 'statuses'));
    }

    public function store(Request $request, Package $package)
    {

        $validated = $request->validate([
            'version'            => 'required|string|max:50|unique:package_versions,version,NULL,id,package_id,' . $package->id,
            'type'               => 'required|in:major,minor,patch',
            'changelog'          => 'nullable|string',
            'what_added'         => 'nullable|string',
            'what_changed'       => 'nullable|string',
            'what_fixed'         => 'nullable|string',
            'file'               => 'required|file|mimes:zip|max:307200', // 300MB
            'min_project_version'    => 'nullable|string|max:20',
            'min_php_version'    => 'nullable|string|max:20',
            'min_laravel_version'=> 'nullable|string|max:20',
            'dependencies'       => 'nullable|array',
            'dependencies.*.slug'    => 'nullable|string',
            'dependencies.*.version' => 'nullable|string',
            'is_mandatory'       => 'boolean',
            'status'             => 'required|in:draft,active,archived',
            'release_date'       => 'nullable|date',
        ]);

        // آپلود فایل ZIP
        $file = $request->file('file');
        $filename = $package->slug . '_v' . Str::slug($validated['version']) . '_' . time() . '.zip';
        $path = $file->storeAs('packages', $filename, 'local');

        $absolutePath = Storage::disk('local')->path($path);
        $hash = hash_file('sha256', $absolutePath);
        $fileSize = $this->formatFileSize(filesize($absolutePath));

        // پردازش dependencies
        $dependencies = null;
        if (!empty($validated['dependencies'])) {
            // فیلتر کردن آیتم‌های خالی
            $filtered = array_filter($validated['dependencies'], function($dep) {
                return !empty($dep['slug']) && !empty($dep['version']);
            });

            // تبدیل به فرمت مورد نظر
            if (!empty($filtered)) {
                $dependencies = [];
                foreach ($filtered as $dep) {
                    $dependencies[$dep['slug']] = $dep['version'];
                }
            }
        }

        $version = PackageVersion::create([
            'package_id'         => $package->id,
            'version'            => $validated['version'],
            'type'               => $validated['type'],
            'changelog'          => $validated['changelog'] ?? null,
            'what_added'         => $validated['what_added'] ?? null,
            'what_changed'       => $validated['what_changed'] ?? null,
            'what_fixed'         => $validated['what_fixed'] ?? null,
            'file_path'          => $path,
            'file_size'          => $fileSize,
            'file_hash'          => $hash,
            'min_project_version'    => $validated['min_project_version'] ?? null,
            'min_php_version'    => $validated['min_php_version'] ?? null,
            'min_laravel_version'=> $validated['min_laravel_version'] ?? null,
            'dependencies'       => $dependencies,
            'is_mandatory'       => $request->has('is_mandatory'),
            'status'             => $validated['status'],
            'release_date'       => $validated['release_date'] ?? now(),
        ]);

        return redirect()->route('admin.packages.versions.index', $package)
            ->with('success', "نسخه {$version->version} با موفقیت آپلود شد.");
    }

    public function show(Package $package, PackageVersion $version)
    {
        return view('back.packages.versions.show', compact('package', 'version'));
    }

    public function edit(Package $package, PackageVersion $version)
    {
        $types = $this->getTypes();
        $statuses = $this->getStatuses();
        return view('back.packages.versions.edit', compact('package', 'version', 'types', 'statuses'));
    }

    public function update(Request $request, Package $package, PackageVersion $version)
    {
        $validated = $request->validate([
            'version'            => 'required|string|max:50|unique:package_versions,version,' . $version->id . ',id,package_id,' . $package->id,
            'type'               => 'required|in:major,minor,patch',
            'changelog'          => 'nullable|string',
            'what_added'         => 'nullable|string',
            'what_changed'       => 'nullable|string',
            'what_fixed'         => 'nullable|string',
            'min_project_version'    => 'nullable|string|max:20',
            'min_php_version'    => 'nullable|string|max:20',
            'min_laravel_version'=> 'nullable|string|max:20',
            'dependencies'       => 'nullable|array',
            'dependencies.*.slug'    => 'nullable|string',
            'dependencies.*.version' => 'nullable|string',
            'is_mandatory'       => 'boolean',
            'status'             => 'required|in:draft,active,archived',
            'release_date'       => 'nullable|date',
            'file'               => 'nullable|file|mimes:zip|max:307200',
        ]);

        // در صورت آپلود فایل جدید
        if ($request->hasFile('file')) {
            // حذف فایل قدیمی
            if ($version->file_path && Storage::disk('local')->exists($version->file_path)) {
                Storage::disk('local')->delete($version->file_path);
            }

            $file = $request->file('file');
            $filename = $package->slug . '_v' . Str::slug($validated['version']) . '_' . time() . '.zip';
            $path = $file->storeAs('packages', $filename, 'local');

            $absolutePath = Storage::disk('local')->path($path);
            $validated['file_path'] = $path;
            $validated['file_hash'] = hash_file('sha256', $absolutePath);
            $validated['file_size'] = $this->formatFileSize(filesize($absolutePath));
        }

        // پردازش dependencies
        $dependencies = null;
        if (!empty($validated['dependencies'])) {
            $dependencies = [];
            foreach ($validated['dependencies'] as $dep) {
                if (!empty($dep['slug'])) {
                    $dependencies[$dep['slug']] = $dep['version'];
                }
            }
        }
        $validated['dependencies'] = $dependencies;
        $validated['is_mandatory'] = $request->has('is_mandatory');

        $version->update($validated);

        return redirect()->route('admin.packages.versions.index', $package)
            ->with('success', 'نسخه با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Package $package, PackageVersion $version)
    {
        // حذف فایل
        if ($version->file_path && Storage::disk('local')->exists($version->file_path)) {
            Storage::disk('local')->delete($version->file_path);
        }

        $version->delete();

        return redirect()->route('admin.packages.versions.index', $package)
            ->with('success', 'نسخه حذف شد.');
    }

    /* ---------------- Helpers ---------------- */

    private function getTypes(): array
    {
        return [
            'major' => 'Major (تغییرات بزرگ)',
            'minor' => 'Minor (قابلیت جدید)',
            'patch' => 'Patch (رفع باگ)',
        ];
    }

    private function getStatuses(): array
    {
        return [
            'draft'    => 'پیش‌نویس',
            'active'   => 'منتشر شده',
            'archived' => 'آرشیو شده',
        ];
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor] ?? 'B');
    }
}
