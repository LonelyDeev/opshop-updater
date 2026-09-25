<?php

namespace App\Livewire\Updates;

use App\Livewire\Concerns\WithToasts;
use App\Models\Project;
use App\Models\Update;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('آپدیت‌ها')]
class Index extends Component
{
    use WithPagination, WithFileUploads, WithToasts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    #[Url]
    public int $project_id = 0;

    /** مرتب‌سازی — پیش‌فرض همان ترتیب قبلی صفحه (جدیدترین) است */
    #[Url]
    public string $sort = 'newest';

    /** @var array<string, mixed> */
    public array $form = [];

    public bool $showModal = false;
    public ?int $editingId = null;
    public ?int $deleteId = null;

    /** @var array<int,int> */
    public array $selected = [];

    public bool $selectAll = false;

    public bool $showBulkModal = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedProjectId(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function records()
    {
        return Update::query()
            ->with('project')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('version', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->project_id, fn ($q) => $q->where('project_id', $this->project_id))
            ->when($this->sort === 'newest', fn ($q) => $q->latest())
            ->when($this->sort === 'oldest', fn ($q) => $q->oldest())
            ->paginate(12);
    }

    #[Computed]
    public function projects()
    {
        return Project::query()
            ->active()
            ->orderBy('name')
            ->get();
    }

    /* ---------------------------------------------------------------- */
    /*  CRUD (ported from Back/UpdateController)                         */
    /* ---------------------------------------------------------------- */

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = [
            'title' => '',
            'version' => '',
            'project_id' => '',
            'description' => '',
            'type' => 'minor',
            'status' => 'draft',
            'release_date' => '',
            'is_mandatory' => false,
            'download_link' => '',
            'file' => null,
        ];
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $update = Update::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $update->id;
        $this->form = [
            'title' => $update->title ?? '',
            'version' => $update->version ?? '',
            'project_id' => (string) $update->project_id,
            'description' => $update->description ?? '',
            'type' => $update->type ?? 'minor',
            'status' => $update->status ?? 'draft',
            'release_date' => $update->release_date?->format('Y-m-d') ?? '',
            'is_mandatory' => (bool) $update->is_mandatory,
            'download_link' => $update->download_link ?? '',
            'file' => null,
        ];
        $this->showModal = true;
    }

    public function save(): void
    {
        // empty string → null so «nullable|date» passes when input is blank
        $this->form['release_date'] = trim((string) ($this->form['release_date'] ?? '')) ?: null;

        $validated = $this->validate();
        $data = $validated['form'];

        /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null $file */
        $file = $data['file'] ?? null;
        unset($data['file'], $data['download_link'], $data['file_size']);

        $data['is_mandatory'] = (bool) ($this->form['is_mandatory'] ?? false);

        if ($file) {
            // حذف فایل قدیمی اگر از نوع ذخیره شده در دیسک باشد (پورت از update())
            if ($this->editingId
                && ($previous = Update::find($this->editingId))
                && $previous->download_link
                && ! filter_var($previous->download_link, FILTER_VALIDATE_URL)
                && Storage::disk('local')->exists($previous->download_link)) {
                Storage::disk('local')->delete($previous->download_link);
            }

            $filename = 'update_' . time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('updates', $filename, 'local');

            if (! $path || ! Storage::disk('local')->exists($path)) {
                $this->toast('فایل ذخیره نشد.', 'error');

                return;
            }

            $data['download_link'] = $path;
            $data['file_size'] = $this->formatFileSize($file->getSize());
        } elseif ($this->editingId) {
            $existing = Update::find($this->editingId);

            if (blank($this->form['download_link'] ?? null)) {
                // فایل با دکمه «حذف فایل» حذف شده است
                if ($existing?->download_link
                    && ! filter_var($existing->download_link, FILTER_VALIDATE_URL)
                    && Storage::disk('local')->exists($existing->download_link)) {
                    Storage::disk('local')->delete($existing->download_link);
                }
                $data['download_link'] = null;
                $data['file_size'] = null;
            } else {
                $data['download_link'] = $existing?->download_link;
                $data['file_size'] = $existing?->file_size;
            }
        }

        if ($this->editingId) {
            Update::findOrFail($this->editingId)->update($data);
            $this->toast('آپدیت با موفقیت به‌روزرسانی شد.');
        } else {
            Update::create($data);
            $this->toast('آپدیت با موفقیت ایجاد شد.');
        }

        $this->form['file'] = null;
        $this->showModal = false;
    }

