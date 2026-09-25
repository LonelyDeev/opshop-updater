<div class="space-y-6">
    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-black text-zinc-900 dark:text-zinc-50">لاگ‌های سیستم</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">آخرین رویدادها و خطاهای ثبت‌شده در فایل لاگ سیستم؛ برای مشاهده جزئیات روی هر ردیف کلیک کنید.</p>
        </div>
        <div class="flex items-center gap-2">
            <x-btn variant="secondary" icon="download" wire:click="download">دانلود لاگ</x-btn>
            <x-btn variant="danger-soft" icon="trash" wire:click="$set('confirmClear', true)">پاک کردن لاگ</x-btn>
        </div>
    </div>

    {{-- bulk toolbar --}}
    @if($selectedIds)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-rose-50 p-3 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:ring-rose-400/20">
            <div class="flex items-center gap-2 text-sm font-bold text-rose-700 dark:text-rose-400">
                <x-icon name="check-square" class="size-4.5" />
                {{ fa_num(count($selectedIds)) }} ورودی لاگ انتخاب شده است
            </div>
            <div class="flex items-center gap-2">
                <x-btn variant="secondary" size="sm" wire:click="clearSelection">انصراف از انتخاب</x-btn>
                <x-btn variant="danger" icon="trash" size="sm" wire:click="$set('confirmingBulkDelete', true)">حذف گروهی</x-btn>
            </div>
        </div>
    @endif

    {{-- terminal card --}}
    <div class="card overflow-hidden">

        {{-- terminal header --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-3 rounded-t-2xl bg-zinc-900 px-4 py-3 sm:px-5 dark:bg-zinc-950">
            <div class="flex items-center gap-1.5">
                <span class="size-3 rounded-full bg-rose-500"></span>
                <span class="size-3 rounded-full bg-amber-400"></span>
                <span class="size-3 rounded-full bg-emerald-500"></span>
            </div>
            <span class="font-mono text-xs font-semibold tracking-wide text-zinc-100" dir="ltr">system.log</span>
            @if($this->fileSize)
                <span class="hidden font-mono text-[11px] text-zinc-500 md:inline" dir="ltr">{{ bytes_human($this->fileSize) }}</span>
            @endif

            <div class="ms-auto flex flex-wrap items-center gap-2">
                <select wire:model.live="level" dir="ltr"
                        class="h-9 cursor-pointer rounded-lg border border-zinc-700 bg-zinc-800 px-2.5 font-mono text-xs text-zinc-200 focus:border-brand-500 focus:outline-none">
                    <option value="">همه سطوح</option>
                    @foreach($this->levels as $lvl)
                        <option value="{{ $lvl }}">{{ strtoupper($lvl) }}</option>
                    @endforeach
                </select>
                <div class="relative">
                    <x-icon name="arrow-up-down" class="pointer-events-none absolute start-3 top-1/2 size-3.5 -translate-y-1/2 text-zinc-500" />
                    <select wire:model.live="sort" aria-label="ترتیب نمایش"
                            class="h-9 cursor-pointer rounded-lg border border-zinc-700 bg-zinc-800 ps-9 pe-2.5 font-mono text-xs text-zinc-200 focus:border-brand-500 focus:outline-none">
                        <option value="newest">جدیدترین</option>
                        <option value="oldest">قدیمی‌ترین</option>
                        <option value="level">بر اساس سطح</option>
                    </select>
                </div>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-3.5 -translate-y-1/2 text-zinc-500" />
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="جستجو…" dir="ltr"
                           class="h-9 w-40 rounded-lg border border-zinc-700 bg-zinc-800 ps-9 pe-3 font-mono text-xs text-zinc-100 placeholder:text-zinc-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-52" />
                </div>
                <div wire:loading wire:target="search, level, sort" class="flex items-center gap-1.5 font-mono text-[11px] text-brand-400">
                    <x-icon name="loader" class="size-3.5 animate-spin" />
                    …
                </div>
            </div>
        </div>

        {{-- entries --}}
        @if($this->records->count())
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800/70">
                @foreach($this->records as $key => $log)
                    @php
                        $variant = match(strtolower($log['level'])) {
                            'error' => 'danger',
                            'warning' => 'warning',
                            'info' => 'info',
                            default => 'neutral',
                        };
                    @endphp
                    <div wire:key="log-{{ $key }}" x-data="{ open: false }">
                        <div class="flex">
                            <div class="flex items-center px-4">
                                <input type="checkbox" class="checkbox" aria-label="انتخاب این رویداد"
                                       wire:model.live="selectedIds" value="{{ $key }}" />
                            </div>
                            <button type="button" @click="open = !open"
                                    class="flex flex-1 cursor-pointer items-start gap-3 px-4 py-3 text-start transition-colors hover:bg-zinc-50 sm:px-5 dark:hover:bg-zinc-800/50">
                                <x-badge :variant="$variant">{{ $log['level'] }}</x-badge>
                                <span class="hidden shrink-0 font-mono text-xs leading-5 text-zinc-500 tabular-nums sm:block sm:w-44 dark:text-zinc-400" dir="ltr">{{ $log['date'] }}</span>
                                <span class="min-w-0 flex-1 break-all font-mono text-xs leading-5 text-zinc-700 dark:text-zinc-300" dir="ltr">{{ $log['message'] }}</span>
                                <span class="mt-0.5 shrink-0 text-zinc-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                                    <x-icon name="chevron-down" class="size-4" />
                                </span>
                            </button>
                            <button type="button" wire:click="$set('deleteKey', {{ $key }})"
                                    class="flex items-center px-4 text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400 sm:px-5"
                                    title="حذف ورودی">
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>

                        <div x-show="open" x-cloak class="border-t border-zinc-100 bg-zinc-50/60 px-4 py-3 sm:px-5 dark:border-zinc-800/70 dark:bg-zinc-900/60">
                            <pre class="max-h-64 overflow-auto rounded-xl bg-zinc-950 p-3.5 font-mono text-[11px] leading-5 whitespace-pre-wrap break-all text-zinc-300" dir="ltr">{{ $log['message'] }}@if($log['context'])

{{ $log['context'] }}@endif</pre>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- pagination (RTL override) --}}
            <div class="border-t border-zinc-100 px-4 py-3 sm:px-5 dark:border-zinc-800">
                {{ $this->records->links() }}
            </div>
        @else
            <x-empty icon="terminal" title="لاگی ثبت نشد" description="هیچ رویدادی در فایل لاگ ثبت نشده است یا با فیلترهای فعلی منطبق نیست.">
                @if($this->search || $this->level)
                    <x-btn variant="soft" icon="refresh-cw" wire:click="resetFilters">بازنشانی فیلترها</x-btn>
                @endif
            </x-empty>
        @endif
    </div>

    {{-- clear confirm --}}
    <x-modal wire:model="confirmClear" title="پاک کردن لاگ‌ها" size="sm">
        @if($confirmClear)
            <div class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl bg-rose-50 p-4 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20">
                    <x-icon name="alert-triangle" class="size-6 shrink-0" />
                    <p class="text-sm leading-6">آیا از پاک کردن <strong>تمام لاگ‌های سیستم</strong> مطمئن هستید؟ این عمل قابل بازگشت نیست.</p>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-btn variant="secondary" wire:click="$set('confirmClear', false)">انصراف</x-btn>
                    <x-btn variant="danger" icon="trash" wire:click="clear" :loading="true">پاک کردن قطعی</x-btn>
                </div>
            </div>
        @endif
    </x-modal>

    {{-- single delete confirm --}}
    <x-modal wire:model="deleteKey" title="حذف ورودی لاگ" size="sm">
        @if($deleteKey !== null)
            @php($target = $this->records->items()[$deleteKey] ?? null)
            <div class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl bg-rose-50 p-4 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20">
                    <x-icon name="trash" class="size-6 shrink-0" />
                    <p class="text-sm leading-6 break-all">
                        ورودی انتخاب‌شده برای همیشه از فایل لاگ حذف شود؟ این عمل قابل بازگشت نیست.
                        @if($target)
                            <span class="mt-1 block font-mono text-xs text-rose-600 dark:text-rose-300" dir="ltr">{{ \Illuminate\Support\Str::limit($target['message'], 80) }}</span>
                        @endif
                    </p>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-btn variant="secondary" wire:click="$set('deleteKey', null)">انصراف</x-btn>
                    <x-btn variant="danger" icon="trash" wire:click="delete" :loading="true">حذف قطعی</x-btn>
                </div>
            </div>
        @endif
    </x-modal>

    {{-- bulk delete confirm --}}
    <x-modal wire:model="confirmingBulkDelete" title="حذف گروهی ورودی‌های لاگ" size="sm">
        @if($confirmingBulkDelete)
            <div class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl bg-rose-50 p-4 text-rose-700 ring-1 ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-400/20">
                    <x-icon name="trash" class="size-6 shrink-0" />
                    <p class="text-sm leading-6">
                        <strong>{{ fa_num(count($selectedIds)) }}</strong> ورودی لاگ انتخاب‌شده برای همیشه از فایل حذف شود؟
                        این عمل قابل بازگشت نیست.
                    </p>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-btn variant="secondary" wire:click="clearSelection">انصراف</x-btn>
                    <x-btn variant="danger" icon="trash" wire:click="bulkDelete" :loading="true">حذف {{ fa_num(count($selectedIds)) }} ورودی</x-btn>
                </div>
            </div>
        @endif
    </x-modal>
</div>
