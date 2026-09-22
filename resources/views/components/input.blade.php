{{--
    Text input.
    <x-input wire:model="form.name" placeholder="نام پکیج" icon="package" />
    Add "input-error" class when validation fails.
--}}
@props(['icon' => null])

<div class="relative w-full">
    @if($icon)
        <x-icon :name="$icon" class="pointer-events-none absolute start-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
    @endif
    <input {{ $attributes->merge(['class' => trim('input '.($icon ? 'ps-10 pe-3.5' : ''))]) }} />
</div>