    public function removeFile(): void
    {
        $this->form['download_link'] = '';
    }

    public function delete(): void
    {
        $update = Update::findOrFail($this->deleteId ?? 0);

        // حذف فایل مرتبط از دیسک local (پورت از destroy())
        if ($update->download_link && Storage::disk('local')->exists($update->download_link)) {
            Storage::disk('local')->delete($update->download_link);
        }

        $title = $update->title;
        $update->delete();
        $this->deleteId = null;
        $this->toast("آپدیت «{$title}» حذف شد.");
    }

    /* ---------------------------------------------------------------- */
    /*  حذف چندتایی                                                      */
    /* ---------------------------------------------------------------- */

    public function updatedSelectAll(bool $value): void
    {
        $ids = $this->records()->pluck('id')->all();

        $this->selected = $value
            ? array_values(array_unique(array_merge($this->selected, $ids)))
            : array_values(array_diff($this->selected, $ids));
    }

    public function toggleSelect(int $id): void
    {
        $this->selected = in_array($id, $this->selected)
            ? array_values(array_diff($this->selected, [$id]))
            : array_values(array_merge($this->selected, [$id]));

        // همگام‌سازی چک‌باکس سربرگ با وضعیت صفحه فعلی
        $pageIds = $this->records()->pluck('id')->all();
        $this->selectAll = $pageIds !== [] && array_diff($pageIds, $this->selected) === [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function confirmBulkDelete(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->showBulkModal = true;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            $this->showBulkModal = false;

            return;
        }

        $count = 0;
        foreach (Update::whereIn('id', $this->selected)->get() as $update) {
            // حذف فایل مرتبط از دیسک local (همان منطق حذف تکی)
            if ($update->download_link && Storage::disk('local')->exists($update->download_link)) {
                Storage::disk('local')->delete($update->download_link);
            }

            $update->delete();
            $count++;
        }

        $this->clearSelection();
        $this->showBulkModal = false;
        $this->toast(fa_num($count) . ' آپدیت انتخاب‌شده حذف شد.');
    }

    /** @return array<string, array<int, string>|string> */
    protected function rules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.version' => ['required', 'string', 'max:50'],
            'form.project_id' => ['required', 'integer', 'exists:projects,id'],
            'form.description' => ['required', 'string'],
            'form.type' => ['required', 'in:major,minor,patch'],
            'form.status' => ['required', 'in:draft,active,archived'],
            'form.release_date' => ['nullable', 'date'],
            'form.file' => ['nullable', 'file', 'mimes:zip,rar,tar,gz', 'max:307200'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.title.required' => 'عنوان آپدیت الزامی است.',
            'form.version.required' => 'شماره نسخه الزامی است.',
            'form.version.max' => 'شماره نسخه حداکثر ۵۰ کاراکتر است.',
            'form.project_id.required' => 'انتخاب پروژه الزامی است.',
            'form.project_id.exists' => 'پروژه انتخاب‌شده معتبر نیست.',
            'form.description.required' => 'توضیحات آپدیت الزامی است.',
            'form.type.in' => 'نوع آپدیت معتبر نیست.',
            'form.status.in' => 'وضعیت آپدیت معتبر نیست.',
            'form.release_date.date' => 'تاریخ انتشار معتبر نیست.',
            'form.file.file' => 'فایل ارسالی معتبر نیست.',
            'form.file.mimes' => 'فرمت فایل باید یکی از zip، rar، tar یا gz باشد.',
            'form.file.max' => 'حجم فایل حداکثر ۳۰۰ مگابایت است.',
        ];
    }

    /**
     * پورت مستقیم از UpdateController::formatFileSize
     */
    private function formatFileSize($bytes, $decimals = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
    }

    public function render()
    {
        return view('livewire.updates.index');
    }
}
