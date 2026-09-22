<div class="space-y-6">
    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-black text-zinc-900 dark:text-zinc-50">تنظیمات</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">تنظیمات عمومی سایت، ایمیل و ابزارهای نگهداری سیستم.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.settings.gateways') }}" wire:navigate>
                <x-btn variant="secondary" icon="credit-card">مدیریت درگاه‌های پرداخت</x-btn>
            </a>
            <x-btn variant="secondary" icon="refresh-cw" wire:click="$set('confirmCache', true)">پاک‌سازی کش</x-btn>
            <x-btn variant="secondary" icon="zap" wire:click="$set('confirmOptimize', true)">بهینه‌سازی</x-btn>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- general settings --}}
        <x-card title="تنظیمات عمومی" subtitle="اطلاعات پایه سایت که در پنل و ایمیل‌های سیستمی استفاده می‌شود.">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field label="نام سایت" for="site_name" required>
                    <x-input id="site_name" wire:model="form.site_name" placeholder="پنل مدیریت آپدیت" icon="globe" class="{{ $errors->has('form.site_name') ? 'input-error' : '' }}" />
                </x-field>

                <x-field label="لوگو (URL)" for="site_logo" hint="آدرس تصویر لوگوی سایت (اختیاری).">
                    <x-input id="site_logo" wire:model="form.site_logo" placeholder="https://example.com/logo.png" icon="image" dir="ltr" class="{{ $errors->has('form.site_logo') ? 'input-error' : '' }}" />
                </x-field>

                <div class="sm:col-span-2">
                    <x-field label="توضیحات کوتاه سایت" for="site_description">
                        <x-textarea id="site_description" wire:model="form.site_description" rows="3" placeholder="توضیح کوتاهی درباره سرویس…" class="{{ $errors->has('form.site_description') ? 'input-error' : '' }}" />
                    </x-field>
                </div>

                <x-field label="رنگ اصلی تم" for="theme_color" hint="کد رنگ برند سایت.">
                    <div class="flex items-center gap-3">
                        <input type="color" id="theme_color" wire:model="form.theme_color"
                               class="h-10 w-16 shrink-0 cursor-pointer rounded-xl border border-zinc-300 bg-white p-1 dark:border-zinc-700 dark:bg-zinc-800" />
                        <input type="text" wire:model="form.theme_color" dir="ltr" placeholder="#10b981"
                               class="input font-mono {{ $errors->has('form.theme_color') ? 'input-error' : '' }}" />
                    </div>
                </x-field>
            </div>
        </x-card>

        {{-- email settings --}}
        <x-card title="تنظیمات ایمیل" subtitle="تنظیمات سرور SMTP برای ارسال ایمیل‌های سیستمی.">
            <div class="space-y-5">
                <div class="rounded-xl bg-zinc-50 p-4 ring-1 ring-zinc-200/60 dark:bg-zinc-800/40 dark:ring-zinc-700/60">
                    <x-toggle wire:model="form.mail_enabled" label="فعال‌سازی ارسال ایمیل" id="mail_enabled" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field label="آدرس ایمیل فرستنده" for="mail_from_address">
                        <x-input id="mail_from_address" wire:model="form.mail_from_address" type="email" placeholder="no-reply@example.com" icon="mail" dir="ltr" class="{{ $errors->has('form.mail_from_address') ? 'input-error' : '' }}" />
                    </x-field>

                    <x-field label="نام فرستنده" for="mail_from_name">
                        <x-input id="mail_from_name" wire:model="form.mail_from_name" placeholder="پنل مدیریت آپدیت" icon="user" class="{{ $errors->has('form.mail_from_name') ? 'input-error' : '' }}" />
                    </x-field>

                    <x-field label="SMTP Host" for="mail_host">
                        <x-input id="mail_host" wire:model="form.mail_host" placeholder="smtp.example.com" icon="server" dir="ltr" class="{{ $errors->has('form.mail_host') ? 'input-error' : '' }}" />
                    </x-field>

                    <x-field label="SMTP Port" for="mail_port">
                        <x-input id="mail_port" wire:model="form.mail_port" type="number" placeholder="587" icon="lock" dir="ltr" class="{{ $errors->has('form.mail_port') ? 'input-error' : '' }}" />
                    </x-field>

                    <x-field label="SMTP Username" for="mail_username">
                        <x-input id="mail_username" wire:model="form.mail_username" placeholder="username" icon="user" dir="ltr" class="{{ $errors->has('form.mail_username') ? 'input-error' : '' }}" />
                    </x-field>

                    <x-field label="SMTP Password" for="mail_password">
                        <x-input id="mail_password" wire:model="form.mail_password" type="password" placeholder="••••••••" icon="key" dir="ltr" class="{{ $errors->has('form.mail_password') ? 'input-error' : '' }}" />
                    </x-field>
                </div>
            </div>
        </x-card>

        {{-- save --}}
        <div class="flex items-center justify-end gap-2">
            <x-btn type="submit" icon="save" :loading="true">ذخیره تنظیمات</x-btn>
        </div>
    </form>

    {{-- clear cache confirm --}}
    <x-modal wire:model="confirmCache" title="پاک‌سازی کش سیستم" size="sm">
        @if($confirmCache)
            <div class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl bg-amber-50 p-4 text-amber-700 ring-1 ring-amber-600/10 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-400/20">
                    <x-icon name="refresh-cw" class="size-6 shrink-0" />
                    <p class="text-sm leading-6">کش برنامه، تنظیمات و ویوها پاک می‌شود. این عملیات ایمن است؛ ادامه می‌دهید؟</p>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-btn variant="secondary" wire:click="$set('confirmCache', false)">انصراف</x-btn>
                    <x-btn icon="refresh-cw" wire:click="clearCache" :loading="true">پاک‌سازی کش</x-btn>
                </div>
            </div>
        @endif
    </x-modal>

    {{-- optimize confirm --}}
    <x-modal wire:model="confirmOptimize" title="بهینه‌سازی سیستم" size="sm">
        @if($confirmOptimize)
            <div class="space-y-4">
                <div class="flex items-center gap-3 rounded-xl bg-brand-50 p-4 text-brand-700 ring-1 ring-brand-600/10 dark:bg-brand-500/10 dark:text-brand-400 dark:ring-brand-400/20">
                    <x-icon name="zap" class="size-6 shrink-0" />
                    <p class="text-sm leading-6">تنظیمات، مسیرها و ویوها کش می‌شوند تا سرعت برنامه بالا برود؛ ادامه می‌دهید؟</p>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <x-btn variant="secondary" wire:click="$set('confirmOptimize', false)">انصراف</x-btn>
                    <x-btn icon="zap" wire:click="optimize" :loading="true">بهینه‌سازی</x-btn>
                </div>
            </div>
        @endif
    </x-modal>
</div>
