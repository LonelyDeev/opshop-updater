@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView({ behavior: 'smooth' })
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="صفحه‌بندی" class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                نمایش
                <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ fa_num($paginator->firstItem() ?? 0) }}</span>
                تا
                <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ fa_num($paginator->lastItem() ?? 0) }}</span>
                از
                <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ fa_num($paginator->total()) }}</span>
                نتیجه
            </p>

            <div class="flex flex-wrap items-center gap-1.5">
                {{-- previous --}}
                @if ($paginator->onFirstPage())
                    <span class="inline-flex size-9 cursor-default items-center justify-center rounded-xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-600" aria-disabled="true">
                        <x-icon name="chevron-right" class="size-4" />
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                            class="inline-flex size-9 items-center justify-center rounded-xl text-zinc-500 transition-colors hover:bg-zinc-200/70 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700/60 dark:hover:text-zinc-100"
                            aria-label="صفحه قبل">
                        <x-icon name="chevron-right" class="size-4" />
                    </button>
                @endif

                {{-- page numbers --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="inline-flex size-9 items-center justify-center text-zinc-400 dark:text-zinc-500" aria-disabled="true">…</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex size-9 items-center justify-center rounded-xl bg-brand-600 font-bold text-white shadow-sm" aria-current="page">{{ fa_num($page) }}</span>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                                        class="inline-flex size-9 items-center justify-center rounded-xl text-zinc-600 transition-colors hover:bg-zinc-200/70 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-700/60 dark:hover:text-zinc-100"
                                        aria-label="صفحه {{ $page }}">
                                    {{ fa_num($page) }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- next --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                            class="inline-flex size-9 items-center justify-center rounded-xl text-zinc-500 transition-colors hover:bg-zinc-200/70 hover:text-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700/60 dark:hover:text-zinc-100"
                            aria-label="صفحه بعد">
                        <x-icon name="chevron-left" class="size-4" />
                    </button>
                @else
                    <span class="inline-flex size-9 cursor-default items-center justify-center rounded-xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-600" aria-disabled="true">
                        <x-icon name="chevron-left" class="size-4" />
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
