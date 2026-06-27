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
            'title'         => 'required|string|max:255',
            'version'       => 'required|string|max:50',
            'project_id'    => 'required|exists:projects,id',
            'description'   => 'required|string',
            'type'          => 'required|in:major,minor,patch',
            'status'        => 'required|in:draft,active,archived',
            'download_link' => 'nullable|url|max:500',
            'release_date'  => 'nullable|date',
            'is_mandatory'  => 'boolean',
            'file'          => 'nullable|file|mimes:zip,rar,tar,gz|max:307200',
        ]);

        // آپلود فایل در صورت وجود
        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');

                if (!$file->isValid()) {
                    return response()->json([
                        'message' => 'خطا در آپلود فایل: ' . $file->getErrorMessage(),
                    ], 500);
                }

                $filename = 'update_' . time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('updates', $filename, 'local');

                if (!$path) {
                    return response()->json(['message' => 'فایل ذخیره نشد.'], 500);
                }

                if (!Storage::disk('local')->exists($path)) {
                    return response()->json(['message' => 'فایل آپلود شد اما در دیسک وجود ندارد.'], 500);
                }

                $validated['download_link'] = $path;

            } catch (\Exception $e) {
                return response()->json(['message' => 'خطا: ' . $e->getMessage()], 500);
            }
        }

        $validated['is_mandatory'] = $request->has('is_mandatory');

        UpdateModel::create($validated);

        return response()->json([
            'message'  => 'آپدیت با موفقیت ایجاد شد.',
            'redirect' => route('admin.updates.index'),
        ], 201);
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
            'title'         => 'required|string|max:255',
            'version'       => 'required|string|max:50',
            'project_id'    => 'required|exists:projects,id',
            'description'   => 'required|string',
            'type'          => 'required|in:major,minor,patch',
            'status'        => 'required|in:draft,active,archived',
            'download_link' => 'nullable|url|max:500',
            'release_date'  => 'nullable|date',
            'is_mandatory'  => 'boolean',
            'file'          => 'nullable|file|mimes:zip,rar,tar,gz|max:307200', // 300MB
        ]);

        // آپلود فایل جدید در صورت وجود
        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');

                if (!$file->isValid()) {
                    return response()->json([
                        'message' => 'خطا در آپلود فایل: ' . $file->getErrorMessage(),
                    ], 500);
                }

                // حذف فایل قدیمی اگر از نوع ذخیره شده در دیسک باشد
                if ($update->download_link && !filter_var($update->download_link, FILTER_VALIDATE_URL)) {
                    // اگر فایل قبلی در دیسک ذخیره شده بود
                    if (Storage::disk('local')->exists($update->download_link)) {
                        Storage::disk('local')->delete($update->download_link);
                    }
                }

                $filename = 'update_' . time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('updates', $filename, 'local');

                if (!$path) {
                    return response()->json(['message' => 'فایل ذخیره نشد.'], 500);
                }

                if (!Storage::disk('local')->exists($path)) {
                    return response()->json(['message' => 'فایل آپلود شد اما در دیسک وجود ندارد.'], 500);
                }

                $validated['download_link'] = $path;

            } catch (\Exception $e) {
                return response()->json(['message' => 'خطا: ' . $e->getMessage()], 500);
            }
        }

        $validated['is_mandatory'] = $request->has('is_mandatory');

        $update->update($validated);

        // اگر درخواست Ajax بود، پاسخ JSON برمی‌گردانیم
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message'  => 'آپدیت با موفقیت به‌روزرسانی شد.',
                'redirect' => route('admin.updates.index'),
            ], 200);
        }

        // برای درخواست‌های عادی
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
            if (Storage::disk('local')->exists($update->download_link)) {
                Storage::disk('local')->delete($update->download_link);
            }
        }

        $update->delete();

        return redirect()->route('admin.updates.index')
            ->with('success', 'آپدیت با موفقیت حذف شد.');
    }
}
