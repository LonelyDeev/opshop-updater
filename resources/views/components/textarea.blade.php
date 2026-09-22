{{--
    Textarea.
    <x-textarea wire:model="form.description" rows="5" />
--}}
@props(['rows' => 4])

<textarea {{ $attributes->merge(['class' => 'input', 'rows' => $rows]) }}></textarea>
