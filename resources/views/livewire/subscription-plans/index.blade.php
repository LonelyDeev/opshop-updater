@php
    // گزینه‌های مدت اعتبار طرح (۰ = نامحدود) — مقدار فعلی غیرپیش‌فرض هم به‌عنوان گزینه سفارشی اضافه می‌شود
    $durationOptions = collect([0, 1, 3, 6, 12, 24, 36])
        ->mapWithKeys(fn ($m) => [$m => $m === 0 ? 'نامحدود' : fa_num($m) . ' ماه'])
        ->all();
    $currentDuration = (string) ($form['duration_months'] ?? '1');
    if ($currentDuration !== '' && ! isset($durationOptions[$currentDuration])) {
        $durationOptions[$currentDuration] = fa_num($currentDuration) . ' ماه (سفارشی)';
    }

    // پکیج‌های هنوز اضافه‌نشده به طرح (برای دراپ‌داون افزودن)
    $addedPackageIds = collect($formPackages)
        ->map(fn ($row) => (int) ($row['package_id'] ?? 0))
        ->filter()
        ->all();
    $availablePackages = $this->packages
        ->reject(fn ($p) => in_array($p->id, $addedPackageIds))
        ->mapWithKeys(fn ($p) => [$p->id => $p->name])
        ->all();
@endphp

