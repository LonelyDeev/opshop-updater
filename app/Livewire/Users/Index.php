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

    /** @var array<string, mixed> */
    public array $form = [];

    public bool $showModal = false;
    public ?int $editingId = null;
    public ?int $deleteId = null;

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

    #[Computed]
    public function records()
    {
        return User::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->role, fn ($q) => $q->where('role', $this->role))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at')
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

        $user->delete();
        $this->deleteId = null;
        $this->toast('کاربر با موفقیت حذف شد.');
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
