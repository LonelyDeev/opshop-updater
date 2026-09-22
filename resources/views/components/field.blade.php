{{--
    Form field wrapper: label + control slot + hint + validation error.
    <x-field label="نام" for="name" required>
        <x-input id="name" wire:model="form.name" />
    </x-field>
--}}
@props(['label' => null, 'for' => null, 'required' => false, 'hint' => null, 'error' => null])

<div {{ $attributes->merge(['class' => 'w-full space-y-1.5']) }}>
    @if($label)
        <label for="{{ $for }}" class="flex items-center gap-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
            @if($required)
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if($hint)
        <p class="text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif

    {{-- نمایش خطای اعتبارسنجی — مقاوم در برابر نبود $errors (با ?? حتی اگر تعریف نشده باشد خطا نمی‌دهد)
         کلیدهای کاندید: error صریح، for ساده و for با پیشوند form. (Livewire اعتبارسنجی را با کلید کامل ثبت می‌کند) --}}
    @php($fieldErrorBag = $errors ?? null)
    @php($fieldErrorKey = $fieldErrorBag
        ? collect([$error, $for, $for ? 'form.' . $for : null])->filter()->first(fn ($c) => $fieldErrorBag->has($c))
        : null)
    @if($fieldErrorKey)
        <p class="flex items-center gap-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">
            <x-icon name="alert-circle" class="size-3.5 shrink-0" />
            {{ $fieldErrorBag->first($fieldErrorKey) }}
        </p>
    @endif
</div>
