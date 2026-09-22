{{--
    Alpine dropdown menu.
    <x-dropdown align="end" width="w-56">
        <x-slot:trigger> ... button ... </x-slot:trigger>
        <x-slot:menu> ... items ... </x-slot:menu>
    </x-dropdown>
--}}
@props(['align' => 'end', 'width' => 'w-56'])

@php
    $alignment = [
        'start' => 'start-0 origin-top-start',
        'end'   => 'end-0 origin-top-end',
        'center' => 'left-1/2 -translate-x-1/2 origin-top',
    ];
@endphp

<div x-data="{ open: false }" @click.outside="open = false" @close-dropdown.window="open = false" @keydown.escape.window="open = false" {{ $attributes }} class="relative">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute top-full z-40 mt-2 {{ $alignment[$align] ?? $alignment['end'] }} {{ $width }} rounded-xl bg-white p-1.5 shadow-pop ring-1 ring-zinc-900/5 dark:bg-zinc-800 dark:ring-white/10"
    >
        {{ $menu }}
    </div>
</div>
