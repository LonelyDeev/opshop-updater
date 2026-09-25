<div class="space-y-6">

    {{-- ============ Hero ============ --}}
    <section class="shop-hero relative overflow-hidden">
        {{-- decorative glows --}}
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="shop-hero-glow absolute -top-32 -start-32 size-96 rounded-full blur-3xl"></div>
            <div class="shop-hero-glow absolute -bottom-40 -end-24 size-96 rounded-full opacity-60 blur-3xl"></div>
        </div>

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
                </p>

                {{-- search --}}
                <div class="mt-6 max-w-xl">
                    <x-input wire:model.live.debounce.400ms="search" type="search" icon="search"
                             placeholder="جستجوی پکیج… (نام یا نامک)" />
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Subscription plans CTA ============ --}}
    <section class="mx-auto w-full max-w-7xl px-4 sm:px-6">
        <a href="{{ route('shop.plans') }}" wire:navigate
           class="shop-hero group relative flex items-center gap-4 overflow-hidden rounded-2xl p-5 shadow-card ring-1 ring-white/10 transition-all hover:shadow-card-lg sm:p-6">
            {{-- decorative glow --}}
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="shop-hero-glow absolute -top-24 -end-16 size-64 rounded-full opacity-40 blur-3xl"></div>
            </div>

            <span class="relative flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/25">
                <x-icon name="crown" class="size-6" />
            </span>

            <div class="relative min-w-0 flex-1">
                <p class="text-sm font-black text-white sm:text-base">طرح‌های اشتراک</p>
                <p class="mt-0.5 text-xs leading-5 text-teal-50/80">
                    دسترسی به چندین پکیج با یک خرید — طرح‌های ماهانه تا دائمی با لایسنس اختصاصی
                </p>
            </div>

            <span class="relative hidden items-center gap-1.5 rounded-xl bg-white/15 px-3.5 py-2 text-xs font-bold text-white ring-1 ring-white/20 transition-colors group-hover:bg-white/25 sm:inline-flex">
                مشاهده طرح‌ها
                <x-icon name="arrow-left" class="size-3.5 transition-transform group-hover:-translate-x-1" />
            </span>
        </a>
    </section>

    {{-- ============ Stats ============ --}}
    <section class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-center gap-4 px-4 sm:justify-start sm:px-6">
        <x-stat label="پکیج فعال" :value="fa_num($this->stats['packages'])" icon="package" variant="primary" />
        <x-stat label="نسخه فعال" :value="fa_num($this->stats['versions'])" icon="layers" variant="info" />
        <x-stat label="پرداخت امن" value="درگاه‌های بانکی" icon="shield-check" variant="warning" />
    </section>

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

                            {{-- free badge --}}
                            @if($package->is_free || $minPrice <= 0)
                                <span class="absolute bottom-2 end-2">
                                    <x-badge variant="success" icon="gift">رایگان</x-badge>
                                </span>
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
                                    @if($package->is_free || $minPrice <= 0)
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
