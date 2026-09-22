<div class="space-y-6">
    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-black text-zinc-900 dark:text-zinc-50">خریدها</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">پرداخت‌های پکیج‌ها و وضعیت تراکنش‌ها.</p>
        </div>
    </div>

    {{-- summary cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="درآمد خریدهای موفق" :value="money($this->stats['revenue'])" icon="wallet" variant="primary" hint="جمع پرداخت‌های وضعیت موفق" />
        <x-stat label="در انتظار پرداخت" :value="fa_num($this->stats['pending'])" icon="clock" variant="warning" hint="تراکنش‌های ناتمام" />
        <x-stat label="خریدهای ناموفق" :value="fa_num($this->stats['failed'])" icon="x-circle" variant="danger" hint="پرداخت‌های شکست‌خورده" />
    </div>

    {{-- filters --}}
    <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute start-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="جستجوی شناسه تراکنش یا نام مشتری…" class="input ps-10" />
        </div>
        <select wire:model.live="status" class="input sm:w-44">
            <option value="">همه وضعیت‌ها</option>
            <option value="paid">موفق</option>
            <option value="pending">در انتظار</option>
            <option value="failed">ناموفق</option>
            <option value="refunded">بازگشتی</option>
        </select>
        <div wire:loading wire:target="search, status" class="flex items-center gap-2 text-xs text-brand-600 dark:text-brand-400">
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
                            <th>شناسه تراکنش</th>
                            <th>مشتری</th>
                            <th class="hidden md:table-cell">پکیج</th>
                            <th class="hidden lg:table-cell">نسخه</th>
                            <th>مبلغ</th>
                            <th class="hidden md:table-cell">درگاه</th>
                            <th>وضعیت</th>
                            <th class="hidden sm:table-cell">تاریخ پرداخت</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->records as $purchase)
                            <tr wire:key="purchase-{{ $purchase->id }}">
                                <td>
                                    <a href="{{ route('admin.purchases.show', $purchase) }}" wire:navigate dir="ltr"
                                       class="font-mono text-xs font-bold text-brand-600 transition-colors hover:underline dark:text-brand-400">
                                        {{ $purchase->transaction_id }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('admin.customers.index') }}?search={{ urlencode($purchase->customer?->name ?? '') }}" wire:navigate
                                       class="group flex items-center gap-3">
                                        <x-avatar :name="$purchase->customer?->name ?? '؟'" size="sm" />
                                        <span class="truncate text-sm font-medium text-zinc-700 transition-colors group-hover:text-brand-600 dark:text-zinc-200 dark:group-hover:text-brand-400">
                                            {{ $purchase->customer?->name ?? '—' }}
                                        </span>
                                    </a>
                                </td>
                                <td class="hidden md:table-cell">
                                    <div class="flex items-center gap-2">
                                        <x-icon name="package" class="size-4 shrink-0 text-zinc-400" />
                                        <span class="truncate text-sm text-zinc-600 dark:text-zinc-300">{{ $purchase->package?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="hidden lg:table-cell">
                                    @if($purchase->version)
                                        <x-badge variant="neutral" class="font-mono" dir="ltr">v{{ $purchase->version->version }}</x-badge>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-sm font-black tabular-nums text-zinc-800 dark:text-zinc-100">
                                    {{ money($purchase->amount) }}
                                </td>
                                <td class="hidden md:table-cell">
                                    <x-badge variant="neutral" class="font-mono uppercase">{{ $purchase->gateway ?? '—' }}</x-badge>
                                </td>
                                <td>
                                    @php($variant = match($purchase->status) {
                                        'paid' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        'refunded' => 'neutral',
                                        default => 'neutral',
                                    })
                                    @php($label = match($purchase->status) {
                                        'paid' => 'موفق',
                                        'pending' => 'در انتظار',
                                        'failed' => 'ناموفق',
                                        'refunded' => 'بازگشتی',
                                        default => $purchase->status,
                                    })
                                    <x-badge :variant="$variant">{{ $label }}</x-badge>
                                </td>
                                <td class="hidden whitespace-nowrap text-xs tabular-nums text-zinc-500 dark:text-zinc-400 sm:table-cell">
                                    {{ $purchase->paid_at ? verta_date($purchase->paid_at, 'Y/m/d') : '—' }}
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.purchases.show', $purchase) }}" wire:navigate
                                           class="rounded-lg p-2 text-zinc-400 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400"
                                           title="مشاهده">
                                            <x-icon name="eye" class="size-4.5" />
                                        </a>
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
            <x-empty icon="receipt" title="خریدی یافت نشد" description="پرداخت‌های مشتریان از طریق API فروش اینجا نمایش داده می‌شوند یا فیلترها را تغییر دهید." />
        @endif
    </div>
</div>
