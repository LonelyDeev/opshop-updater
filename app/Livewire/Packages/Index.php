<?php

namespace App\Livewire\Packages;

use App\Livewire\Concerns\WithToasts;
use App\Models\Package;
use App\Models\Project;
use App\Services\ImageUploadService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('پکیج‌ها')]
class Index extends Component
{
    use WithPagination, WithToasts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $project_id = '';

    /** مرتب‌سازی — پیش‌فرض همان ترتیب قبلی صفحه (جدیدترین) است */
    #[Url]
    public string $sort = 'newest';

    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $deleteId = null;

    /** @var array<int,int> */
    public array $selected = [];

    public bool $selectAll = false;

    public bool $showBulkModal = false;

    /** @var array<string, mixed> */
    public array $form = [];

    public const CATEGORIES = [
        'shop'         => 'فروشگاه',
        'payment'      => 'پرداخت',
        'notification' => 'اعلان',
        'seo'          => 'سئو',
        'blog'         => 'بلاگ',
        'utility'      => 'ابزار',
        'theme'        => 'قالب',
        'other'        => 'سایر',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
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
        return Package::query()
            ->with(['project:id,name', 'latestVersion'])
            ->withCount('activeVersions')
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->project_id !== '', fn ($q) => $q->where('project_id', (int) $this->project_id))
            ->when($this->sort === 'newest', fn ($q) => $q->latest())
            ->when($this->sort === 'oldest', fn ($q) => $q->oldest())
            ->when($this->sort === 'name_asc', fn ($q) => $q->orderBy('name'))
            ->when($this->sort === 'downloads_desc', fn ($q) => $q->orderByDesc('downloads_count'))
            ->when($this->sort === 'purchases_desc', fn ($q) => $q->orderByDesc('purchases_count'))
            ->paginate(12);
    }

    #[Computed]
    public function projects()
    {
        return Project::query()->orderBy('name')->get(['id', 'name']);
    }

    /* ---------------------------------------------------------------- */
    /*  CRUD                                                             */
    /* ---------------------------------------------------------------- */

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = [
            'project_id'        => '',
            'name'              => '',
            'slug'              => '',
            'short_description' => '',
            'description'       => '',
            'author'            => '',
            'category'          => '',
            'is_free'           => false,
            'default_price'     => 0,
            'status'            => 'draft',
            'module_name'       => '',
            'sort_order'        => 0,
        ];
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $package = Package::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $package->id;
        $this->form = $package->only([
            'project_id', 'name', 'slug', 'short_description', 'description',
            'author', 'category', 'is_free', 'default_price', 'status',
            'module_name', 'sort_order',
        ]);
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->normalizeBlankables();
        $validated = $this->validate();

        $form = $validated['form'];

        // تولید slug اگر خالی باشد
        if (blank($form['slug'] ?? null)) {
            $form['slug'] = Str::slug($form['name']);
        }

        // تولید module_name اگر خالی باشد
        if (blank($form['module_name'] ?? null)) {
            $form['module_name'] = ucfirst(Str::camel($form['slug']));
        }

        // مقادیر پیش‌فرض
        $form['is_free'] = (bool) ($form['is_free'] ?? false);
        $form['default_price'] = (int) ($form['default_price'] ?? 0);
        $form['sort_order'] = (int) ($form['sort_order'] ?? 0);

        if ($this->editingId) {
            Package::findOrFail($this->editingId)->update($form);
            $this->toast('پکیج با موفقیت به‌روزرسانی شد.');
        } else {
            $package = Package::create($form);
            $this->toast('پکیج جدید ایجاد شد.');
            $this->redirect(route('admin.packages.show', $package), true);

            return;
        }

        $this->showModal = false;
    }

    public function delete(): void
    {
        $package = Package::with('images', 'versions')->findOrFail($this->deleteId ?? 0);

        $name = $package->name;
        $this->deletePackage($package);
        $this->deleteId = null;
        $this->toast("پکیج «{$name}» و تمام نسخه‌های آن حذف شد.");
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
        foreach (Package::with('images', 'versions')->whereIn('id', $this->selected)->get() as $package) {
            $this->deletePackage($package);
            $count++;
        }

        $this->clearSelection();
        $this->showBulkModal = false;
        $this->toast(fa_num($count) . ' پکیج انتخاب‌شده به‌همراه نسخه‌ها و لایسنس‌های آن‌ها حذف شد.');
    }

    /** حذف کامل پکیج: تصاویر، فایل‌های ZIP نسخه‌ها و خود رکورد */
    private function deletePackage(Package $package): void
    {
        $imageService = app(ImageUploadService::class);

        // حذف تصویر شاخص و گالری
        $imageService->deleteThumbnail($package);
        $imageService->deleteAllGalleryImages($package);

        // حذف فایل‌های ZIP نسخه‌ها
        foreach ($package->versions as $version) {
            if ($version->file_path && file_exists(storage_path('app/' . $version->file_path))) {
                @unlink(storage_path('app/' . $version->file_path));
            }
        }

        $package->delete();
    }

    /** تبدیل رشته‌های خالی به null تا ولیدیشن nullable درست کار کند. */
    private function normalizeBlankables(): void
    {
        foreach (['default_price', 'sort_order'] as $key) {
            $this->form[$key] = blank($this->form[$key] ?? null) ? null : $this->form[$key];
        }
    }

    /** @return array<string, array<int, string>|string> */
    protected function rules(): array
    {
        $slugUnique = $this->editingId
            ? 'unique:packages,slug,' . $this->editingId
            : 'unique:packages,slug';

        return [
            'form.project_id'        => ['required', 'exists:projects,id'],
            'form.name'              => ['required', 'string', 'max:255'],
            'form.slug'              => ['nullable', 'string', 'max:100', $slugUnique],
            'form.short_description' => ['nullable', 'string', 'max:255'],
            'form.description'       => ['nullable', 'string'],
            'form.author'            => ['nullable', 'string', 'max:100'],
            'form.category'          => ['nullable', 'string', 'max:50'],
            'form.is_free'           => ['nullable', 'boolean'],
            'form.default_price'     => ['nullable', 'integer', 'min:0'],
            'form.status'            => ['required', 'in:draft,active,archived'],
            'form.module_name'       => ['nullable', 'string', 'max:100'],
            'form.sort_order'        => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.project_id.required' => 'انتخاب پروژه الزامی است.',
            'form.name.required'        => 'نام پکیج الزامی است.',
            'form.slug.unique'          => 'این نامک قبلاً استفاده شده است.',
            'form.default_price.integer' => 'قیمت باید عدد باشد.',
            'form.status.required'      => 'وضعیت را انتخاب کنید.',
        ];
    }

    public function render()
    {
        return view('livewire.packages.index');
    }
}
