<div class="space-y-6">
    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-black text-zinc-900 dark:text-zinc-50">گزارش‌ها</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">تحلیل جامع مشتریان، آپدیت‌ها و فروش — به‌روز تا {{ verta_date(now(), 'l d F Y') }}</p>
        </div>
        <x-btn variant="secondary" icon="refresh-cw" wire:click="$refresh">به‌روزرسانی گزارش‌ها</x-btn>
    </div>

    @php($tabs = [
        ['id' => 'overview', 'label' => 'نمای کلی', 'icon' => 'bar-chart'],
        ['id' => 'customers', 'label' => 'مشتریان', 'icon' => 'users'],
        ['id' => 'updates', 'label' => 'آپدیت‌ها', 'icon' => 'git-branch'],
        ['id' => 'sales', 'label' => 'فروش', 'icon' => 'wallet'],
    ])
    <x-tabs :tabs="$tabs">
        <x-slot:overview>
            <div class="space-y-6">
                {{-- stat cards --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($this->overviewStats as $stat)
                        <x-stat :label="$stat['label']" :value="fa_num($stat['value'])" :icon="$stat['icon']"
                                :variant="$stat['variant']" :hint="$stat['hint']" :href="$stat['href']" />
                    @endforeach
                </div>

                {{-- customers chart + subscription pills --}}
                <x-card padding="p-5">
                    <x-chart :title="$this->customerChart['title']" :series="$this->customerChart['series']" color="brand" :height="200" />

                    <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <span class="me-1 flex items-center gap-1.5 text-xs font-semibold text-zinc-500 dark:text-zinc-400">
                            <x-icon name="ticket" class="size-3.5" />
                            وضعیت اشتراک‌ها:
                        </span>
                        <x-badge variant="success" icon="check-circle">فعال {{ fa_num($this->subscriptionBreakdown['active']) }}</x-badge>
                        <x-badge variant="danger" icon="alert-circle">منقضی شده {{ fa_num($this->subscriptionBreakdown['expired']) }}</x-badge>
                        <x-badge variant="neutral" icon="clock">غیرفعال {{ fa_num($this->subscriptionBreakdown['inactive']) }}</x-badge>
                    </div>
                </x-card>

                {{-- key metrics table --}}
                <x-card title="جدول شاخص‌های کلیدی" subtitle="خلاصه وضعیت کل سیستم">
                    <div class="table-wrap ring-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>شاخص</th>
                                    <th class="text-end">مقدار</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->summaryMetrics as $group)
                                    <tr wire:key="metric-group-{{ $loop->index }}">
                                        <td colspan="2" class="pt-5 pb-1.5">
                                            <span class="inline-flex items-center gap-2 rounded-lg bg-zinc-100 px-2.5 py-1.5 text-xs font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                                <x-icon :name="$group['icon']" class="size-4 text-brand-600 dark:text-brand-400" />
                                                {{ $group['title'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    @foreach($group['rows'] as $row)
                                        <tr wire:key="metric-{{ $loop->parent->index }}-{{ $loop->index }}">
                                            <td class="text-sm text-zinc-600 dark:text-zinc-300">{{ $row['label'] }}</td>
                                            <td class="text-end font-bold tabular-nums text-zinc-800 dark:text-zinc-100">{{ $row['value'] }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>
            </x-slot:overview>
        <x-slot:customers>
            <div class="space-y-6">
                {{-- date range filters --}}
                <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
                    <x-field label="از تاریخ عضویت" class="sm:w-44">
                        <x-input type="date" wire:model.live="from" dir="ltr" icon="calendar" />
                    </x-field>
                    <x-field label="تا تاریخ" class="sm:w-44">
                        <x-input type="date" wire:model.live="to" dir="ltr" icon="calendar" />
                    </x-field>
                    @if($from || $to)
                        <x-btn variant="ghost" icon="x" wire:click="resetFilters">حذف فیلترها</x-btn>
                    @endif
                    <div class="hidden flex-1 sm:block"></div>
                    <div class="flex items-center gap-2 text-xs text-zinc-400">
                        <x-icon name="calendar" class="size-3.5" />
                        بازه بر اساس تاریخ عضویت (میلادی)
                    </div>
                    <div wire:loading wire:target="from, to" class="flex items-center gap-2 text-xs text-brand-600 dark:text-brand-400">
                        <x-icon name="loader" class="size-4 animate-spin" />
                        در حال فیلتر…
                    </div>
                </div>

                {{-- customers table --}}
                <div class="card overflow-hidden">
                    @if($this->customersReport->count())
                        <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3 dark:border-zinc-800">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                <span class="font-bold tabular-nums text-zinc-700 dark:text-zinc-200">{{ fa_num($this->customersReport->total()) }}</span>
                                مشتری در بازه انتخابی
                            </p>
                            <x-icon name="users" class="size-4 text-zinc-300 dark:text-zinc-600" />
                        </div>
                        <div class="table-wrap ring-0">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>مشتری</th>
                                        <th>اشتراک‌ها</th>
                                        <th>مجموع پرداخت</th>
                                        <th class="hidden sm:table-cell">تاریخ عضویت</th>
                                        <th>وضعیت</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->customersReport as $customer)
                                        <tr wire:key="report-customer-{{ $customer->id }}">
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <x-avatar :name="$customer->name" size="sm" />
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $customer->name }}</p>
                                                        <p class="truncate text-xs text-zinc-500">{{ $customer->email }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="tabular-nums text-zinc-600 dark:text-zinc-300">{{ fa_num($customer->subscriptions_count) }}</td>
                                            <td class="font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">
                                                @if($customer->subscriptions_spent + $customer->purchases_spent > 0)
                                                    {{ money($customer->subscriptions_spent + $customer->purchases_spent, false) }}
                                                    <span class="text-[10px] font-normal text-zinc-400">تومان</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="hidden text-sm text-zinc-500 sm:table-cell">{{ verta_date($customer->created_at) }}</td>
                                            <td>
                                                <x-badge variant="{{ $customer->status === 'active' ? 'success' : 'danger' }}">
                                                    {{ $customer->status === 'active' ? 'فعال' : 'غیرفعال' }}
                                                </x-badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            {{ $this->customersReport->links() }}
                        </div>
                    @else
                        <x-empty icon="users" title="مشتری‌ای در این بازه یافت نشد" description="بازه تاریخ را تغییر دهید یا فیلترها را حذف کنید.">
                            @if($from || $to)
                                <x-btn variant="soft" icon="x" wire:click="resetFilters">حذف فیلترها</x-btn>
                            @endif
                        </x-empty>
                    @endif
                </div>
            </div>
            </x-slot:customers>
        <x-slot:updates>
            <div class="space-y-6">
                {{-- filters --}}
                <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <x-select wire:model.live="project" :options="$this->projects->pluck('name', 'id')" placeholder="همه پروژه‌ها" class="sm:w-48" />
                    <x-select wire:model.live="status" :options="\App\Models\Update::getStatuses()" placeholder="همه وضعیت‌ها" class="sm:w-44" />
                    @if($project || $status)
                        <x-btn variant="ghost" icon="x" wire:click="resetFilters">حذف فیلترها</x-btn>
                    @endif
                    <div class="hidden flex-1 sm:block"></div>
                    <div wire:loading wire:target="project, status" class="flex items-center gap-2 text-xs text-brand-600 dark:text-brand-400">
                        <x-icon name="loader" class="size-4 animate-spin" />
                        در حال فیلتر…
                    </div>
                </div>

                {{-- updates table --}}
                <div class="card overflow-hidden">
                    @if($this->updatesReport->count())
                        <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-3 dark:border-zinc-800">
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                <span class="font-bold tabular-nums text-zinc-700 dark:text-zinc-200">{{ fa_num($this->updatesReport->total()) }}</span>
                                آپدیت
                                @if($project)
                                    <span class="text-zinc-400">در پروژه «{{ $this->projects->firstWhere('id', (int) $project)?->name }}»</span>
                                @endif
                            </p>
                            <x-icon name="git-branch" class="size-4 text-zinc-300 dark:text-zinc-600" />
                        </div>
                        <div class="table-wrap ring-0">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>عنوان</th>
                                        <th class="hidden md:table-cell">پروژه</th>
                                        <th>نسخه</th>
                                        <th class="hidden lg:table-cell">نوع</th>
                                        <th>وضعیت</th>
                                        <th class="hidden sm:table-cell">تاریخ انتشار</th>
                                        <th class="hidden lg:table-cell">حجم فایل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->updatesReport as $update)
                                        @php($statusVariant = match($update->status) {
                                            'active' => 'success',
                                            'draft' => 'warning',
                                            default => 'neutral',
                                        })
                                        @php($statusLabel = \App\Models\Update::getStatuses()[$update->status] ?? $update->status)
                                        @php($typeVariant = match($update->type) {
                                            'major' => 'primary',
                                            'minor' => 'info',
                                            default => 'neutral',
                                        })
                                        @php($typeLabel = match($update->type) {
                                            'major' => 'اصلی',
                                            'minor' => 'فرعی',
                                            default => 'اصلاحی',
                                        })
                                        <tr wire:key="report-update-{{ $update->id }}">
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                                        <x-icon name="git-branch" class="size-4" />
                                                    </div>
                                                    <p class="max-w-56 truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $update->title }}</p>
                                                </div>
                                            </td>
                                            <td class="hidden text-sm text-zinc-600 dark:text-zinc-300 md:table-cell">{{ $update->project?->name ?? '—' }}</td>
                                            <td><x-badge variant="primary"><span dir="ltr" class="font-mono">{{ $update->version }}</span></x-badge></td>
                                            <td class="hidden lg:table-cell"><x-badge :variant="$typeVariant">{{ $typeLabel }}</x-badge></td>
                                            <td><x-badge :variant="$statusVariant">{{ $statusLabel }}</x-badge></td>
                                            <td class="hidden text-sm text-zinc-500 sm:table-cell">{{ verta_date($update->release_date) }}</td>
                                            <td class="hidden text-sm tabular-nums text-zinc-500 lg:table-cell" dir="ltr">{{ $update->file_size ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
                            {{ $this->updatesReport->links() }}
                        </div>
                    @else
                        <x-empty icon="git-branch" title="آپدیتی یافت نشد" description="فیلترهای پروژه یا وضعیت را تغییر دهید.">
                            @if($project || $status)
                                <x-btn variant="soft" icon="x" wire:click="resetFilters">حذف فیلترها</x-btn>
                            @endif
                        </x-empty>
                    @endif
                </div>
            </div>
            </x-slot:updates>
        <x-slot:sales>
            <div class="space-y-6">
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                    {{-- monthly paid purchases chart --}}
                    <x-card class="xl:col-span-2" padding="p-5">
                        <x-chart :title="$this->salesChart['title']" :series="$this->salesChart['series']" color="teal" :height="200" />
                    </x-card>

                    {{-- revenue summary --}}
                    <x-card title="خلاصه درآمد">
                        <div class="space-y-4">
                            <div class="rounded-2xl bg-gradient-to-br from-brand-500 to-brand-600 p-5 text-white shadow-lg shadow-brand-500/20">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-medium text-brand-50/90">درآمد کل (پرداخت‌شده)</p>
                                    <x-icon name="wallet" class="size-5 text-brand-50/90" />
                                </div>
                                <p class="mt-2 text-2xl font-black tabular-nums">{{ money($this->revenue['total']) }}</p>
                                <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                                    <div class="rounded-xl bg-white/10 px-3 py-2.5 backdrop-blur">
                                        <p class="text-brand-50/80">اشتراک‌ها</p>
                                        <p class="mt-0.5 font-bold tabular-nums">{{ money($this->revenue['subscriptions'], false) }}</p>
                                    </div>
                                    <div class="rounded-xl bg-white/10 px-3 py-2.5 backdrop-blur">
                                        <p class="text-brand-50/80">پکیج‌ها</p>
                                        <p class="mt-0.5 font-bold tabular-nums">{{ money($this->revenue['packages'], false) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                                        <x-icon name="check-circle" class="size-4 text-emerald-500" />
                                        <p class="text-xs font-medium">خرید موفق</p>
                                    </div>
                                    <p class="mt-1.5 text-lg font-black tabular-nums text-zinc-900 dark:text-zinc-50">{{ fa_num($this->revenue['paid_purchases']) }}</p>
                                </div>
                                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                                        <x-icon name="clock" class="size-4 text-amber-500" />
                                        <p class="text-xs font-medium">در انتظار پرداخت</p>
                                    </div>
                                    <p class="mt-1.5 text-lg font-black tabular-nums text-zinc-900 dark:text-zinc-50">{{ fa_num($this->revenue['pending_purchases']) }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-xl bg-zinc-50 px-4 py-3 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                                <div class="flex items-center gap-2.5">
                                    <x-icon name="x-circle" class="size-4.5 text-rose-500" />
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">خریدهای ناموفق</p>
                                </div>
                                <p class="font-black tabular-nums text-zinc-900 dark:text-zinc-50">{{ fa_num($this->revenue['failed_purchases']) }}</p>
                            </div>
                        </div>
                    </x-card>
                </div>

                {{-- top selling packages --}}
                <x-card title="پرفروش‌ترین پکیج‌ها" subtitle="بر اساس تعداد خریدهای پرداخت‌شده">
                    @if($this->topPackages->count())
                        <div class="table-wrap ring-0">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="w-14">#</th>
                                        <th>پکیج</th>
                                        <th>خرید موفق</th>
                                        <th>درآمد</th>
                                        <th class="hidden sm:table-cell">دانلود</th>
                                        <th class="hidden md:table-cell">قیمت</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->topPackages as $package)
                                        <tr wire:key="report-package-{{ $package->id }}">
                                            <td>
                                                <span class="flex size-8 items-center justify-center rounded-xl bg-zinc-100 text-xs font-black tabular-nums text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                                    {{ fa_num($loop->iteration) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-500/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                                        <x-icon name="package" class="size-4.5" />
                                                    </div>
                                                    <p class="max-w-48 truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $package->name }}</p>
                                                </div>
                                            </td>
                                            <td class="font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">{{ fa_num($package->paid_purchases_count) }}</td>
                                            <td class="font-semibold tabular-nums text-brand-600 dark:text-brand-400">{{ money($package->paid_purchases_revenue, false) }}</td>
                                            <td class="hidden tabular-nums text-zinc-600 dark:text-zinc-300 sm:table-cell">{{ fa_num($package->downloads_count) }}</td>
                                            <td class="hidden text-sm text-zinc-600 dark:text-zinc-300 md:table-cell">
                                                {{ $package->is_free ? 'رایگان' : money($package->default_price, false) }}
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.packages.show', $package) }}" wire:navigate
                                                   class="text-zinc-400 transition-colors hover:text-brand-600" aria-label="مشاهده">
                                                    <x-icon name="chevron-left" class="size-4" />
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-empty icon="package" title="پکیجی ثبت نشده" description="پس از ثبت پکیج و فروش، پرفروش‌ها اینجا نمایش داده می‌شوند." />
                    @endif
                </x-card>
            </div>
        </x-slot:sales>
    </x-tabs>
</div>
