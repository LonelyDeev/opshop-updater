@php
    // برچسب‌های وضعیت پرداخت (مشابه الگوی سایر صفحات)
    $paymentLabels = ['paid' => 'پرداخت شده', 'free' => 'رایگان', 'pending' => 'در انتظار پرداخت', 'failed' => 'ناموفق'];
    $paymentVariants = ['paid' => 'success', 'free' => 'info', 'pending' => 'warning', 'failed' => 'danger'];

    // آیکن بج وضعیت درخواست
    $statusIcons = ['approved' => 'check', 'rejected' => 'x', 'pending' => 'clock'];

    $stats = $this->stats;
@endphp

<div class="space-y-6">
    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-black text-zinc-900 dark:text-zinc-50">درخواست‌ها و تأییدها</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                بررسی درخواست‌های خرید طرح اشتراک و تأیید/رد آنها؛ تأیید هر درخواست، لایسنس پکیج‌های طرح را صادر یا تمدید می‌کند.
                <span class="whitespace-nowrap">({{ fa_num($this->records->total()) }} درخواست)</span>
            </p>
        </div>
        @if($stats['pending_count'] > 0)
            <x-badge variant="warning" icon="clock">درخواست‌های در انتظار تأیید: {{ fa_num($stats['pending_count']) }}</x-badge>
        @endif
    </div>

    {{-- stat cards --}}
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-stat label="در انتظار تأیید" :value="fa_num($stats['pending_count'])" icon="clipboard-check" variant="warning"
                :hint="$stats['total_pending_payment'] > 0 ? 'مجموع: ' . fa_num(money($stats['total_pending_payment'])) : null" />
        <x-stat label="در انتظار پرداخت" :value="fa_num($stats['awaiting_payment_count'])" icon="clock" variant="neutral"
                hint="پرداخت هنوز انجام نشده" />
        <x-stat label="تأیید شده" :value="fa_num($stats['approved_count'])" icon="badge-check" variant="info"
                hint="لایسنس‌ها صادر شده است" />
        <x-stat label="رد شده" :value="fa_num($stats['rejected_count'])" icon="x-circle" variant="danger" />
    </div>

    {{-- toolbar / filters --}}
    <div class="card flex flex-col gap-3 p-4 xl:flex-row xl:items-center">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute start-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="جستجوی مشتری، طرح یا کد پیگیری…" class="input ps-10" />
        </div>
        <select wire:model.live="status" class="input xl:w-44">
            <option value="">همه وضعیت‌ها</option>
            <option value="pending">در انتظار تأیید</option>
            <option value="approved">تأیید شده</option>
            <option value="rejected">رد شده</option>
        </select>
        <select wire:model.live="payment" class="input xl:w-44">
            <option value="">همه پرداخت‌ها</option>
            <option value="paid">پرداخت شده</option>
            <option value="free">رایگان</option>
            <option value="pending">در انتظار پرداخت</option>
            <option value="failed">ناموفق</option>
        </select>
        <select wire:model.live="sort" class="input xl:w-44">
            <option value="newest">جدیدترین</option>
            <option value="oldest">قدیمی‌ترین</option>
            <option value="amount_desc">بیشترین مبلغ</option>
            <option value="amount_asc">کمترین مبلغ</option>
        </select>
        <x-btn variant="ghost" icon="refresh-cw" wire:click="resetFilters">حذف فیلترها</x-btn>
        <div wire:loading wire:target="search, status, payment, sort" class="flex items-center gap-2 text-xs text-brand-600 dark:text-brand-400">
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
                            <th>مشتری</th>
                            <th class="hidden md:table-cell">طرح</th>
                            <th>مبلغ</th>
                            <th class="hidden lg:table-cell">درگاه</th>
                            <th class="hidden sm:table-cell">پرداخت</th>
                            <th>وضعیت</th>
                            <th class="hidden xl:table-cell">تاریخ درخواست</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->records as $request)
                            <tr wire:key="request-{{ $request->id }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :name="$request->customer?->name ?? '؟'" />
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-zinc-800 dark:text-zinc-100">{{ $request->customer?->name ?? '—' }}</p>
                                            <p class="truncate text-xs text-zinc-400" dir="ltr">{{ $request->customer?->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="truncate font-semibold text-zinc-800 dark:text-zinc-100">{{ $request->plan?->name ?? '—' }}</span>
                                            @if($request->plan?->is_free)
                                                <x-badge variant="info">رایگان</x-badge>
                                            @endif
                                            @if($request->plan?->is_one_time)
                                                <x-badge variant="neutral">یک‌بار مصرف</x-badge>
                                            @endif
                                        </div>
                                        <p class="flex items-center gap-1 text-xs text-zinc-400">
                                            <x-icon name="calendar" class="size-3" />
                                            {{ $request->plan?->duration_label ?? '—' }}
                                        </p>
                                    </div>
                                </td>
                                <td>
                                    @if($request->isFreePlan())
                                        <x-badge variant="info">رایگان</x-badge>
                                    @else
                                        <p class="font-semibold tabular-nums text-zinc-800 dark:text-zinc-100">{{ fa_num(money($request->amount)) }}</p>
                                    @endif
                                </td>
                                <td class="hidden lg:table-cell">
                                    @if($request->gateway)
                                        @php
                                            $gatewayLabel = config('general.supported_gateways.' . $request->gateway) ?? $request->gateway;
                                        @endphp
                                        <x-badge variant="neutral">{{ $gatewayLabel }}</x-badge>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="hidden sm:table-cell">
                                    <x-badge :variant="$paymentVariants[$request->payment_status] ?? 'neutral'">{{ $paymentLabels[$request->payment_status] ?? $request->payment_status }}</x-badge>
                                </td>
                                <td>
                                    <x-badge :variant="$request->status_badge" :icon="$statusIcons[$request->status] ?? null">{{ $request->status_label }}</x-badge>
                                </td>
                                <td class="hidden xl:table-cell">
                                    <p class="text-sm tabular-nums text-zinc-600 dark:text-zinc-300">{{ verta_date($request->created_at) }}</p>
                                    <p class="text-xs text-zinc-400">{{ verta_from_now($request->created_at) }}</p>
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" wire:click="viewRequest({{ $request->id }})"
                                                class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400"
                                                title="جزئیات">
                                            <x-icon name="eye" class="size-4.5" />
                                        </button>
                                        @if($request->status === 'pending')
                                            @if($request->isPaymentSettled())
                                                <button type="button" wire:click="openApprove({{ $request->id }})"
                                                        class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400"
                                                        title="تأیید و فعال‌سازی">
                                                    <x-icon name="badge-check" class="size-4.5" />
                                                </button>
                                                <button type="button" wire:click="openReject({{ $request->id }})"
                                                        class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400"
                                                        title="رد درخواست">
                                                    <x-icon name="x-circle" class="size-4.5" />
                                                </button>
                                            @else
                                                {{-- پرداخت هنوز انجام نشده — دکمه‌ها غیرفعال --}}
                                                <button type="button" disabled title="پرداخت هنوز انجام نشده"
                                                        class="cursor-not-allowed rounded-lg p-2 text-zinc-300 opacity-50 dark:text-zinc-600">
                                                    <x-icon name="badge-check" class="size-4.5" />
                                                </button>
                                                <button type="button" disabled title="پرداخت هنوز انجام نشده"
                                                        class="cursor-not-allowed rounded-lg p-2 text-zinc-300 opacity-50 dark:text-zinc-600">
                                                    <x-icon name="x-circle" class="size-4.5" />
                                                </button>
                                            @endif
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
            <x-empty icon="clipboard-check" title="درخواستی یافت نشد" description="درخواست خریدی ثبت نشده یا فیلترها نتیجه‌ای ندارند.">
                <x-btn variant="soft" icon="refresh-cw" wire:click="resetFilters">حذف فیلترها</x-btn>
            </x-empty>
        @endif
    </div>

    {{-- detail modal --}}
    <x-modal wire:model="viewId" title="جزئیات درخواست" :subtitle="$this->viewRecord ? '#' . fa_num($this->viewRecord->id) . ' — ' . $this->viewRecord->customer?->name : null" size="xl">
        @if($viewId && $this->viewRecord)
            @php
                $record = $this->viewRecord;
            @endphp
            <div class="space-y-5">
                {{-- خلاصه: مشتری + وضعیت‌ها --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-900/5 dark:bg-zinc-800/60 dark:ring-white/10">
                        <p class="mb-3 flex items-center gap-1.5 text-xs font-bold text-zinc-500 dark:text-zinc-400">
                            <x-icon name="user" class="size-3.5" /> مشتری
                        </p>
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$record->customer?->name ?? '؟'" />
                            <div class="min-w-0">
                                <p class="truncate font-bold text-zinc-800 dark:text-zinc-100">{{ $record->customer?->name ?? '—' }}</p>
                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400" dir="ltr">{{ $record->customer?->email }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-900/5 dark:bg-zinc-800/60 dark:ring-white/10">
                        <p class="mb-3 flex items-center gap-1.5 text-xs font-bold text-zinc-500 dark:text-zinc-400">
                            <x-icon name="info" class="size-3.5" /> وضعیت درخواست
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :variant="$record->status_badge" :icon="$statusIcons[$record->status] ?? null">{{ $record->status_label }}</x-badge>
                            <x-badge :variant="$paymentVariants[$record->payment_status] ?? 'neutral'" icon="credit-card">{{ $paymentLabels[$record->payment_status] ?? $record->payment_status }}</x-badge>
                        </div>
                        <p class="mt-3 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                            ثبت: {{ verta_date($record->created_at) }}
                        </p>
                    </div>
                </div>

                {{-- طرح اشتراک --}}
                <div class="space-y-3 rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-900/5 dark:bg-zinc-800/60 dark:ring-white/10">
                    <p class="flex items-center gap-1.5 text-xs font-bold text-zinc-500 dark:text-zinc-400">
                        <x-icon name="sparkles" class="size-3.5" /> طرح اشتراک
                    </p>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <p class="font-bold text-zinc-800 dark:text-zinc-100">{{ $record->plan?->name ?? '—' }}</p>
                            @if($record->plan?->is_free)
                                <x-badge variant="info">رایگان</x-badge>
                            @endif
                            @if($record->plan?->is_one_time)
                                <x-badge variant="neutral">یک‌بار مصرف</x-badge>
                            @endif
                            <x-badge variant="primary" icon="calendar">{{ $record->plan?->duration_label ?? '—' }}</x-badge>
                        </div>
                        <div class="text-end">
                            @if($record->plan?->is_free)
                                <x-badge variant="info" icon="gift">رایگان</x-badge>
                            @elseif($record->plan?->has_discount)
                                <p class="text-xs font-medium tabular-nums text-amber-600 line-through dark:text-amber-400" dir="ltr">{{ fa_num(money($record->plan->price, false)) }}</p>
                                <p class="font-bold tabular-nums text-zinc-800 dark:text-zinc-100">{{ fa_num(money($record->plan->final_price)) }}</p>
                            @else
                                <p class="font-bold tabular-nums text-zinc-800 dark:text-zinc-100">{{ fa_num(money($record->plan?->final_price ?? 0)) }}</p>
                            @endif
                        </div>
                    </div>

                    @if(filled($record->plan?->features))
                        <ul class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                            @foreach($record->plan->features as $feature)
                                <li class="flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-300">
                                    <x-icon name="check-circle" class="size-3.5 shrink-0 text-emerald-500" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- پکیج‌های طرح + مدت دسترسی مؤثر --}}
                    <div class="table-wrap ring-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>پکیج</th>
                                    <th>مدت دسترسی مؤثر</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($record->plan->packages as $package)
                                    <tr wire:key="view-pkg-{{ $package->id }}">
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <x-icon name="package" class="size-4 shrink-0 text-zinc-400" />
                                                <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $package->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($package->pivot->duration_months)
                                                <x-badge variant="primary" icon="clock">{{ fa_num($package->pivot->duration_months) }} ماه</x-badge>
                                            @else
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                    مدت پیش‌فرض طرح ({{ $record->plan->duration_months ? fa_num($record->plan->duration_months) . ' ماه' : 'نامحدود' }})
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- پرداخت --}}
                <div class="space-y-3 rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-900/5 dark:bg-zinc-800/60 dark:ring-white/10">
                    <p class="flex items-center gap-1.5 text-xs font-bold text-zinc-500 dark:text-zinc-400">
                        <x-icon name="credit-card" class="size-3.5" /> پرداخت
                    </p>
                    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs text-zinc-400 dark:text-zinc-500">مبلغ</dt>
                            <dd class="mt-0.5 text-sm font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">
                                @if($record->isFreePlan())
                                    <x-badge variant="info">رایگان</x-badge>
                                @else
                                    {{ fa_num(money($record->amount)) }}
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-zinc-400 dark:text-zinc-500">درگاه پرداخت</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                                @if($record->gateway)
                                    {{ config('general.supported_gateways.' . $record->gateway) ?? $record->gateway }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-zinc-400 dark:text-zinc-500">کد پیگیری</dt>
                            <dd class="mt-0.5 font-mono text-xs font-semibold tracking-wider text-zinc-700 dark:text-zinc-200" dir="ltr">
                                {{ $record->transaction_id ?: '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-zinc-400 dark:text-zinc-500">وضعیت پرداخت</dt>
                            <dd class="mt-0.5">
                                <x-badge :variant="$paymentVariants[$record->payment_status] ?? 'neutral'">{{ $paymentLabels[$record->payment_status] ?? $record->payment_status }}</x-badge>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-zinc-400 dark:text-zinc-500">تاریخ پرداخت</dt>
                            <dd class="mt-0.5 text-sm font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">
                                {{ $record->paid_at ? verta_date($record->paid_at) : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-zinc-400 dark:text-zinc-500">وضعیت درخواست</dt>
                            <dd class="mt-0.5">
                                <x-badge :variant="$record->status_badge" :icon="$statusIcons[$record->status] ?? null">{{ $record->status_label }}</x-badge>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- وضعیت: خط زمانی + یادداشت مدیر --}}
                <div class="space-y-3 rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-900/5 dark:bg-zinc-800/60 dark:ring-white/10">
                    <p class="flex items-center gap-1.5 text-xs font-bold text-zinc-500 dark:text-zinc-400">
                        <x-icon name="history" class="size-3.5" /> وضعیت
                    </p>
                    @php
                        $timeline = [
                            ['icon' => 'clipboard-check', 'label' => 'ثبت درخواست', 'at' => $record->created_at],
                            ['icon' => 'credit-card', 'label' => 'پرداخت', 'at' => $record->paid_at],
                            $record->status === 'rejected'
                                ? ['icon' => 'x-circle', 'label' => 'رد درخواست', 'at' => $record->rejected_at]
                                : ['icon' => 'check-circle', 'label' => 'تأیید و فعال‌سازی', 'at' => $record->approved_at],
                        ];
                    @endphp
                    <div class="space-y-3">
                        @foreach($timeline as $step)
                            @php
                                $done = filled($step['at']);
                            @endphp
                            <div class="flex items-center gap-3">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full {{ $done ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-600' }}">
                                    <x-icon :name="$step['icon']" class="size-4" />
                                </div>
                                <div class="flex flex-1 items-center justify-between gap-2">
                                    <p class="text-sm font-semibold {{ $done ? 'text-zinc-800 dark:text-zinc-100' : 'text-zinc-400 dark:text-zinc-500' }}">{{ $step['label'] }}</p>
                                    <p class="text-xs tabular-nums {{ $done ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-400 dark:text-zinc-600' }}">
                                        {{ $done ? verta_date($step['at']) . ' — ' . verta_from_now($step['at']) : 'انجام نشده' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($record->admin_note)
                        <div class="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-amber-800 ring-1 ring-amber-600/15 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-400/20">
                            <x-icon name="info" class="mt-0.5 size-4 shrink-0" />
                            <p class="text-xs leading-6"><strong>یادداشت مدیر:</strong> {{ $record->admin_note }}</p>
                        </div>
                    @endif
                </div>

                {{-- لایسنس‌های صادرشده (پس از تأیید) --}}
                @if(filled($record->meta['activated_licenses'] ?? null))
                    <div class="space-y-3 rounded-xl bg-emerald-50/60 p-4 ring-1 ring-emerald-600/15 dark:bg-emerald-500/10 dark:ring-emerald-400/20">
                        <p class="flex items-center gap-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                            <x-icon name="shield-check" class="size-3.5" />
                            لایسنس‌های صادرشده ({{ fa_num($record->licenses_count) }})
                        </p>
                        <div class="table-wrap ring-0">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>پکیج</th>
                                        <th>کلید لایسنس</th>
                                        <th>تاریخ انقضا</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($record->meta['activated_licenses'] as $license)
                                        <tr wire:key="view-license-{{ $license['license_key'] }}">
                                            <td>
                                                <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $license['package'] ?? '—' }}</span>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-1.5" dir="ltr">
                                                    <span class="font-mono text-xs font-semibold tracking-wider text-zinc-700 dark:text-zinc-200">{{ $license['license_key'] }}</span>
                                                    <button type="button" x-data title="کپی کلید لایسنس"
                                                            @click="navigator.clipboard.writeText('{{ $license['license_key'] }}'); $dispatch('toast', {message: 'کلید لایسنس کپی شد', type: 'success'})"
                                                            class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                                                        <x-icon name="copy" class="size-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-sm tabular-nums text-zinc-600 dark:text-zinc-300">
                                                    {{ filled($license['expires_at'] ?? null) ? verta_date($license['expires_at']) : 'نامحدود' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <div class="flex w-full flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($record->isPendingApproval())
                            <button type="button" wire:click="openApprove({{ $record->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs transition-colors hover:bg-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-500">
                                <x-icon name="badge-check" class="size-4" />
                                تأیید و فعال‌سازی
                            </button>
                            <button type="button" wire:click="openReject({{ $record->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700 ring-1 ring-rose-600/20 transition-colors hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20 dark:hover:bg-rose-500/20">
                                <x-icon name="x-circle" class="size-4" />
                                رد درخواست
                            </button>
                        @endif
                    </div>
                    <x-btn variant="secondary" wire:click="$set('viewId', null)">بستن</x-btn>
                </div>
            </x-slot:footer>
        @endif
    </x-modal>

    {{-- approve confirm modal --}}
    <x-modal wire:model="approveId" title="تأیید و فعال‌سازی" subtitle="با تأیید، برای همه پکیج‌های طرح لایسنس صادر/تمدید می‌شود." size="md">
        @if($approveId && $this->approveTarget)
            @php
                $target = $this->approveTarget;
            @endphp
            <form wire:submit="approve" class="space-y-5">
                <div class="flex items-center gap-3 rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-900/5 dark:bg-zinc-800/60 dark:ring-white/10">
                    <x-avatar :name="$target->customer?->name ?? '؟'" size="sm" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ $target->customer?->name }} — {{ $target->plan?->name }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            @if($target->isFreePlan())
                                <x-badge variant="info">رایگان</x-badge>
                            @else
                                <span class="font-semibold tabular-nums">{{ fa_num(money($target->amount)) }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl bg-emerald-50 p-4 text-emerald-800 ring-1 ring-emerald-600/15 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/20">
                    <x-icon name="alert-triangle" class="size-6 shrink-0" />
                    <p class="text-sm leading-6">
                        با تأیید این درخواست، برای
                        <strong>{{ fa_num($target->plan?->packages->count() ?? 0) }}</strong>
                        پکیج این طرح لایسنس صادر/تمدید می‌شود.
                    </p>
                </div>

                <x-field label="یادداشت مدیر (اختیاری)" hint="این یادداشت در تاریخچه درخواست ذخیره می‌شود.">
                    <x-textarea wire:model="adminNote" rows="3" placeholder="مثلاً پرداخت بررسی و تأیید شد." :class="$errors->has('adminNote') ? 'input-error' : ''" />
                </x-field>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <x-btn variant="secondary" wire:click="$set('approveId', null)">انصراف</x-btn>
                    <button type="submit" wire:loading.attr="disabled" wire:target="approve"
                            class="inline-flex h-10 select-none items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-xs transition-all duration-150 hover:bg-emerald-500 active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-500 disabled:pointer-events-none disabled:opacity-50 dark:bg-emerald-600 dark:hover:bg-emerald-500">
                        <x-icon name="badge-check" class="size-4.5" wire:loading.remove wire:target="approve" />
                        <x-icon name="loader" class="size-4 animate-spin" wire:loading wire:target="approve" />
                        تأیید و فعال‌سازی
                    </button>
                </div>
            </form>
        @endif
    </x-modal>

    {{-- reject confirm modal --}}
    <x-modal wire:model="rejectId" title="رد درخواست" subtitle="دلیل رد در تاریخچه درخواست ذخیره می‌شود." size="md">
        @if($rejectId && $this->rejectTarget)
            @php
                $target = $this->rejectTarget;
            @endphp
            <form wire:submit="reject" class="space-y-5">
                <div class="flex items-center gap-3 rounded-xl bg-rose-50 p-4 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20">
                    <x-avatar :name="$target->customer?->name ?? '؟'" size="sm" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold">{{ $target->customer?->name }} — {{ $target->plan?->name }}</p>
                        <p class="mt-0.5 text-xs opacity-80">درخواست رد شده و هیچ لایسنسی صادر نخواهد شد.</p>
                    </div>
                </div>

                <x-field label="دلیل رد" required hint="حداقل ۵ حرف؛ در تاریخچه درخواست ثبت می‌شود.">
                    <x-textarea wire:model="rejectNote" rows="3" placeholder="مثلاً اطلاعات پرداخت قابل تأیید نبود؛ لطفاً با پشتیبانی تماس بگیرید." :class="$errors->has('rejectNote') ? 'input-error' : ''" />
                </x-field>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <x-btn variant="secondary" wire:click="$set('rejectId', null)">انصراف</x-btn>
                    <x-btn type="submit" variant="danger" icon="x-circle" :loading="true">رد درخواست</x-btn>
                </div>
            </form>
        @endif
    </x-modal>
</div>