<div class="space-y-6">
    {{-- CSS snippets that are not part of the prebuilt stylesheet (no build step) --}}
    <style>
        .clamp-2 { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; }
    </style>

    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="flex flex-wrap items-center gap-2 text-lg font-black text-zinc-900 dark:text-zinc-50">
                پلن‌ها و طرح‌های اشتراک
                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-bold tabular-nums text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ fa_num($this->records->total()) }} طرح</span>
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">تعریف پلن‌های اشتراک، قیمت‌گذاری، قابلیت‌ها و پکیج‌های همراه هر طرح؛ مشتریان از همین طرح‌ها درخواست اشتراک ثبت می‌کنند.</p>
        </div>
        <x-btn icon="plus" wire:click="openCreate">طرح جدید</x-btn>
    </div>

    {{-- toolbar --}}
    <div class="card flex flex-col gap-3 p-4 xl:flex-row xl:items-center">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute start-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="جستجوی نام یا توضیحات طرح…" class="input ps-10" />
        </div>
        <select wire:model.live="status" class="input xl:w-40">
            <option value="">همه وضعیت‌ها</option>
            <option value="active">فعال</option>
            <option value="inactive">غیرفعال</option>
        </select>
        <select wire:model.live="sort" class="input xl:w-56">
            <option value="order">مرتب‌سازی: ترتیب نمایش</option>
            <option value="newest">مرتب‌سازی: جدیدترین</option>
            <option value="oldest">مرتب‌سازی: قدیمی‌ترین</option>
            <option value="price_desc">مرتب‌سازی: گران‌ترین</option>
            <option value="price_asc">مرتب‌سازی: ارزان‌ترین</option>
        </select>
        <x-btn variant="ghost" icon="refresh-cw" wire:click="resetFilters">حذف فیلترها</x-btn>
        <div wire:loading wire:target="search, status, sort" class="flex items-center gap-2 text-xs text-brand-600 dark:text-brand-400">
            <x-icon name="loader" class="size-4 animate-spin" />
            در حال فیلتر…
        </div>
    </div>

    {{-- card grid --}}
    @if($this->records->count())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($this->records as $plan)
                <div wire:key="plan-{{ $plan->id }}" class="card flex flex-col p-4">
                    {{-- name + badges --}}
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                            <x-icon name="crown" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold text-zinc-900 dark:text-zinc-100">{{ $plan->name }}</p>
                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                <x-badge :variant="$plan->is_free ? 'info' : 'neutral'" :icon="$plan->is_free ? 'gift' : 'banknote'">{{ $plan->is_free ? 'رایگان' : 'پولی' }}</x-badge>
                                @if($plan->is_one_time)
                                    <x-badge variant="warning" icon="zap">یک‌بار مصرف</x-badge>
                                @endif
                                <x-badge :variant="$plan->is_active ? 'success' : 'danger'">{{ $plan->is_active ? 'فعال' : 'غیرفعال' }}</x-badge>
                                <x-badge variant="neutral" icon="calendar">{{ $plan->duration_label }}</x-badge>
                            </div>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-zinc-100 px-2 py-1 text-[11px] font-medium tabular-nums text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400" title="ترتیب نمایش">
                            <x-icon name="grip-vertical" class="size-3" />
                            {{ fa_num($plan->sort_order) }}
                        </span>
                    </div>

                    {{-- description --}}
                    <p class="clamp-2 mb-3 mt-3 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $plan->description ?: 'بدون توضیح' }}</p>

                    {{-- price --}}
                    <div class="mb-3 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800/60">
                        @if($plan->is_free)
                            <p class="text-lg font-black text-emerald-600 dark:text-emerald-400">رایگان</p>
                        @else
                            <div class="flex flex-wrap items-center gap-2">
                                @if($plan->has_discount)
                                    <p class="text-xs font-medium tabular-nums text-zinc-400 line-through" dir="ltr">{{ money($plan->price, false) }}</p>
                                    <x-badge variant="warning" icon="tag">{{ fa_num($plan->discount_percent) }}٪ تخفیف</x-badge>
                                @endif
                                <p class="text-lg font-black tabular-nums text-zinc-900 dark:text-zinc-50">{{ money($plan->final_price) }}</p>
                            </div>
                            @if($plan->has_discount)
                                <p class="mt-0.5 text-[11px] tabular-nums text-amber-600 dark:text-amber-400">پرداخت: {{ money($plan->discount_price) }}</p>
                            @endif
                        @endif
                    </div>

                    {{-- features checklist (first 4) --}}
                    @if(count($plan->features ?? []))
                        <ul class="mb-3 space-y-1.5">
                            @foreach(array_slice($plan->features ?? [], 0, 4) as $feature)
                                <li class="flex items-start gap-1.5 text-xs leading-5 text-zinc-600 dark:text-zinc-300">
                                    <x-icon name="check" class="mt-0.5 size-3.5 shrink-0 text-emerald-500" />
                                    <span class="truncate">{{ $feature }}</span>
                                </li>
                            @endforeach
                            @if(count($plan->features) > 4)
                                <li class="text-[11px] tabular-nums text-zinc-400">+ {{ fa_num(count($plan->features) - 4) }} قابلیت دیگر…</li>
                            @endif
                        </ul>
                    @endif

                    {{-- footer: stats + actions --}}
                    <div class="mt-auto flex items-center justify-between gap-2 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <div class="flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="inline-flex items-center gap-1 tabular-nums" title="قابلیت‌ها">
                                <x-icon name="list" class="size-3.5" />
                                {{ fa_num(count($plan->features ?? [])) }}
                            </span>
                            <span class="inline-flex items-center gap-1 tabular-nums" title="پکیج‌های همراه طرح">
                                <x-icon name="package" class="size-3.5" />
                                {{ fa_num($plan->packages_count) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="openEdit({{ $plan->id }})"
                                    class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400"
                                    title="ویرایش">
                                <x-icon name="pencil" class="size-4.5" />
                            </button>
                            <button type="button" wire:click="$set('deleteId', {{ $plan->id }})"
                                    class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                    title="حذف">
                                <x-icon name="trash" class="size-4.5" />
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- pagination --}}
        <div class="card px-4 py-3">
            {{ $this->records->links() }}
        </div>
    @else
        <div class="card">
            <x-empty icon="crown" title="طرحی یافت نشد" description="با ایجاد اولین طرح اشتراک شروع کنید یا فیلترها را تغییر دهید.">
                <x-btn variant="soft" icon="plus" wire:click="openCreate">ایجاد طرح</x-btn>
            </x-empty>
        </div>
    @endif

    {{-- create / edit modal --}}
    <x-modal wire:model="showModal" :title="$editingId ? 'ویرایش طرح اشتراک' : 'طرح اشتراک جدید'" subtitle="قابلیت‌ها و پکیج‌های همراه طرح در همین فرم مدیریت می‌شوند." size="xl">
        <form wire:submit="save" class="space-y-6">
            {{-- section: اطلاعات طرح --}}
            <section class="space-y-4">
                <h3 class="flex items-center gap-2 text-sm font-bold text-zinc-800 dark:text-zinc-100">
                    <x-icon name="crown" class="size-4 text-brand-600 dark:text-brand-400" />
                    اطلاعات طرح
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-field label="نام طرح" required>
                        <x-input wire:model="form.name" placeholder="مثلاً پلن یک‌ساله حرفه‌ای" icon="crown" :class="$errors->has('form.name') ? 'input-error' : ''" />
                    </x-field>

                    <x-field label="مدت اعتبار" required hint="۰ به معنی نامحدود است.">
                        <x-select wire:model="form.duration_months" :options="$durationOptions" :class="$errors->has('form.duration_months') ? 'input-error' : ''" />
                    </x-field>
                </div>

                <x-field label="توضیحات">
                    <x-textarea wire:model="form.description" rows="3" placeholder="توضیح کوتاه طرح برای نمایش به مشتری…" :class="$errors->has('form.description') ? 'input-error' : ''" />
                </x-field>

                <div class="flex flex-wrap items-center gap-x-8 gap-y-3 rounded-xl bg-zinc-50 p-3.5 dark:bg-zinc-800/60">
                    <x-toggle wire:model="form.is_free" label="طرح رایگان است" />
                    <x-toggle wire:model="form.is_one_time" label="یک‌بار مصرف" />
                    <x-toggle wire:model="form.is_active" label="فعال" />
                </div>
            </section>

            {{-- section: قیمت‌گذاری --}}
            <section class="space-y-4">
                <h3 class="flex items-center gap-2 text-sm font-bold text-zinc-800 dark:text-zinc-100">
                    <x-icon name="banknote" class="size-4 text-brand-600 dark:text-brand-400" />
                    قیمت‌گذاری
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-field label="قیمت اصلی (تومان)" required>
                        <x-input wire:model="form.price" type="number" min="0" step="1" inputmode="numeric" placeholder="350000" icon="banknote" dir="ltr" :class="$errors->has('form.price') ? 'input-error' : ''" />
                    </x-field>

                    <x-field label="قیمت با تخفیف (تومان)" hint="خالی = بدون تخفیف؛ باید کمتر از قیمت اصلی باشد.">
                        <x-input wire:model="form.discount_price" type="number" min="0" step="1" inputmode="numeric" placeholder="280000" icon="tag" dir="ltr" :class="$errors->has('form.discount_price') ? 'input-error' : ''" />
                    </x-field>

                    <x-field label="ترتیب نمایش">
                        <x-input wire:model="form.sort_order" type="number" min="0" step="1" inputmode="numeric" placeholder="0" icon="arrow-up-down" dir="ltr" :class="$errors->has('form.sort_order') ? 'input-error' : ''" />
                    </x-field>
                </div>
            </section>

            {{-- section: قابلیت‌ها (repeater) --}}
            <section class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-zinc-800 dark:text-zinc-100">
                        <x-icon name="list" class="size-4 text-brand-600 dark:text-brand-400" />
                        قابلیت‌ها
                    </h3>
                    <x-btn variant="soft" size="sm" icon="plus" wire:click="addFeature">افزودن قابلیت</x-btn>
                </div>
                <p class="text-xs leading-5 text-zinc-500 dark:text-zinc-400">هر قابلیت به‌صورت یک مورد تیک‌دار در کارت طرح نمایش داده می‌شود (حداکثر ۱۵۰ کاراکتر). ردیف‌های خالی نادیده گرفته می‌شوند.</p>
                <div class="space-y-2">
                    @foreach($formFeatures as $i => $feature)
                        <div wire:key="feature-{{ $i }}" class="flex items-center gap-2">
                            <x-icon name="check" class="size-4 shrink-0 text-zinc-300 dark:text-zinc-600" />
                            <input type="text" wire:model="formFeatures.{{ $i }}" placeholder="مثلاً پشتیبانی اولویت‌دار" class="input flex-1 {{ $errors->has('formFeatures.'.$i) ? 'input-error' : '' }}" />
                            <button type="button" wire:click="removeFeature({{ $i }})"
                                    class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                    title="حذف قابلیت">
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- section: پکیج‌های طرح --}}
            <section class="space-y-3">
                <h3 class="flex items-center gap-2 text-sm font-bold text-zinc-800 dark:text-zinc-100">
                    <x-icon name="package" class="size-4 text-brand-600 dark:text-brand-400" />
                    پکیج‌های طرح
                </h3>
                <p class="text-xs leading-5 text-zinc-500 dark:text-zinc-400">پس از تأیید درخواست مشتری، برای پکیج‌های این طرح لایسنس صادر/تمدید می‌شود. مدت خالی = مدت پیش‌فرض طرح؛ ۰ = نامحدود.</p>
                <div class="flex items-center gap-2">
                    <select wire:model="packageToAdd" class="input flex-1">
                        <option value="">افزودن پکیج به طرح…</option>
                        @foreach($availablePackages as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <x-btn variant="secondary" icon="plus" wire:click="addPackage">افزودن</x-btn>
                </div>
                @if(count($formPackages))
                    <div class="divide-y divide-zinc-100 rounded-xl ring-1 ring-zinc-200 dark:divide-zinc-800 dark:ring-zinc-800">
                        @foreach($formPackages as $i => $row)
                            <div wire:key="pkg-{{ $i }}" class="flex flex-wrap items-center gap-2 p-3">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <x-icon name="package" class="size-4 shrink-0 text-zinc-400" />
                                    <span class="truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $this->packageNames[(int) $row['package_id']] ?? ('پکیج #' . fa_num($row['package_id'])) }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <input type="number" wire:model="formPackages.{{ $i }}.duration_months" min="0" max="120" step="1" inputmode="numeric"
                                           placeholder="مدت پیش‌فرض طرح" class="input w-40 {{ $errors->has('formPackages.'.$i.'.duration_months') ? 'input-error' : '' }}" dir="ltr" />
                                    <span class="text-xs text-zinc-400">ماه</span>
                                </div>
                                <button type="button" wire:click="removePackage({{ $i }})"
                                        class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                        title="حذف پکیج از طرح">
                                    <x-icon name="trash" class="size-4" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-xl border border-dashed border-zinc-200 px-3 py-4 text-center text-xs leading-5 text-zinc-400 dark:border-zinc-700 dark:text-zinc-500">هنوز پکیجی به این طرح اضافه نشده است.</p>
                @endif
            </section>

            <div class="flex items-center justify-end gap-2 pt-2">
                <x-btn variant="secondary" wire:click="$set('showModal', false)">انصراف</x-btn>
                <x-btn type="submit" icon="save" :loading="true">ذخیره</x-btn>
            </div>
        </form>
    </x-modal>

    {{-- delete confirm --}}
    <x-modal wire:model="deleteId" title="حذف طرح اشتراک" size="sm">
        @if($deleteId)
            @php($target = \App\Models\SubscriptionPlan::withCount(['packages', 'requests'])->find($deleteId))
            <div class="space-y-4">
                <div class="flex items-start gap-3 rounded-xl bg-rose-50 p-4 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20">
                    <x-icon name="alert-triangle" class="size-6 shrink-0" />
                    <div class="space-y-1 text-sm leading-6">
                        <p>آیا از حذف طرح «<strong>{{ $target?->name }}</strong>» مطمئن هستید؟ این عمل قابل بازگشت نیست.</p>
                        @if($target && $target->requests_count > 0)
                            <p class="font-semibold">این طرح {{ fa_num($target->requests_count) }} درخواست دارد که با حذف آن حذف می‌شوند.</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-btn variant="secondary" wire:click="$set('deleteId', null)">انصراف</x-btn>
                    <x-btn variant="danger" icon="trash" wire:click="delete" :loading="true">حذف قطعی</x-btn>
                </div>
            </div>
        @endif
    </x-modal>
</div>
