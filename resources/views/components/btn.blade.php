{{--
    Button – variants: primary | secondary | soft | ghost | danger
    sizes:      sm | md | lg
    <x-btn variant="primary" icon="plus" wire:click="save">ذخیره</x-btn>
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'loading' => false,
])

@php
    $base = 'inline-flex select-none items-center justify-center gap-2 rounded-xl font-semibold transition-all duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 disabled:pointer-events-none disabled:opacity-50 active:scale-[0.98]';

    $variants = [
        'primary'   => 'bg-brand-600 text-white shadow-xs hover:bg-brand-500 hover:shadow-sm dark:bg-brand-600 dark:hover:bg-brand-500',
        'secondary' => 'bg-white text-zinc-700 ring-1 ring-zinc-300 shadow-xs hover:bg-zinc-50 hover:ring-zinc-400 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700 dark:hover:bg-zinc-700',
        'soft'      => 'bg-brand-50 text-brand-700 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/20',
        'ghost'     => 'text-zinc-600 hover:bg-zinc-200/70 dark:text-zinc-300 dark:hover:bg-zinc-700/60',
        'danger'    => 'bg-rose-600 text-white shadow-xs hover:bg-rose-500 dark:hover:bg-rose-500',
        'danger-soft' => 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20',
    ];

    $sizes = [
        'sm' => 'h-8 px-3 text-xs',
        'md' => 'h-10 px-4 text-sm',
        'lg' => 'h-12 px-6 text-base',
    ];

    $classes = trim("{$base} {$variants[$variant]} {$sizes[$size]}");

    /* target for wire:loading — the button's own wire:click / wire:submit action
       (submit buttons inside a form get no target → reacts to the form's request) */
    $loadingTarget = $attributes->whereStartsWith(['wire:click', 'wire:submit'])->first();
    $targetAttr = $loadingTarget ? 'wire:target="' . $loadingTarget . '"' : '';
    $spinnerSize = $size === 'sm' ? 'size-3.5' : 'size-4';
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }} @if($loading) wire:loading.attr="disabled" {{ $targetAttr }} @endif>
    @if($loading)
        {{-- spinner: hidden by default, appears only while the request is in-flight --}}
        @if($loadingTarget)
            <x-icon name="loader" class="{{ $spinnerSize }} animate-spin" wire:loading wire:target="{{ $loadingTarget }}" />
        @else
            <x-icon name="loader" class="{{ $spinnerSize }} animate-spin" wire:loading />
        @endif
        @if($icon)
            {{-- normal icon: visible by default, hidden while loading --}}
            @if($loadingTarget)
                <x-icon :name="$icon" class="{{ $size === 'sm' ? 'size-3.5' : 'size-4.5' }}" wire:loading.remove wire:target="{{ $loadingTarget }}" />
            @else
                <x-icon :name="$icon" class="{{ $size === 'sm' ? 'size-3.5' : 'size-4.5' }}" wire:loading.remove />
            @endif
        @endif
    @elseif($icon)
        <x-icon :name="$icon" class="{{ $size === 'sm' ? 'size-3.5' : 'size-4.5' }}" />
    @endif
    {{ $slot }}
</button>
