<div class="space-y-6">
    {{-- CSS snippets that are not part of the prebuilt stylesheet (no build step) --}}
    <style>
        .pkg-card { transition: transform .2s ease, box-shadow .2s ease; }
        .pkg-card:hover { transform: translateY(-4px); }
        .clamp-2 { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; }
    </style>

    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-black text-zinc-900 dark:text-zinc-50">پکیج‌ها</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">ماژول‌ها و افزونه‌های قابل فروش؛ نسخه‌ها، قیمت‌گذاری و گالری هر پکیج در صفحه جزئیات مدیریت می‌شود.</p>
        </div>
        <x-btn icon="plus" wire:click="openCreate">پکیج جدید</x-btn>
    </div>

    {{-- filters --}}
    <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute start-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="جستجوی نام یا نامک…" class="input ps-10" />
        </div>
        <select wire:model.live="project_id" class="input sm:w-44">
            <option value="">همه پروژه‌ها</option>
            @foreach($this->projects as $project)
                <option value="{{ $project->id }}">{{ $project->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="status" class="input sm:w-44">
            <option value="">همه وضعیت‌ها</option>
            <option value="active">فعال</option>
            <option value="draft">پیش‌نویس</option>
            <option value="archived">آرشیو شده</option>
        </select>
        <div wire:loading wire:target="search, status, project_id" class="flex items-center gap-2 text-xs text-brand-600 dark:text-brand-400">
            <x-icon name="loader" class="size-4 animate-spin" />
            در حال فیلتر…
        </div>
    </div>

    {{-- card grid --}}
    @if($this->records->count())
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($this->records as $package)
                <a href="{{ route('admin.packages.show', $package) }}" wire:navigate wire:key="package-{{ $package->id }}"
                   class="pkg-card card group block overflow-hidden transition-all duration-200 hover:shadow-card-lg">

                    {{-- thumbnail / gradient placeholder --}}
                    <div class="relative aspect-video w-full overflow-hidden">
                        @if($package->thumbnail)
                            <img src="{{ $package->thumbnail_url }}" alt="{{ $package->name }}" loading="lazy"
                                 class="h-full w-full object-cover transition-transform duration-200" />
                        @else
                            <div class="flex h-full w-full items-center justify-center"
                                 style="background-image: linear-gradient(135deg, var(--color-brand-400), var(--color-teal-600))">
                                <x-icon name="package" class="size-12 text-white opacity-80" />
                            </div>
                        @endif

                        {{-- category + status badges --}}
                        <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-2 p-3">
                            <span class="inline-flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-medium text-zinc-700 backdrop-blur-sm"
                                  style="box-shadow: inset 0 0 0 1px rgb(9 9 11 / 0.08)">
                                <x-icon name="tag" class="size-3 text-brand-600" />
                                {{ \App\Livewire\Packages\Index::CATEGORIES[$package->category] ?? 'سایر' }}
                            </span>
                            @php($statusVariant = match($package->status) {
                                'active'   => 'success',
                                'draft'    => 'warning',
                                'archived' => 'neutral',
                                default    => 'neutral',
                            })
                            @php($statusLabel = match($package->status) {
                                'active'   => 'منتشر شده',
                                'draft'    => 'پیش‌نویس',
                                'archived' => 'آرشیو شده',
                                default    => $package->status,
                            })
                            <x-badge :variant="$statusVariant">{{ $statusLabel }}</x-badge>
                        </div>
                    </div>

                    {{-- body --}}
                    <div class="p-4">
                        <p class="truncate font-bold text-zinc-900 dark:text-zinc-100">{{ $package->name }}</p>
                        <p class="clamp-2 mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $package->short_description ?: 'بدون توضیح کوتاه' }}</p>

                        <p class="mt-3 flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                            <x-icon name="folder" class="size-3.5 shrink-0" />
                            <span class="truncate">{{ $package->project?->name ?? 'بدون پروژه' }}</span>
                        </p>

                        {{-- footer row --}}
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-zinc-100 py-3 dark:border-zinc-800">
                            @if($package->is_free)
                                <x-badge variant="success" icon="gift">رایگان</x-badge>
                            @else
                                <span class="text-sm font-bold tabular-nums text-zinc-800 dark:text-zinc-100">{{ money($package->default_price) }}</span>
                            @endif

                            <span class="inline-flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                @if($package->latestVersion)
                                    <x-badge variant="neutral"><span class="font-mono" dir="ltr">v{{ $package->latestVersion->version }}</span></x-badge>
                                @else
                                    <span>بدون نسخه</span>
                                @endif
                            </span>

                            <span class="inline-flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                                <span class="inline-flex items-center gap-1 tabular-nums" title="دانلودها">
                                    <x-icon name="download" class="size-3.5" />
                                    {{ fa_num($package->downloads_count) }}
                                </span>
                                <span class="inline-flex items-center gap-1 tabular-nums" title="خریدها">
                                    <x-icon name="credit-card" class="size-3.5" />
                                    {{ fa_num($package->purchases_count) }}
                                </span>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="card px-4 py-3">
            {{ $this->records->links() }}
        </div>
    @else
        <div class="card">
            <x-empty icon="package" title="پکیجی یافت نشد" description="با ایجاد اولین پکیج شروع کنید یا فیلترها را تغییر دهید.">
                <x-btn variant="soft" icon="plus" wire:click="openCreate">ایجاد پکیج</x-btn>
            </x-empty>
        </div>
    @endif

    {{-- create / edit modal --}}
    <x-modal wire:model="showModal" :title="$editingId ? 'ویرایش پکیج' : 'پکیج جدید'" subtitle="تصاویر، نسخه‌ها و پلن‌های قیمت از صفحه جزئیات پکیج مدیریت می‌شوند." size="lg">
        <form wire:submit="save" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="پروژه" required>
                    <select wire:model="form.project_id" class="input {{ $errors->has('form.project_id') ? 'input-error' : '' }}">
                        <option value="">انتخاب پروژه…</option>
                        @foreach($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field label="وضعیت" required>
                    <x-select wire:model="form.status" :options="[
                        'draft'    => 'پیش‌نویس',
                        'active'   => 'منتشر شده',
                        'archived' => 'آرشیو شده',
                    ]" />
                </x-field>
            </div>

            <x-field label="نام پکیج" required>
                <x-input wire:model="form.name" placeholder="مثلاً درگاه پرداخت ملت" icon="package" :class="$errors->has('form.name') ? 'input-error' : ''" />
            </x-field>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="نامک (Slug)" hint="در صورت خالی بودن، از نام پکیج ساخته می‌شود.">
                    <x-input wire:model="form.slug" placeholder="behpardakht-gateway" icon="link" dir="ltr" :class="$errors->has('form.slug') ? 'input-error' : ''" />
                </x-field>

                <x-field label="نام ماژول" hint="در صورت خالی بودن، از نامک ساخته می‌شود.">
                    <x-input wire:model="form.module_name" placeholder="BehpardakhtGateway" icon="terminal" dir="ltr" :class="$errors->has('form.module_name') ? 'input-error' : ''" />
                </x-field>
            </div>

            <x-field label="توضیح کوتاه" hint="در کارت پکیج نمایش داده می‌شود (حداکثر ۲۵۵ کاراکتر).">
                <x-input wire:model="form.short_description" placeholder="یک جمله درباره کاربرد پکیج…" :class="$errors->has('form.short_description') ? 'input-error' : ''" />
            </x-field>

            <x-field label="توضیحات کامل">
                <x-textarea wire:model="form.description" rows="4" placeholder="توضیح کامل پکیج، قابلیت‌ها و نصب…" :class="$errors->has('form.description') ? 'input-error' : ''" />
            </x-field>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="نویسنده">
                    <x-input wire:model="form.author" placeholder="نام توسعه‌دهنده" icon="user" :class="$errors->has('form.author') ? 'input-error' : ''" />
                </x-field>

                <x-field label="دسته‌بندی">
                    <x-select wire:model="form.category" :options="\App\Livewire\Packages\Index::CATEGORIES" placeholder="انتخاب دسته‌بندی…" />
                </x-field>
            </div>

            <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200 dark:bg-zinc-800/40 dark:ring-zinc-700">
                <x-toggle wire:model.live="form.is_free" label="این پکیج رایگان است" />
                <div class="mt-4">
                    @if(empty($this->form['is_free']))
                        <x-field label="قیمت پیش‌فرض (تومان)">
                            <x-input wire:model="form.default_price" type="number" min="0" placeholder="950000" icon="banknote" dir="ltr" :class="$errors->has('form.default_price') ? 'input-error' : ''" />
                        </x-field>
                    @endif
                </div>
            </div>

            <x-field label="ترتیب نمایش">
                <x-input wire:model="form.sort_order" type="number" min="0" placeholder="0" icon="list" dir="ltr" class="sm:w-44 {{ $errors->has('form.sort_order') ? 'input-error' : '' }}" />
            </x-field>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-btn variant="secondary" wire:click="$set('showModal', false)">انصراف</x-btn>
                <x-btn type="submit" icon="save" :loading="true">ذخیره</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- delete confirm --}}
    <x-modal wire:model="deleteId" title="حذف پکیج" size="sm">
        @if($deleteId)
            @php($target = \App\Models\Package::find($deleteId))
            <div class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl bg-rose-50 p-4 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20">
                    <x-icon name="alert-triangle" class="size-6 shrink-0" />
                    <p class="text-sm leading-6">
                        آیا از حذف پکیج «<strong>{{ $target?->name }}</strong>» مطمئن هستید؟
                        تصویر شاخص، گالری و تمام نسخه‌ها و فایل‌های آن نیز حذف می‌شوند. این عمل قابل بازگشت نیست.
                    </p>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-btn variant="secondary" wire:click="$set('deleteId', null)">انصراف</x-btn>
                    <x-btn variant="danger" icon="trash" wire:click="delete" :loading="true">حذف قطعی</x-btn>
                </div>
            </div>
        @endif
    </x-modal>
</div>
