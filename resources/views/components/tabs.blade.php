{{--
    Tabs (Alpine, works with wire:navigate).
    <x-tabs :tabs="$tabs" :active="$tab" />   where $tabs = [['id' => 'info', 'label' => '...', 'icon' => '...']]
    Content: <x-slot:info> ... </x-slot:info>  (one named slot per tab id)
--}}
@props(['tabs' => [], 'active' => null])

<div x-data="tabs('{{ $active ?? ($tabs[0]['id'] ?? '') }}')" {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="flex gap-1 overflow-x-auto rounded-xl bg-zinc-200/60 p-1 dark:bg-zinc-800/60" role="tablist">
        @foreach($tabs as $tab)
            <button type="button" role="tab" :aria-selected="tab === '{{ $tab['id'] }}'"
                    @click="tab = '{{ $tab['id'] }}'"
                    :class="tab === '{{ $tab['id'] }}' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                    class="flex shrink-0 cursor-pointer items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition-all">
                @if(!empty($tab['icon']))
                    <x-icon :name="$tab['icon']" class="size-4" />
                @endif
                {{ $tab['label'] }}
                @if(!empty($tab['count']))
                    <span class="rounded-full bg-zinc-300/70 px-2 py-0.5 text-[11px] font-bold tabular-nums text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">{{ fa_num($tab['count']) }}</span>
                @endif
            </button>
        @endforeach
    </div>

    <div class="mt-5">
        @foreach($tabs as $tab)
            <div x-show="tab === '{{ $tab['id'] }}'" x-cloak role="tabpanel" class="animate-fade-in">
                {{ ${$tab['id']} ?? '' }}
            </div>
        @endforeach
    </div>
</div>
