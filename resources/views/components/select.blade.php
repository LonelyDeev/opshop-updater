{{--
    Select.
    <x-select wire:model="form.status" :options="['draft' => 'پیش‌نویس', ...]" />
--}}
@props(['options' => [], 'placeholder' => null])

<select {{ $attributes->merge(['class' => 'input']) }}>
    @if($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
