<?php

namespace App\Livewire\Projects;

use App\Livewire\Concerns\WithToasts;
use App\Models\Project;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

#[Layout('components.layouts.app')]
#[Title('پروژه‌ها')]
class Index extends Component
{
    use WithPagination, WithToasts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    /** @var array<string, mixed> */
    public array $form = [];

    public bool $showModal = false;
    public ?int $editingId = null;
    public ?int $deleteId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function records()
    {
        return Project::query()
            ->withCount(['updates', 'packages'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(12);
    }

    /* ---------------------------------------------------------------- */
    /*  CRUD                                                             */
    /* ---------------------------------------------------------------- */

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = ['name' => '', 'slug' => '', 'description' => '', 'repository_url' => '', 'status' => 'active'];
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $project->id;
        $this->form = $project->only(['name', 'slug', 'description', 'repository_url', 'status']);
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if (blank($validated['form']['slug'] ?? null)) {
            $validated['form']['slug'] = Str::slug($validated['form']['name']);
        }

        if ($this->editingId) {
            Project::findOrFail($this->editingId)->update($validated['form']);
            $this->toast('پروژه با موفقیت به‌روزرسانی شد.');
        } else {
            Project::create($validated['form']);
            $this->toast('پروژه جدید ایجاد شد.');
        }

        $this->showModal = false;
    }

    public function delete(): void
    {
        $project = Project::findOrFail($this->deleteId ?? 0);

        if ($project->updates()->count() > 0 || $project->packages()->count() > 0) {
            $this->toast('امکان حذف پروژه‌ای که آپدیت یا پکیج دارد وجود ندارد.', 'error');
            $this->deleteId = null;

            return;
        }

        $name = $project->name;
        $project->delete();
        $this->deleteId = null;
        $this->toast("پروژه «{$name}» حذف شد.");
    }

    /** @return array<string, array<int, string>|string> */
    protected function rules(): array
    {
        $slugUnique = $this->editingId
            ? 'unique:projects,slug,' . $this->editingId
            : 'unique:projects,slug';

        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.slug' => ['nullable', 'string', 'max:255', $slugUnique],
            'form.description' => ['nullable', 'string'],
            'form.repository_url' => ['nullable', 'url'],
            'form.status' => ['required', 'in:active,archived,pending'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.name.required' => 'نام پروژه الزامی است.',
            'form.slug.unique' => 'این نامک قبلاً استفاده شده است.',
            'form.repository_url.url' => 'آدرس مخزن معتبر نیست.',
            'form.status.required' => 'وضعیت را انتخاب کنید.',
        ];
    }

    public function render()
    {
        return view('livewire.projects.index');
    }
}
