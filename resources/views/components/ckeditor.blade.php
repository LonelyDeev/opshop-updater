{{--
    Rich-text (CKEditor 4) field bound to a Livewire property.

    <x-ckeditor model="form.description" label="توضیحات" :value="$form['description'] ?? ''" error="form.description" />

    Structure:
      1. marker div — morphable (OUTSIDE wire:ignore), carries the fresh
         server value in data-editor-value; richEditor.pull() reads it
         after livewire:morph (openEdit / openCreate).
      2. editor shell — wire:ignore protects the CKEditor DOM from morphing;
         the hidden textarea is replaced by CKEditor on first visibility.
--}}
@props(['model' => null, 'label' => null, 'error' => null, 'required' => false, 'value' => ''])

<div wire:key="ck-marker-{{ str_replace('.', '-', $model) }}"
     data-editor-model="{{ $model }}"
     data-editor-value="{{ $value }}"
     hidden aria-hidden="true"></div>

<div wire:key="ck-editor-{{ str_replace('.', '-', $model) }}" wire:ignore x-data="richEditor('{{ $model }}')" class="w-full space-y-1.5">
    @if($label)
        <label class="flex items-center gap-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">
            {{ $label }}
            @if($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    {{-- CKEditor replaces the hidden textarea; wire:model keeps the value entangled --}}
    <textarea x-ref="target" wire:model="{{ $model }}" class="hidden" rows="8"></textarea>

    @if($error)
        @error($error)
            <p class="flex items-center gap-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">
                <x-icon name="alert-circle" class="size-3.5 shrink-0" />
                {{ $message }}
            </p>
        @enderror
    @endif
</div>
