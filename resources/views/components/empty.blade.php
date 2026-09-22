{{--
    Empty state for tables / lists.
    <x-empty icon="package" title="پکیجی یافت نشد" description="..."> action slot </x-empty>
--}}
@props(['icon' => 'search', 'title' => 'موردی یافت نشد', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 px-6 py-14 text-center']) }}>
    <div class="flex size-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
        <x-icon :name="$icon" class="size-7" />
    </div>
    <div class="space-y-1">
        <p class="text-sm font-bold text-zinc-700 dark:text-zinc-200">{{ $title }}</p>
        @if($description)
            <p class="max-w-sm text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="mt-1">{{ $slot }}</div>
    @endif
</div>
