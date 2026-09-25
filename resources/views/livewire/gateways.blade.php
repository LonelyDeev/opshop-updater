<div class="space-y-6">
    @php
        $schema = \App\Livewire\Gateways::schema();
        $activeCount = count(array_filter($gateways, fn ($gw) => $gw['is_active']));
    @endphp

    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-black text-zinc-900 dark:text-zinc-50">درگاه‌های پرداخت</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">مدیریت و پیکربندی درگاه‌های پرداخت آنلاین سایت.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-badge variant="success" icon="check-circle">{{ fa_num($activeCount) }} از {{ fa_num(count($gateways)) }} درگاه فعال</x-badge>
            <a href="{{ route('admin.settings.index') }}" wire:navigate>
                <x-btn variant="secondary" icon="settings">تنظیمات</x-btn>
            </a>
        </div>
    </div>

    @if(count($gateways))
        <form wire:submit="save" class="space-y-6">
            <div class="grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($gateways as $index => $gw)
                    @php
                        $meta = $schema[$gw['key']] ?? ['label' => $gw['name'], 'fields' => []];
                        $nameError = $errors->has("gateways.{$index}.name") ? 'input-error' : '';
                        $orderingError = $errors->has("gateways.{$index}.ordering") ? 'input-error' : '';
                    @endphp

                    <div wire:key="gw-{{ $gw['id'] }}" class="card flex flex-col">
                        {{-- gateway header --}}
                        <div class="flex items-center gap-3 border-b border-zinc-200/80 p-4 dark:border-zinc-800">
                            <div class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $gw['is_active'] ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500' }}">
                                <x-icon name="credit-card" class="size-5.5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold text-zinc-800 dark:text-zinc-100">{{ $meta['label'] ?? $gw['name'] }}</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <x-badge :variant="$gw['is_active'] ? 'success' : 'danger'">{{ $gw['is_active'] ? 'فعال' : 'غیرفعال' }}</x-badge>
                                    <span class="font-mono text-[11px] text-zinc-400" dir="ltr">{{ $gw['key'] }}</span>
                                </div>
                            </div>
                            <x-toggle wire:model.live="gateways.{{ $index }}.is_active" id="gw-active-{{ $gw['id'] }}" />
                        </div>

                        {{-- gateway body --}}
                        <div class="flex-1 space-y-4 p-4">
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-2">
                                    <x-field label="عنوان" for="gw-name-{{ $gw['id'] }}" required>
                                        <x-input id="gw-name-{{ $gw['id'] }}" wire:model="gateways.{{ $index }}.name" class="{{ $nameError }}" />
                                    </x-field>
                                </div>
                                <x-field label="ترتیب" for="gw-order-{{ $gw['id'] }}">
                                    <x-input id="gw-order-{{ $gw['id'] }}" wire:model="gateways.{{ $index }}.ordering" type="number" dir="ltr" class="{{ $orderingError }}" />
                                </x-field>
                            </div>

                            @foreach($meta['fields'] as $field)
                                @if(($field['type'] ?? 'text') === 'textarea')
                                    <x-field wire:key="gw-{{ $gw['id'] }}-f-{{ $field['name'] }}" :label="$field['label']" required>
                                        <x-textarea wire:model="gateways.{{ $index }}.configs.{{ $field['name'] }}" rows="3" dir="ltr" class="font-mono" />
                                    </x-field>
                                @else
                                    <x-field wire:key="gw-{{ $gw['id'] }}-f-{{ $field['name'] }}" :label="$field['label']" required>
                                        <x-input wire:model="gateways.{{ $index }}.configs.{{ $field['name'] }}" dir="ltr" class="font-mono" />
                                    </x-field>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- save bar --}}
            <div class="card sticky bottom-4 flex flex-wrap items-center justify-between gap-3 p-4 sm:p-5">
                <p class="flex items-center gap-2 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                    <x-icon name="info" class="size-4 shrink-0" />
                    برای فعال نمودن هر یک از درگاه‌ها، پس از انتخاب درگاه اطلاعات مربوط به آن را پر کنید.
                </p>
                <x-btn type="submit" icon="save" :loading="true">ذخیره تغییرات</x-btn>
            </div>
        </form>
    @else
        <div class="card">
            <x-empty icon="credit-card" title="درگاهی یافت نشد" description="درگاه‌های پشتیبانی‌شده در فایل پیکربندی سیستم تعریف نشده‌اند." />
        </div>
    @endif
</div>
