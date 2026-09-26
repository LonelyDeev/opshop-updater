<div class="space-y-6">

    {{-- ============ Hero ============ --}}
    <section class="shop-hero relative overflow-hidden">
        {{-- decorative glows --}}
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="shop-hero-glow absolute -top-32 -start-32 size-96 rounded-full blur-3xl"></div>
            <div class="shop-hero-glow absolute -bottom-40 -end-24 size-96 rounded-full opacity-60 blur-3xl"></div>
        </div>

        {{-- grid pattern --}}
        <div class="pointer-events-none absolute inset-0 opacity-[0.04]" aria-hidden="true"
             style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 44px 44px;"></div>

        <div class="relative mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 sm:py-16">
            <div class="max-w-2xl animate-slide-up">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-teal-100 ring-1 ring-white/20">
                    <x-icon name="sparkles" class="size-3.5" />
                    مرکز توزیع پکیج و آپدیت پروژه‌ها
                </span>
                <h1 class="shop-hero-title mt-4 text-3xl font-black leading-relaxed sm:text-4xl">
                    فروشگاه پکیج‌ها
                </h1>
                <p class="mt-3 max-w-xl text-sm leading-7 text-teal-50/80 sm:text-base">
                    پکیج‌های آماده را تهیه کنید و با «کد آپدیت» پنل مشتری، بدون نیاز به ثبت‌نام، مستقیماً خرید و دانلود کنید.
                    برای دسترسی‌های ویژه هم سراغ <a href="{{ route('shop.subscriptions') }}" wire:navigate class="font-bold text-teal-100 underline decoration-teal-100/40 underline-offset-4 transition-colors hover:decoration-teal-100">طرح‌های اشتراک</a> بروید.
                </p>

                {{-- search --}}
                <div class="mt-6 max-w-xl">
                    <x-input wire:model.live.debounce.400ms="search" type="search" icon="search"
                             placeholder="جستجوی پکیج… (نام یا نامک)" />
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Stats ============ --}}
    <section class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-center gap-4 px-4 sm:justify-start sm:px-6">
        <x-stat label="پکیج فعال" :value="fa_num($this->stats['packages'])" icon="package" variant="primary" />
        <x-stat label="نسخه فعال" :value="fa_num($this->stats['versions'])" icon="layers" variant="info" />
        <x-stat label="طرح اشتراک فعال" :value="fa_num($this->stats['plans'])" icon="crown" variant="warning" />
        <x-stat label="خرید موفق" :value="fa_num($this->stats['customers'])" icon="check-circle" variant="neutral" />
    </section>

    {{-- ============ بررسی اشتراک / بنر اشتراک فعال ============ --}}
    <section class="mx-auto w-full max-w-7xl px-4 sm:px-6">
        @php($mySubscription = $this->myActiveSubscription)
        @if($mySubscription && $mySubscription->plan)
            {{-- بنر موفق: مشتری اشتراک فعالِ طرح‌محور دارد --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-bl from-emerald-50 via-white to-teal-50 ring-1 ring-emerald-200 dark:from-zinc-900 dark:via-zinc-900 dark:to-emerald-950/40 dark:ring-emerald-500/25">
                <div class="pointer-events-none absolute -top-20 -end-16 size-56 rounded-full bg-emerald-400/10 blur-3xl" aria-hidden="true"></div>

                <div class="relative flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:p-6">
                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                        <x-icon name="badge-check" class="size-6" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                            <h2 class="text-base font-black text-emerald-800 dark:text-emerald-300">اشتراک فعال دارید</h2>
                            <x-badge variant="success" icon="crown">{{ $mySubscription->plan->name }}</x-badge>
                            @if($mySubscription->expires_at)
                                <span class="inline-flex items-center gap-1 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                    <x-icon name="calendar-clock" class="size-3.5" />
                                    اعتبار تا {{ verta_date($mySubscription->expires_at) }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1.5 text-sm leading-6 text-emerald-700 dark:text-emerald-300/80">
                            پکیج‌های این طرح برای شما رایگان‌اند؛ در فهرست پایین با نشان «رایگان با اشتراک» مشخص شده‌اند.
                        </p>
                        @if($mySubscription->plan->packages->isNotEmpty())
                            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                    <x-icon name="gift" class="size-3.5" />
                                    {{ fa_num($mySubscription->plan->packages->count()) }} پکیج رایگان:
                                </span>
                                @foreach($mySubscription->plan->packages as $pkg)
                                    <span class="rounded-lg bg-emerald-100/80 px-2 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-600/10 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-400/20">
                                        {{ \Illuminate\Support\Str::limit($pkg->name, 18) }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <x-btn variant="ghost" size="sm" icon="x" wire:click="forgetMySubscription" :loading="true"
                           class="shrink-0 self-start text-zinc-500 dark:text-zinc-400 sm:self-center">
                        فراموشی
                    </x-btn>
                </div>
            </div>
        @else
            {{-- جعبه ورودی: بررسی اشتراک --}}
            <div class="rounded-2xl bg-white p-5 ring-1 ring-zinc-200/80 dark:bg-zinc-900 dark:ring-zinc-700/60 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div class="flex min-w-0 flex-1 items-start gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                            <x-icon name="crown" class="size-6" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-base font-black text-zinc-900 dark:text-zinc-50">اشتراک فعال دارید؟</h2>
                            <p class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                                اگر طرح اشتراک خریداری کرده‌اید، کد آپدیت خود را وارد کنید تا پکیج‌های رایگانِ طرح شما در فروشگاه مشخص شوند.
                            </p>
                        </div>
                    </div>
                    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                        <div class="sm:w-56">
                            <x-input wire:model="updateCode" icon="key" placeholder="کد آپدیت خود را وارد کنید…" dir="ltr" class="text-start font-mono" />
                        </div>
                        <x-btn wire:click="checkMySubscription" icon="search" :loading="true" class="shrink-0">
                            بررسی اشتراک من
                        </x-btn>
                    </div>
                </div>
            </div>
        @endif
    </section>

    {{-- ============ Subscription plans teaser ============ --}}
    @if($this->featuredPlans->count())
        <section class="mx-auto w-full max-w-7xl px-4 sm:px-6">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-bl from-brand-50 via-white to-teal-50 ring-1 ring-brand-100 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800 dark:ring-zinc-800">
                <div class="shop-hero-glow pointer-events-none absolute -top-24 -start-24 size-72 rounded-full opacity-30 blur-3xl" aria-hidden="true"></div>

                <div class="relative p-6 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 text-lg font-black text-zinc-900 dark:text-zinc-50">
                                <x-icon name="crown" class="size-5 text-amber-500" />
                                طرح‌های اشتراک ویژه
                            </h2>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                یک اشتراک بخرید، قابلیت‌های ویژه + پکیج‌های منتخب را رایگان داشته باشید.
                            </p>
                        </div>
                        <a href="{{ route('shop.subscriptions') }}" wire:navigate>
                            <x-btn icon="arrow-left">مشاهده همه طرح‌ها</x-btn>
                        </a>
                    </div>

                    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                        @foreach($this->featuredPlans as $plan)
                            <a href="{{ route('shop.subscriptions') }}" wire:navigate
                               class="group flex flex-col gap-3 rounded-2xl bg-white/80 p-5 ring-1 ring-zinc-200/70 backdrop-blur transition-all hover:-translate-y-1 hover:shadow-card-lg dark:bg-zinc-800/70 dark:ring-zinc-700/50">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="flex items-center gap-2 text-sm font-black text-zinc-900 dark:text-zinc-50">
                                        <x-icon name="crown" class="size-4 text-amber-500" />
                                        {{ $plan->name }}
                                    </span>
                                    @if($plan->final_price <= 0)
                                        <x-badge variant="success" icon="gift">رایگان</x-badge>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    <x-icon name="calendar-clock" class="size-3.5" />
                                    {{ $plan->duration_label }}
                                    @if($plan->is_one_time)
                                        · یک‌بارمصرف
                                    @endif
                                </div>
                                @if($plan->packages->count())
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($plan->packages->take(3) as $pkg)
                                            <span class="rounded-lg bg-brand-50 px-2 py-0.5 text-[11px] font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
                                                {{ \Illuminate\Support\Str::limit($pkg->name, 16) }}
                                            </span>
                                        @endforeach
                                        @if($plan->packages->count() > 3)
                                            <span class="rounded-lg bg-zinc-100 px-2 py-0.5 text-[11px] font-bold text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                                +{{ fa_num($plan->packages->count() - 3) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                <div class="mt-auto flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-700/50">
                                    <span class="text-sm font-black tabular-nums text-zinc-900 dark:text-zinc-50">
                                        {{ $plan->final_price > 0 ? money($plan->final_price) : 'رایگان' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 transition-colors group-hover:text-brand-500 dark:text-brand-400">
                                        جزئیات طرح
                                        <x-icon name="arrow-left" class="size-3.5 transition-transform group-hover:-translate-x-1" />
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ Filters + grid ============ --}}
    <section class="mx-auto w-full max-w-7xl px-4 pb-10 sm:px-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-zinc-900 dark:text-zinc-50">پکیج‌های فروشگاه</h2>
                <p class="mt-0.5 text-xs text-zinc-500">
                    @if($this->records->total())
                        {{ fa_num($this->records->total()) }} پکیج
                    @endif
                </p>
            </div>
            <div class="w-full sm:w-56">
                <x-select wire:model.live="category" :options="$this->categories" placeholder="همه دسته‌بندی‌ها" />
            </div>
        </div>

        @if($this->records->count())
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($this->records as $package)
                    @php($minPrice = $package->activePricingPlans->count() ? $package->activePricingPlans->min('final_price') : $package->default_price)
                    @php($isCovered = in_array($package->id, $this->coveredPackageIds))
                    <a href="{{ route('shop.package', $package->slug) }}" wire:key="pkg-{{ $package->id }}" wire:navigate
                       class="card group flex flex-col overflow-hidden transition-all hover:shadow-card-lg">

                        {{-- thumbnail --}}
                        <div class="relative aspect-video overflow-hidden">
                            @if($package->thumbnail_url && $package->thumbnail_url !== asset('images/package-default.png'))
                                <img src="{{ $package->thumbnail_url }}" alt="{{ $package->name }}"
                                     class="size-full object-cover transition-transform duration-200 group-hover:scale-110" />
                            @else
                                <div class="shop-thumb flex size-full items-center justify-center transition-transform duration-200 group-hover:scale-110">
                                    <x-icon name="package" class="size-12 text-white/80" />
                                </div>
                            @endif

                            {{-- version badge --}}
                            @if($package->latestVersion)
                                <span class="absolute bottom-2 start-2 inline-flex items-center gap-1 rounded-lg bg-zinc-950/70 px-2 py-1 font-mono text-xs font-bold text-white backdrop-blur" dir="ltr">
                                    v{{ $package->latestVersion->version }}
                                </span>
                            @endif

                            {{-- free / subscription-covered badges --}}
                            @if($isCovered || $package->is_free || $minPrice <= 0)
                                <div class="absolute bottom-2 end-2 flex flex-col items-end gap-1">
                                    @if($package->is_free || $minPrice <= 0)
                                        <x-badge variant="success" icon="gift">رایگان</x-badge>
                                    @endif
                                    @if($isCovered)
                                        <x-badge variant="warning" icon="crown">رایگان با اشتراک</x-badge>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- body --}}
                        <div class="flex flex-1 flex-col gap-3 p-5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-base font-bold text-zinc-900 transition-colors group-hover:text-brand-600 dark:text-zinc-50">
                                    {{ $package->name }}
                                </h3>
                                @if($package->category)
                                    <x-badge variant="neutral">{{ \App\Livewire\Shop\Home::CATEGORIES[$package->category] ?? $package->category }}</x-badge>
                                @endif
                            </div>

                            <p class="flex-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">
                                {{ \Illuminate\Support\Str::limit($package->short_description, 110) }}
                            </p>

                            <div class="flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                                <span class="text-sm font-black tabular-nums text-zinc-900 dark:text-zinc-100">
                                    @if($isCovered)
                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                            <x-icon name="crown" class="size-3.5" />
                                            رایگان با اشتراک شما
                                        </span>
                                    @elseif($package->is_free || $minPrice <= 0)
                                        <span class="text-emerald-600">رایگان</span>
                                    @else
                                        {{ money($minPrice) }}
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-brand-600 dark:text-brand-400">
                                    مشاهده و خرید
                                    <x-icon name="arrow-left" class="size-3.5 transition-transform group-hover:-translate-x-1" />
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $this->records->links() }}
            </div>
        @else
            <div class="card">
                <x-empty icon="package" title="پکیجی یافت نشد" description="با جستجوی دیگری تلاش کنید یا دسته‌بندی را تغییر دهید." />
            </div>
        @endif
    </section>
</div>
