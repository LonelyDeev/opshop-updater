@php($plan = $request->plan)
@php($approved = $request->status === \App\Models\SubscriptionRequest::STATUS_APPROVED)
@php($rejected = $request->status === \App\Models\SubscriptionRequest::STATUS_REJECTED)
@php($awaitingPayment = !$approved && !$rejected && $request->payment_status === \App\Models\SubscriptionRequest::PAYMENT_PENDING)
@php($paymentFailed = !$approved && !$rejected && $request->payment_status === \App\Models\SubscriptionRequest::PAYMENT_FAILED)

<div class="space-y-6" @if($request->status === \App\Models\SubscriptionRequest::STATUS_PENDING) wire:poll.60s="refreshStatus" @endif>

    {{-- ============ Breadcrumb ============ --}}
    <nav class="mx-auto w-full max-w-3xl px-4 pt-6 sm:px-6" aria-label="مسیر">
        <ol class="flex items-center gap-1.5 text-xs text-zinc-500">
            <li>
                <a href="{{ route('shop.home') }}" wire:navigate class="inline-flex items-center gap-1 font-medium transition-colors hover:text-brand-600">
                    <x-icon name="home" class="size-3.5" />
                    فروشگاه
                </a>
            </li>
            <li aria-hidden="true"><x-icon name="chevron-left" class="size-3.5 text-zinc-300" /></li>
            <li>
                <a href="{{ route('shop.plans') }}" wire:navigate class="font-medium transition-colors hover:text-brand-600">طرح‌های اشتراک</a>
            </li>
            <li aria-hidden="true"><x-icon name="chevron-left" class="size-3.5 text-zinc-300" /></li>
            <li class="font-bold text-zinc-700 dark:text-zinc-200">وضعیت درخواست</li>
        </ol>
    </nav>

    {{-- ============ Page header ============ --}}
    <section class="mx-auto flex w-full max-w-3xl flex-wrap items-center justify-between gap-3 px-4 sm:px-6">
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-black text-zinc-900 sm:text-2xl dark:text-zinc-50">وضعیت درخواست اشتراک</h1>
            @if($plan)
                <x-badge variant="primary" icon="crown">{{ $plan->name }}</x-badge>
            @endif
            <span class="rounded-lg bg-zinc-100 px-2 py-1 font-mono text-xs font-bold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400" dir="ltr">
                #{{ fa_num($request->id) }}
            </span>
        </div>

        <x-btn variant="secondary" size="sm" icon="refresh-cw" wire:click="refreshStatus" :loading="true">
            به‌روزرسانی وضعیت
        </x-btn>
    </section>

    {{-- ============ State hero ============ --}}
    @if($approved)
        {{-- تأیید شده: طرح فعال --}}
        <section class="shop-hero-success relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="shop-hero-glow absolute -top-24 -end-24 size-96 rounded-full opacity-50 blur-3xl"></div>
            </div>
            <div class="relative mx-auto flex w-full max-w-3xl flex-col items-center gap-4 px-4 py-10 text-center sm:px-6 sm:py-12">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/25">
                    <x-icon name="check-circle" class="size-8" />
                </span>
                <h2 class="text-2xl font-black text-white sm:text-3xl">طرح شما فعال شد 🎉</h2>
                <p class="max-w-xl text-sm leading-7 text-emerald-50/80">
                    درخواست شما تأیید شد و برای همه پکیج‌های «{{ $plan?->name }}» لایسنس صادر شد؛ کلیدها را در پایین همین صفحه می‌بینید.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-emerald-50 ring-1 ring-white/20">
                        <x-icon name="key" class="size-3.5" />
                        {{ fa_num($this->licenses->count()) }} لایسنس فعال
                    </span>
                    @if($request->approved_at)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-emerald-50 ring-1 ring-white/20">
                            <x-icon name="calendar" class="size-3.5" />
                            تاریخ تأیید: {{ fa_num(verta_date($request->approved_at, 'Y/m/d H:i')) }}
                        </span>
                    @endif
                </div>
            </div>
        </section>
    @elseif($rejected)
        {{-- رد شده --}}
        <section class="shop-hero-fail relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="shop-hero-glow absolute -top-24 -end-24 size-96 rounded-full opacity-30 blur-3xl"></div>
            </div>
            <div class="relative mx-auto flex w-full max-w-3xl flex-col items-center gap-4 px-4 py-10 text-center sm:px-6 sm:py-12">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/25">
                    <x-icon name="x-circle" class="size-8" />
                </span>
                <h2 class="text-2xl font-black text-white sm:text-3xl">درخواست شما رد شد</h2>
                @if($request->admin_note)
                    <div class="max-w-xl rounded-xl bg-amber-50/95 p-4 text-start ring-1 ring-amber-500/30 dark:bg-amber-400/15 dark:ring-amber-300/20">
                        <p class="flex items-start gap-2 text-sm leading-6 text-amber-800 dark:text-amber-100">
                            <x-icon name="info" class="mt-0.5 size-4 shrink-0" />
                            <span>
                                <span class="font-black">یادداشت مدیر:</span>
                                {{ $request->admin_note }}
                            </span>
                        </p>
                    </div>
                @endif
                <p class="max-w-xl text-sm leading-7 text-rose-50/80">
                    برای پیگیری با پشتیبانی تماس بگیرید.
                </p>
                <a href="{{ route('shop.plans') }}" wire:navigate>
                    <x-btn variant="secondary" icon="crown">مشاهده طرح‌های دیگر</x-btn>
                </a>
            </div>
        </section>
    @elseif($awaitingPayment)
        {{-- در انتظار پرداخت --}}
        <section class="shop-hero-pending relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="shop-hero-glow absolute -top-24 -end-24 size-96 rounded-full opacity-30 blur-3xl"></div>
            </div>
            <div class="relative mx-auto flex w-full max-w-3xl flex-col items-center gap-4 px-4 py-10 text-center sm:px-6 sm:py-12">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/25">
                    <x-icon name="clock" class="size-8" />
                </span>
                <h2 class="text-2xl font-black text-white sm:text-3xl">در انتظار پرداخت</h2>
                <p class="max-w-xl text-sm leading-7 text-amber-50/80">
                    پرداخت این درخواست هنوز انجام نشده است. پس از پرداخت، درخواست در انتظار تأیید مدیر قرار می‌گیرد.
                </p>
                <div class="flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2 ring-1 ring-white/20">
                    <span class="text-xs text-amber-50/80">مبلغ قابل پرداخت</span>
                    <span class="text-sm font-black tabular-nums text-white">{{ money($request->amount) }}</span>
                </div>
                @if($request->payment_url)
                    <a href="{{ $request->payment_url }}" target="_blank" rel="noopener">
                        <x-btn variant="secondary" icon="credit-card">ادامه پرداخت</x-btn>
                    </a>
                @endif
            </div>
        </section>
    @elseif($paymentFailed)
        {{-- پرداخت ناموفق --}}
        <section class="shop-hero-fail relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="shop-hero-glow absolute -top-24 -end-24 size-96 rounded-full opacity-30 blur-3xl"></div>
            </div>
            <div class="relative mx-auto flex w-full max-w-3xl flex-col items-center gap-4 px-4 py-10 text-center sm:px-6 sm:py-12">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/25">
                    <x-icon name="x-circle" class="size-8" />
                </span>
                <h2 class="text-2xl font-black text-white sm:text-3xl">پرداخت ناموفق</h2>
                <p class="max-w-xl text-sm leading-7 text-rose-50/80">
                    {{ $request->meta['fail_reason'] ?? 'متأسفانه پرداخت شما تأیید نشد. مبلغی از حساب شما کسر نشده است؛ می‌توانید دوباره تلاش کنید.' }}
                </p>
                <a href="{{ route('shop.plans') }}" wire:navigate>
                    <x-btn variant="secondary" icon="refresh-cw">تلاش مجدد خرید</x-btn>
                </a>
            </div>
        </section>
    @else
        {{-- ثبت شده و در انتظار تأیید مدیر --}}
        <section class="shop-hero-pending relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="shop-hero-glow absolute -top-24 -end-24 size-96 rounded-full opacity-30 blur-3xl"></div>
            </div>
            <div class="relative mx-auto flex w-full max-w-3xl flex-col items-center gap-4 px-4 py-10 text-center sm:px-6 sm:py-12">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/25">
                    <x-icon name="history" class="size-8" />
                </span>
                <h2 class="text-2xl font-black text-white sm:text-3xl">درخواست شما ثبت شد و در انتظار تأیید مدیر است</h2>
                <p class="max-w-xl text-sm leading-7 text-amber-50/80">
                    {{ $request->isPaid() ? 'پرداخت شما دریافت شد؛' : 'این طرح رایگان است؛' }}
                    پس از تأیید، لایسنس‌ها همین‌جا نمایش داده می‌شوند.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-amber-50 ring-1 ring-white/20">
                        <x-icon name="calendar" class="size-3.5" />
                        تاریخ درخواست: {{ fa_num(verta_date($request->created_at, 'Y/m/d H:i')) }}
                    </span>
                    @if($request->paid_at)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-amber-50 ring-1 ring-white/20">
                            <x-icon name="check-circle" class="size-3.5" />
                            تاریخ پرداخت: {{ fa_num(verta_date($request->paid_at, 'Y/m/d H:i')) }}
                        </span>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ============ Details ============ --}}
    <section class="mx-auto w-full max-w-3xl px-4 sm:px-6">
        <x-card title="جزئیات درخواست" subtitle="اطلاعات ثبت‌شده برای این درخواست اشتراک">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">طرح</p>
                    <p class="mt-1.5 text-sm font-bold text-zinc-800 dark:text-zinc-100">
                        @if($plan)
                            {{ $plan->name }}
                            <span class="text-xs font-medium text-zinc-400">({{ fa_num($plan->duration_label) }})</span>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </p>
                </div>

                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">مبلغ</p>
                    <p class="mt-1.5 text-sm font-black tabular-nums text-zinc-800 dark:text-zinc-100">
                        @if($request->amount <= 0)
                            <span class="text-emerald-600">رایگان</span>
                        @else
                            {{ money($request->amount) }}
                        @endif
                    </p>
                </div>

                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">درگاه پرداخت</p>
                    <div class="mt-1.5">
                        @if($request->gateway)
                            @php($gatewayLabel = config('general.supported_gateways.' . $request->gateway) ?? $request->gateway)
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge variant="neutral">{{ $gatewayLabel }}</x-badge>
                                @if($gatewayLabel !== $request->gateway)
                                    <span class="font-mono text-xs text-zinc-400" dir="ltr">{{ strtoupper($request->gateway) }}</span>
                                @endif
                            </div>
                        @else
                            <span class="text-sm text-zinc-400">— (طرح رایگان)</span>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">شماره تراکنش</p>
                    <div class="mt-1.5 flex items-center gap-2">
                        @if($request->transaction_id)
                            <span class="min-w-0 truncate font-mono text-sm font-bold text-zinc-800 dark:text-zinc-100" dir="ltr">{{ $request->transaction_id }}</span>
                            <button x-data type="button"
                                    @click="navigator.clipboard.writeText('{{ $request->transaction_id }}'); $dispatch('toast', {message: 'کپی شد'})"
                                    class="shrink-0 rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-brand-600 dark:hover:bg-zinc-700 dark:hover:text-brand-400"
                                    title="کپی شماره تراکنش">
                                <x-icon name="copy" class="size-4" />
                            </button>
                        @else
                            <span class="text-sm text-zinc-400">—</span>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">وضعیت</p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <x-badge :variant="$request->status_badge">{{ $request->status_label }}</x-badge>
                        <x-badge :variant="$request->payment_badge">پرداخت: {{ $request->payment_status_label }}</x-badge>
                    </div>
                </div>

                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">تاریخ درخواست</p>
                    <p class="mt-1.5 text-sm font-bold tabular-nums text-zinc-800 dark:text-zinc-100">
                        {{ fa_num(verta_date($request->created_at, 'Y/m/d H:i')) }}
                    </p>
                </div>

                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">تاریخ پرداخت</p>
                    <p class="mt-1.5 text-sm font-bold tabular-nums text-zinc-800 dark:text-zinc-100">
                        {{ $request->paid_at ? fa_num(verta_date($request->paid_at, 'Y/m/d H:i')) : '—' }}
                    </p>
                </div>

                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">تاریخ تأیید</p>
                    <p class="mt-1.5 text-sm font-bold tabular-nums text-zinc-800 dark:text-zinc-100">
                        {{ $request->approved_at ? fa_num(verta_date($request->approved_at, 'Y/m/d H:i')) : '—' }}
                    </p>
                </div>
            </div>

            {{-- پکیج‌های طرح --}}
            @if($plan && $plan->packages->isNotEmpty())
                <div class="mt-5 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                    <p class="mb-2.5 flex items-center gap-1.5 text-xs font-bold text-zinc-500 dark:text-zinc-400">
                        <x-icon name="package" class="size-3.5" />
                        پکیج‌های این طرح (پس از تأیید مدیر فعال می‌شوند)
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($plan->packages as $package)
                            <a href="{{ route('shop.package', $package->slug) }}" wire:key="planpkg-{{ $package->id }}" wire:navigate
                               class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-600 ring-1 ring-zinc-200 transition-colors hover:bg-brand-50 hover:text-brand-700 hover:ring-brand-300 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700 dark:hover:bg-brand-500/10 dark:hover:text-brand-400">
                                {{ $package->name }}
                                <x-icon name="arrow-left" class="size-3" />
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-card>
    </section>

    {{-- ============ Licenses (پس از تأیید) ============ --}}
    @if($this->licenses->isNotEmpty())
        <section class="mx-auto w-full max-w-3xl px-4 sm:px-6">
            <x-card title="لایسنس‌های شما" subtitle="این کلیدها برای فعال‌سازی و به‌روزرسانی پکیج‌های طرح استفاده می‌شوند">
                <div class="space-y-4">
                    @foreach($this->licenses as $license)
                        <div wire:key="lic-{{ $license->id }}" class="space-y-3 rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 dark:bg-zinc-800/60 dark:ring-zinc-700">
                            {{-- پکیج + لینک --}}
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-500/10 text-brand-600 dark:text-brand-400">
                                        <x-icon name="package" class="size-5" />
                                    </span>
                                    <p class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ $license->package?->name }}</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($license->expires_at === null)
                                        <x-badge variant="info" icon="clock">اعتبار نامحدود</x-badge>
                                    @else
                                        @php($remaining = (int) $license->days_remaining)
                                        @if($license->isExpired())
                                            <x-badge variant="danger" icon="clock">منقضی شده</x-badge>
                                        @elseif($remaining < 14)
                                            <x-badge variant="warning" icon="clock">{{ fa_num($remaining) }} روز باقی‌مانده</x-badge>
                                        @else
                                            <x-badge variant="neutral" icon="clock">{{ fa_num($remaining) }} روز باقی‌مانده</x-badge>
                                        @endif
                                    @endif

                                    @if($license->package)
                                        <a href="{{ route('shop.package', $license->package->slug) }}" wire:navigate
                                           class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 transition-colors hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                                            مشاهده پکیج
                                            <x-icon name="arrow-left" class="size-3.5" />
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- کلید لایسنس + کپی --}}
                            <div class="flex items-center gap-3 rounded-xl bg-brand-50 p-3.5 ring-1 ring-brand-600/10 dark:bg-brand-500/10 dark:ring-brand-400/20" dir="ltr">
                                <span class="min-w-0 flex-1 break-all font-mono text-sm font-black tracking-wider text-brand-700 dark:text-brand-300">{{ $license->license_key }}</span>
                                <button x-data type="button"
                                        @click="navigator.clipboard.writeText('{{ $license->license_key }}'); $dispatch('toast', {message: 'کپی شد'})"
                                        class="flex shrink-0 items-center gap-1.5 rounded-lg bg-white px-2.5 py-1.5 text-xs font-medium text-zinc-500 ring-1 ring-zinc-200 transition-colors hover:text-brand-600 hover:ring-brand-300 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700 dark:hover:text-brand-400"
                                        title="کپی کلید لایسنس">
                                    <x-icon name="copy" class="size-3.5" />
                                    کپی
                                </button>
                            </div>

                            {{-- تاریخ انقضا --}}
                            <p class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                                <span>تاریخ صدور: {{ fa_num(verta_date($license->created_at, 'Y/m/d')) }}</span>
                                <span>
                                    تاریخ انقضا:
                                    <span class="font-bold {{ $license->isExpired() ? 'text-rose-600' : 'text-zinc-700 dark:text-zinc-200' }}">
                                        {{ $license->expires_at ? fa_num(verta_date($license->expires_at, 'Y/m/d')) : 'نامحدود' }}
                                    </span>
                                </span>
                            </p>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </section>
    @endif

    {{-- ============ CTA ============ --}}
    <section class="mx-auto flex w-full max-w-3xl flex-wrap items-center justify-center gap-3 px-4 pb-12 sm:px-6">
        <a href="{{ route('shop.home') }}" wire:navigate>
            <x-btn variant="secondary" icon="home">بازگشت به فروشگاه</x-btn>
        </a>
        <a href="{{ route('shop.plans') }}" wire:navigate>
            <x-btn variant="soft" icon="crown">مشاهده طرح‌ها</x-btn>
        </a>
    </section>
</div>
