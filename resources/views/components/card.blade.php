{{--
    Card container.
    <x-card title="..." :subtitle="$sub" :action="$slotForHeader">
        ...
    </x-card>
--}}
@props(['title' => null, 'subtitle' => null, 'padding' => 'p-5 sm:p-6', 'headerPadding' => null])

<section {{ $attributes->merge(['class' => 'card']) }}>
    @if($title || isset($header))
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200/80 px-5 py-4 dark:border-zinc-800 sm:px-6">
            <div class="min-w-0">
                @if($title)
                    <h3 class="truncate text-sm font-bold text-zinc-800 dark:text-zinc-100 sm:text-base">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
                @endif
                {{ $header ?? '' }}
            </div>
            @isset($action)
                <div class="flex shrink-0 items-center gap-2">
                    {{ $action }}
                </div>
            @endisset
        </header>
    @endif

    <div {{ $padding ? 'class="'.trim($padding.' '.$attributes->get('body-class', '')).'"' : '' }}>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t border-zinc-200/80 px-5 py-4 dark:border-zinc-800 sm:px-6">
            {{ $footer }}
        </footer>
    @endisset
</section>
