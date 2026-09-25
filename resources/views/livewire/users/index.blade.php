<div class="space-y-6">
    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-black text-zinc-900 dark:text-zinc-50">کاربران پنل</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">مدیریت دسترسی کاربران مدیریت و وضعیت حساب‌ها.</p>
        </div>
        <x-btn icon="user-plus" wire:click="openCreate">کاربر جدید</x-btn>
    </div>

    {{-- filters --}}
    <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute start-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="جستجوی نام یا ایمیل…" class="input ps-10" />
        </div>
        <select wire:model.live="role" class="input sm:w-36">
            <option value="">همه نقش‌ها</option>
            <option value="admin">ادمین</option>
            <option value="operator">اپراتور</option>
        </select>
        <select wire:model.live="status" class="input sm:w-36">
            <option value="">همه وضعیت‌ها</option>
            <option value="active">فعال</option>
            <option value="inactive">غیرفعال</option>
        </select>
        <div wire:loading wire:target="search, role, status" class="flex items-center gap-2 text-xs text-brand-600 dark:text-brand-400">
            <x-icon name="loader" class="size-4 animate-spin" />
            در حال فیلتر…
        </div>
    </div>

    {{-- table --}}
    <div class="card overflow-hidden">
        @if($this->records->count())
            <div class="table-wrap ring-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>کاربر</th>
                            <th class="hidden sm:table-cell">نقش</th>
                            <th>وضعیت</th>
                            <th class="hidden md:table-cell">تاریخ ایجاد</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->records as $user)
                            <tr wire:key="user-{{ $user->id }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :name="$user->name" size="sm" />
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                                {{ $user->name }}
                                                @if($user->id === auth()->id())
                                                    <span class="ms-1 text-[10px] font-bold text-brand-600 dark:text-brand-400">(شما)</span>
                                                @endif
                                            </p>
                                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400" dir="ltr">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden sm:table-cell">
                                    @if($user->role === 'admin')
                                        <x-badge variant="primary" icon="shield-check">ادمین</x-badge>
                                    @else
                                        <x-badge variant="neutral" icon="user">اپراتور</x-badge>
                                    @endif
                                </td>
                                <td>
                                    <x-badge variant="{{ $user->status === 'active' ? 'success' : 'neutral' }}">
                                        {{ $user->status === 'active' ? 'فعال' : 'غیرفعال' }}
                                    </x-badge>
                                </td>
                                <td class="hidden text-sm text-zinc-500 md:table-cell">{{ verta_date($user->created_at) }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        @if($user->status === 'active')
                                            <button type="button" wire:click="toggleStatus({{ $user->id }})"
                                                    class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-amber-50 hover:text-amber-600 disabled:pointer-events-none disabled:opacity-40 dark:hover:bg-amber-500/10 dark:hover:text-amber-400"
                                                    title="{{ $user->id === auth()->id() ? 'تغییر وضعیت حساب خودتان ممکن نیست' : 'غیرفعال‌سازی' }}"
                                                    @if($user->id === auth()->id()) disabled @endif>
                                                <x-icon name="x-circle" class="size-4.5" />
                                            </button>
                                        @else
                                            <button type="button" wire:click="toggleStatus({{ $user->id }})"
                                                    class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600 disabled:pointer-events-none disabled:opacity-40 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400"
                                                    title="{{ $user->id === auth()->id() ? 'تغییر وضعیت حساب خودتان ممکن نیست' : 'فعال‌سازی' }}"
                                                    @if($user->id === auth()->id()) disabled @endif>
                                                <x-icon name="check-circle" class="size-4.5" />
                                            </button>
                                        @endif
                                        <button type="button" wire:click="openEdit({{ $user->id }})"
                                                class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400"
                                                title="ویرایش">
                                            <x-icon name="pencil" class="size-4.5" />
                                        </button>
                                        @if($user->id === auth()->id())
                                            <button type="button" disabled
                                                    class="cursor-not-allowed rounded-lg p-2 text-zinc-300 opacity-40 dark:text-zinc-600"
                                                    title="حذف حساب خودتان ممکن نیست">
                                                <x-icon name="trash" class="size-4.5" />
                                            </button>
                                        @else
                                            <button type="button" wire:click="$set('deleteId', {{ $user->id }})"
                                                    class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                                    title="حذف">
                                                <x-icon name="trash" class="size-4.5" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                {{ $this->records->links() }}
            </div>
        @else
            <x-empty icon="users" title="کاربری یافت نشد" description="کاربر جدیدی اضافه کنید یا فیلترها را تغییر دهید.">
                <x-btn variant="soft" icon="user-plus" wire:click="openCreate">ایجاد کاربر</x-btn>
            </x-empty>
        @endif
    </div>

    {{-- create / edit modal --}}
    <x-modal wire:model="showModal" :title="$editingId ? 'ویرایش کاربر' : 'کاربر جدید'" size="md">
        <form wire:submit="save" class="space-y-5">
            <x-field label="نام" required>
                <x-input wire:model="form.name" placeholder="مثلاً سارا محمدی" icon="user" :class="$errors->has('form.name') ? 'input-error' : ''" />
            </x-field>

            <x-field label="ایمیل" required>
                <x-input wire:model="form.email" placeholder="user@panel.test" icon="mail" dir="ltr" :class="$errors->has('form.email') ? 'input-error' : ''" />
            </x-field>

            <x-field label="رمز عبور" :required="$editingId ? false : true" hint="{{ $editingId ? 'در صورت خالی گذاشتن، رمز عبور فعلی حفظ می‌شود.' : 'حداقل ۶ کاراکتر.' }}">
                <x-input wire:model="form.password" type="password" placeholder="••••••••" icon="lock" dir="ltr" :class="$errors->has('form.password') ? 'input-error' : ''" />
            </x-field>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-field label="نقش" required>
                    <x-select wire:model="form.role" :options="[
                        'admin' => 'ادمین',
                        'operator' => 'اپراتور',
                    ]" :class="$errors->has('form.role') ? 'input-error' : ''" />
                </x-field>

                <x-field label="وضعیت" required>
                    <x-select wire:model="form.status" :options="[
                        'active' => 'فعال',
                        'inactive' => 'غیرفعال',
                    ]" :class="$errors->has('form.status') ? 'input-error' : ''" />
                </x-field>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-btn variant="secondary" wire:click="$set('showModal', false)">انصراف</x-btn>
                <x-btn type="submit" icon="save" :loading="true">ذخیره</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- delete confirm --}}
    <x-modal wire:model="deleteId" title="حذف کاربر" size="sm">
        @if($deleteId)
            @php($target = \App\Models\User::find($deleteId))
            <div class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl bg-rose-50 p-4 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20">
                    <x-icon name="alert-triangle" class="size-6 shrink-0" />
                    <p class="text-sm leading-6">آیا از حذف کاربر «<strong>{{ $target?->name }}</strong>» مطمئن هستید؟ این عمل قابل بازگشت نیست.</p>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-btn variant="secondary" wire:click="$set('deleteId', null)">انصراف</x-btn>
                    <x-btn variant="danger" icon="trash" wire:click="delete" :loading="true">حذف قطعی</x-btn>
                </div>
            </div>
        @endif
    </x-modal>
</div>
