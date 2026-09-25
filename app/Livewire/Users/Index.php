<?php

namespace App\Livewire\Users;

use App\Livewire\Concerns\WithToasts;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('کاربران پنل')]
class Index extends Component
{
    use WithPagination, WithToasts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

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

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
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
        return User::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->role, fn ($q) => $q->where('role', $this->role))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->sort === 'newest', fn ($q) => $q->orderByDesc('created_at'))
            ->when($this->sort === 'oldest', fn ($q) => $q->orderBy('created_at'))
            ->when($this->sort === 'name_asc', fn ($q) => $q->orderBy('name'))
            ->paginate(12);
    }

    /* ---------------------------------------------------------------- */
    /*  CRUD (ported from old UserController)                             */
    /* ---------------------------------------------------------------- */

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = ['name' => '', 'email' => '', 'password' => '', 'role' => 'operator', 'status' => 'active'];
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $user->id;
        $this->form = ['name' => $user->name, 'email' => $user->email, 'password' => '', 'role' => $user->role, 'status' => $user->status];
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $data = $validated['form'];

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);

            // خالی بودن رمز = بدون تغییر (مثل کنترلر قدیمی)
            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            $user->update($data);
            $this->toast('اطلاعات کاربر با موفقیت به‌روزرسانی شد.');
        } else {
            User::create($data);
            $this->toast('کاربر جدید با موفقیت ایجاد شد.');
        }

        $this->showModal = false;
    }

    public function delete(): void
    {
        $user = User::findOrFail($this->deleteId ?? 0);

        // جلوگیری از حذف خودتان
        if ($user->id === auth()->id()) {
            $this->toast('شما نمی‌توانید حساب کاربری خودتان را حذف کنید.', 'error');
            $this->deleteId = null;

            return;
        }

        // جلوگیری از حذف آخرین مدیر
        if ($user->role === 'admin' && User::where('role', 'admin')->whereKeyNot($user->id)->doesntExist()) {
            $this->toast('حداقل یک مدیر باید باقی بماند.', 'error');
            $this->deleteId = null;

            return;
        }

        $user->delete();
        $this->deleteId = null;
        $this->toast('کاربر با موفقیت حذف شد.');
    }

    /* ---------------------------------------------------------------- */
    /*  حذف چندتایی                                                      */
    /* ---------------------------------------------------------------- */

    public function updatedSelectAll(bool $value): void
    {
        // حساب کاربر جاری هرگز انتخاب نمی‌شود
        $ids = array_values(array_diff($this->records()->pluck('id')->all(), [auth()->id()]));

        $this->selected = $value
            ? array_values(array_unique(array_merge($this->selected, $ids)))
            : array_values(array_diff($this->selected, $ids));
    }

    public function toggleSelect(int $id): void
    {
        if ($id === auth()->id()) {
            return; // حساب خودتان قابل انتخاب نیست
        }

        $this->selected = in_array($id, $this->selected)
            ? array_values(array_diff($this->selected, [$id]))
            : array_values(array_merge($this->selected, [$id]));

        // همگام‌سازی چک‌باکس سربرگ با وضعیت صفحه فعلی
        $pageIds = array_values(array_diff($this->records()->pluck('id')->all(), [auth()->id()]));
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

        $records = User::whereIn('id', $this->selected)->get();

        // مدیرانی که پس از این حذف باقی می‌مانند (خارج از انتخاب + خودِ کاربر جاری)
        $deletableIds = $records->pluck('id')->diff([auth()->id()])->all();
        $remainingAdmins = User::where('role', 'admin')
            ->whereNotIn('id', $deletableIds)
            ->count();

        $count = 0;
        $keptAdmin = false;

        foreach ($records as $user) {
            // حساب مدیر فعلی قابل حذف نیست
            if ($user->id === auth()->id()) {
                continue;
            }

            // حداقل یک مدیر باید باقی بماند
            if ($user->role === 'admin' && $remainingAdmins === 0 && ! $keptAdmin) {
                $keptAdmin = true;
                continue;
            }

            $user->delete();
            $count++;
        }

        $this->clearSelection();
        $this->showBulkModal = false;

        if ($keptAdmin) {
            $this->toast(fa_num($count) . ' کاربر حذف شد؛ حداقل یک مدیر باید باقی بماند.', 'warning');
        } else {
            $this->toast(fa_num($count) . ' کاربر انتخاب‌شده حذف شد.');
        }
    }

    // تغییر سریع وضعیت (فعال/غیرفعال)
    public function toggleStatus(int $id): void
    {
        $user = User::findOrFail($id);

        // جلوگیری از تغییر وضعیت خودتان
        if ($user->id === auth()->id()) {
            $this->toast('شما نمی‌توانید وضعیت حساب کاربری خودتان را تغییر دهید.', 'error');

            return;
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        $this->toast($newStatus === 'active' ? 'کاربر فعال شد.' : 'کاربر غیرفعال شد.');
    }

    /** @return array<string, array<int, string>|string> */
    protected function rules(): array
    {
        $emailUnique = $this->editingId
            ? 'unique:users,email,' . $this->editingId
            : 'unique:users,email';

        $password = $this->editingId
            ? ['nullable', 'string', 'min:6']
            : ['required', 'string', 'min:6'];

        return [
            'form.name'     => ['required', 'string', 'max:255'],
            'form.email'    => ['required', 'email', 'max:255', $emailUnique],
            'form.password' => $password,
            'form.role'     => ['required', 'in:admin,operator'],
            'form.status'   => ['required', 'in:active,inactive'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.name.required'     => 'نام کاربر الزامی است.',
            'form.email.required'    => 'ایمیل الزامی است.',
            'form.email.email'       => 'ایمیل معتبر نیست.',
            'form.email.unique'      => 'این ایمیل قبلاً استفاده شده است.',
            'form.password.required' => 'رمز عبور الزامی است.',
            'form.password.min'      => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
            'form.role.required'     => 'نقش کاربر را انتخاب کنید.',
            'form.status.required'   => 'وضعیت را انتخاب کنید.',
        ];
    }

    public function render()
    {
        return view('livewire.users.index');
    }
}
