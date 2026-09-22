{{--
    Dashboard stat card.
    <x-stat label="مشتریان" :value="fa_num(120)" icon="users" variant="primary" hint="۱۰ فعال" href="..." />
--}}
@props(['label' => null, 'value' => null, 'icon' => null, 'variant' => 'primary', 'trend' => null, 'trendUp' => true, 'hint' => null, 'href' => null])

@php
    $variants = [
        'primary' => 'bg-brand-500/10 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
        'info'    => 'bg-teal-500/10 text-teal-600 dark:bg-teal-500/15 dark:text-teal-400',
        'warning' => 'bg-amber-500/10 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400',
        'danger'  => 'bg-rose-500/10 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400',
        'violet'  => 'bg-violet-500/10 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400',
        'neutral' => 'bg-zinc-500/10 text-zinc-600 dark:bg-zinc-500/15 dark:text-zinc-400',
    ];

    $base = 'card group flex items-center gap-4 p-4 transition-all hover:shadow-card-lg';
    $iconBox = $variants[$variant] ?? $variants['neutral'];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>
@else
    <div {{ $attributes->merge(['class' => $base]) }}>
@endif
    @if($icon)
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $iconBox }}">
            <x-icon :name="$icon" class="size-5.5" />
        </div>
    @endif
    <div class="min-w-0 flex-1">
        <p class="truncate text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
        <p class="mt-1 text-xl font-black tabular-nums text-zinc-900 dark:text-zinc-50">{{ $value }}</p>
        @if($hint)
            <p class="mt-0.5 truncate text-[11px] text-zinc-400 dark:text-zinc-500">{{ $hint }}</p>
        @endif
    </div>
    @if($trend)
        <span class="flex shrink-0 items-center gap-1 rounded-full px-2 py-1 text-xs font-bold {{ $trendUp ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400' }}">
            <x-icon :name="$trendUp ? 'trending-up' : 'trending-down'" class="size-3.5" />
            {{ $trend }}
        </span>
    @endif
@if($href)
    </a>
@else
    </div>
@endif
