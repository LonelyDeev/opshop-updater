<div class="space-y-6">
    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-black text-zinc-900 dark:text-zinc-50">پروژه‌ها</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">مدیریت پروژه‌های پایه که آپدیت‌ها و پکیج‌هایشان منتشر می‌شود.</p>
        </div>
        <x-btn icon="plus" wire:click="openCreate">پروژه جدید</x-btn>
    </div>

    {{-- filters --}}
    <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute start-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="جستجوی نام یا نامک…" class="input ps-10" />
        </div>
        <select wire:model.live="status" class="input sm:w-44">
            <option value="">همه وضعیت‌ها</option>
            <option value="active">فعال</option>
            <option value="pending">در انتظار</option>
            <option value="archived">بایگانی شده</option>
        </select>
        <div class="relative sm:w-44">
            <x-icon name="arrow-up-down" class="pointer-events-none absolute start-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <select wire:model.live="sort" class="input ps-10" aria-label="ترتیب نمایش">
                <option value="newest">جدیدترین</option>
                <option value="oldest">قدیمی‌ترین</option>
                <option value="id_desc">شناسه (نزولی)</option>
                <option value="id_asc">شناسه (صعودی)</option>
                <option value="name_asc">بر اساس نام</option>
                <option value="name_desc">بر اساس نام (معکوس)</option>
            </select>
        </div>
        <div wire:loading wire:target="search, status, sort" class="flex items-center gap-2 text-xs text-brand-600 dark:text-brand-400">
            <x-icon name="loader" class="size-4 animate-spin" />
            در حال فیلتر…
        </div>
    </div>

    {{-- bulk toolbar --}}
    @if($selectedIds)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-rose-50 p-3 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:ring-rose-400/20">
            <div class="flex items-center gap-2 text-sm font-bold text-rose-700 dark:text-rose-400">
                <x-icon name="check-square" class="size-4.5" />
                {{ fa_num(count($selectedIds)) }} پروژه انتخاب شده است
            </div>
            <div class="flex items-center gap-2">
                <x-btn variant="secondary" size="sm" wire:click="clearSelection">انصراف از انتخاب</x-btn>
                <x-btn variant="danger" icon="trash" size="sm" wire:click="$set('confirmingBulkDelete', true)">حذف گروهی</x-btn>
            </div>
        </div>
    @endif

    {{-- table --}}
    <div class="card overflow-hidden">
        @if($this->records->count())
            <div class="table-wrap ring-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input type="checkbox" class="checkbox" aria-label="انتخاب همه"
                                       @if($this->allPageSelected()) checked @endif
                                       wire:click="toggleSelectAll" />
                            </th>
                            <th>پروژه</th>
                            <th class="hidden md:table-cell">نامک</th>
                            <th>وضعیت</th>
                            <th class="hidden lg:table-cell">آپدیت‌ها</th>
                            <th class="hidden lg:table-cell">پکیج‌ها</th>
                            <th class="hidden sm:table-cell">تاریخ ایجاد</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->records as $project)
                            <tr wire:key="project-{{ $project->id }}">
                                <td>
                                    <input type="checkbox" class="checkbox" aria-label="انتخاب این پروژه"
                                           wire:model.live="selectedIds" value="{{ $project->id }}" />
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                            <x-icon name="folder" class="size-5" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-zinc-800 dark:text-zinc-100">{{ $project->name }}</p>
                                            @if($project->repository_url)
                                                <a href="{{ $project->repository_url }}" target="_blank" rel="noopener"
                                                   class="mt-0.5 flex items-center gap-1 truncate text-xs text-zinc-400 transition-colors hover:text-brand-500" dir="ltr">
                                                    {{ \Illuminate\Support\Str::limit($project->repository_url, 38) }}
                                                    <x-icon name="external-link" class="size-3" />
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden font-mono text-xs text-zinc-500 md:table-cell" dir="ltr">{{ $project->slug }}</td>
                                <td>
                                    @php($variant = match($project->status) {
                                        'active' => 'success',
                                        'pending' => 'warning',
                                        'archived' => 'neutral',
                                        default => 'neutral',
                                    })
                                    <x-badge :variant="$variant">{{ $project->status_label }}</x-badge>
                                </td>
                                <td class="hidden tabular-nums text-zinc-600 dark:text-zinc-300 lg:table-cell">{{ fa_num($project->updates_count) }}</td>
                                <td class="hidden tabular-nums text-zinc-600 dark:text-zinc-300 lg:table-cell">{{ fa_num($project->packages_count) }}</td>
                                <td class="hidden text-sm text-zinc-500 sm:table-cell">{{ verta_date($project->created_at) }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" wire:click="openEdit({{ $project->id }})"
                                                class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400"
                                                title="ویرایش">
                                            <x-icon name="pencil" class="size-4.5" />
                                        </button>
                                        <button type="button" wire:click="$set('deleteId', {{ $project->id }})"
                                                class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                                title="حذف">
                                            <x-icon name="trash" class="size-4.5" />
                                        </button>
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
            <x-empty icon="folder" title="پروژه‌ای یافت نشد" description="با ایجاد اولین پروژه شروع کنید یا فیلترها را تغییر دهید.">
                <x-btn variant="soft" icon="plus" wire:click="openCreate">ایجاد پروژه</x-btn>
            </x-empty>
        @endif
    </div>

    {{-- create / edit modal --}}
    <x-modal wire:model="showModal" :title="$editingId ? 'ویرایش پروژه' : 'پروژه جدید'" size="md">
        <form wire:submit="save" class="space-y-5">
            <x-field label="نام پروژه" required>
                <x-input wire:model="form.name" placeholder="مثلاً فروشگاه ساز" icon="folder" :class="$errors->has('form.name') ? 'input-error' : ''" />
            </x-field>

            <x-field label="نامک (Slug)" hint="در صورت خالی بودن، از نام پروژه ساخته می‌شود.">
                <x-input wire:model="form.slug" placeholder="my-project" icon="link" dir="ltr" :class="$errors->has('form.slug') ? 'input-error' : ''" />
            </x-field>

            <x-field label="وضعیت">
                <x-select wire:model="form.status" :options="[
                    'active' => 'فعال',
                    'pending' => 'در انتظار',
                    'archived' => 'بایگانی شده',
                ]" />
            </x-field>

            <x-field label="آدرس مخزن (Git)">
                <x-input wire:model="form.repository_url" placeholder="https://github.com/user/repo" icon="git-branch" dir="ltr" :class="$errors->has('form.repository_url') ? 'input-error' : ''" />
            </x-field>

            <x-ckeditor model="form.description" label="توضیحات" :value="$form['description'] ?? ''" error="form.description" />

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-btn variant="secondary" wire:click="$set('showModal', false)">انصراف</x-btn>
                <x-btn type="submit" icon="save" :loading="true">ذخیره</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- delete confirm --}}
    <x-modal wire:model="deleteId" title="حذف پروژه" size="sm">
        @if($deleteId)
            @php($target = \App\Models\Project::find($deleteId))
            <div class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl bg-rose-50 p-4 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20">
                    <x-icon name="alert-triangle" class="size-6 shrink-0" />
                    <p class="text-sm leading-6">آیا از حذف پروژه «<strong>{{ $target?->name }}</strong>» مطمئن هستید؟ این عمل قابل بازگشت نیست.</p>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-btn variant="secondary" wire:click="$set('deleteId', null)">انصراف</x-btn>
                    <x-btn variant="danger" icon="trash" wire:click="delete" :loading="true">حذف قطعی</x-btn>
                </div>
            </div>
        @endif
    </x-modal>

    {{-- bulk delete confirm --}}
    <x-modal wire:model="confirmingBulkDelete" title="حذف گروهی پروژه‌ها" size="sm">
        @if($confirmingBulkDelete)
            <div class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl bg-rose-50 p-4 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20">
                    <x-icon name="trash" class="size-6 shrink-0" />
                    <p class="text-sm leading-6">
                        <strong>{{ fa_num(count($selectedIds)) }}</strong> پروژه انتخاب‌شده برای همیشه حذف شود؟
                        پروژه‌های دارای آپدیت یا پکیج حذف نخواهند شد. این عمل قابل بازگشت نیست.
                    </p>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-btn variant="secondary" wire:click="clearSelection">انصراف</x-btn>
                    <x-btn variant="danger" icon="trash" wire:click="bulkDelete" :loading="true">حذف {{ fa_num(count($selectedIds)) }} پروژه</x-btn>
                </div>
            </div>
        @endif
    </x-modal>
</div>
