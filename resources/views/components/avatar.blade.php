{{--
    Avatar with initials (RTL-safe).
    <x-avatar :name="$user->name" :src="$user->avatar" size="md" />
--}}
@props(['name' => '?', 'src' => null, 'size' => 'md'])

@php
    $sizes = [
        'xs' => 'size-7 text-[10px]',
        'sm' => 'size-9 text-xs',
        'md' => 'size-10 text-sm',
        'lg' => 'size-12 text-base',
    ];

    $palette = ['bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300',
                'bg-teal-100 text-teal-700 dark:bg-teal-500/20 dark:text-teal-300',
                'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300',
                'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300'];

    $initial = mb_substr(trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $name ?? '')) ?: '؟', 0, 1);
    $color = $palette[abs(crc32($name ?? '')) % count($palette)];
@endphp

<div {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center overflow-hidden rounded-full font-bold '.$color.' '.($sizes[$size] ?? $sizes['md'])]) }}>
    @if($src)
        <img src="{{ $src }}" alt="{{ $name }}" class="size-full object-cover" loading="lazy" />
    @else
        <span aria-hidden="true">{{ $initial }}</span>
        <span class="sr-only">{{ $name }}</span>
    @endif
</div>
