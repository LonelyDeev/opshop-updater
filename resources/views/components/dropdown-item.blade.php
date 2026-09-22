{{--
    Dropdown menu item.
    <x-dropdown-item icon="pencil" wire:click="edit({{ $id }})">ویرایش</x-dropdown-item>
--}}
@props(['icon' => null, 'variant' => 'default'])

@php
    $variants = [
        'default' => 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-700/60 dark:hover:text-zinc-100',
        'danger'  => 'text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10',
    ];
@endphp

<button type="button" {{ $attributes->merge(['class' => 'flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-start text-sm font-medium transition-colors '.$variants[$variant]]) }}>
    @if($icon)
        <x-icon :name="$icon" class="size-4 shrink-0 opacity-70" />
    @endif
    <span class="truncate">{{ $slot }}</span>
</button>
