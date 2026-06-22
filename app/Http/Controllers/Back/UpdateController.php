<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Update as UpdateModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{
    /**
     * نمایش لیست آپدیت‌ها
     */
    public function index()
    {
        $updates = UpdateModel::latest()->paginate(15);
        return view('back.updates.index', compact('updates'));
    }

    /**
     * فرم ایجاد آپدیت جدید
     */
    public function create()
    {
        $types = UpdateModel::getTypes();
        $statuses = UpdateModel::getStatuses();
        $projects=Project::latest()->active()->get();
        return view('back.updates.create', compact('types', 'statuses', 'projects'));
    }

    /**
     * ذخیره آپدیت جدید
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'version' => 'required|string|max:50',
            'project_id' => 'required|exists:projects,id',
            'description' => 'required|string',
            'type' => 'required|in:major,minor,patch',
            'status' => 'required|in:draft,active,archived',
            'download_link' => 'nullable|url|max:500',
            'release_date' => 'nullable|date',
            'is_mandatory' => 'boolean',
            'file' => 'nullable|file|mimes:zip,rar,tar,gz|max:102400',
        ]);

        // آپلود فایل در صورت وجود
        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');

                // بررسی وجود خطا در آپلود
                if (!$file->isValid()) {
                    return back()->with('error', 'خطا در آپلود فایل: ' . $file->getErrorMessage());
                }

                $filename = 'update_' . time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('updates', $filename, 'local');

                // بررسی ذخیره شدن
                if (!$path) {
                    return back()->with('error', 'فایل ذخیره نشد.');
                }

                // بررسی وجود فایل در دیسک
                if (!Storage::disk('local')->exists($path)) {
                    return back()->with('error', 'فایل آپلود شد اما در دیسک وجود ندارد.');
                }

                $validated['download_link'] = $path;

            } catch (\Exception $e) {
                return back()->with('error', 'خطا: ' . $e->getMessage());
            }
        }


        $validated['is_mandatory'] = $request->has('is_mandatory');

        UpdateModel::create($validated);

        return redirect()->route('admin.updates.index')
            ->with('success', 'آپدیت با موفقیت ایجاد شد.');
    }

    /**
     * نمایش جزئیات یک آپدیت
     */
    public function show($id)
    {
        $update = UpdateModel::findOrFail($id);
        return view('back.updates.show', compact('update'));
    }

    /**
     * فرم ویرایش آپدیت
     */
    public function edit($id)
    {
        $update = UpdateModel::findOrFail($id);
        $types = UpdateModel::getTypes();
        $statuses = UpdateModel::getStatuses();
        return view('back.updates.edit', compact('update', 'types', 'statuses'));
    }

    /**
     * به‌روزرسانی آپدیت
     */
    public function update(Request $request, $id)
    {
        $update = UpdateModel::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'version' => 'required|string|max:50',
            'description' => 'required|string',
            'type' => 'required|in:major,minor,patch',
            'status' => 'required|in:draft,active,archived',
            'download_link' => 'nullable|url|max:500',
            'release_date' => 'nullable|date',
            'is_mandatory' => 'boolean',
            'file' => 'nullable|file|mimes:zip,rar,tar,gz|max:102400',
        ]);

        // آپلود فایل جدید در صورت وجود
        if ($request->hasFile('file')) {
            // حذف فایل قدیمی
            if ($update->download_link) {
                $oldPath = str_replace('/storage/', '', $update->download_link);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('updates', $filename, 'public');
            $validated['download_link'] = Storage::url($path);
        }

        $validated['is_mandatory'] = $request->has('is_mandatory');

        $update->update($validated);

        return redirect()->route('admin.updates.index')
            ->with('success', 'آپدیت با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف آپدیت
     */
    public function destroy($id)
    {
        $update = UpdateModel::findOrFail($id);

        // حذف فایل مرتبط
        if ($update->download_link) {
            $path = str_replace('/storage/', '', $update->download_link);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $update->delete();

        return redirect()->route('admin.updates.index')
            ->with('success', 'آپدیت با موفقیت حذف شد.');
    }
}
