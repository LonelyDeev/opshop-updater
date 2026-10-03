<div class="space-y-6">
    {{-- header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="flex items-center gap-2 text-lg font-black text-zinc-900 dark:text-zinc-50">
                <x-icon name="message-square" class="size-5 text-brand-600 dark:text-brand-400" />
                مدیریت قالب‌های پیامک
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                قالب‌های پیامکی که هنگام خرید، فعال‌سازی، تمدید و انقضای اشتراک به‌صورت خودکار ارسال می‌شوند؛ متغیرها را با <code dir="ltr" class="rounded bg-zinc-100 px-1 font-mono text-xs dark:bg-zinc-800">{متغیر}</code> داخل متن قرار دهید.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <x-btn variant="secondary" icon="settings" href="{{ route('admin.settings.index') }}#sms">تنظیمات پیامک</x-btn>
            <x-btn icon="plus" wire:click="openCreate" :loading="true">افزودن قالب</x-btn>
        </div>
    </div>

    {{-- status banner --}}
    @if(!$smsEnabled)
        <div class="card flex items-start gap-3 border-amber-300/60 bg-amber-50 p-4 dark:border-amber-400/20 dark:bg-amber-500/10">
            <x-icon name="info" class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
            <div class="text-sm text-amber-800 dark:text-amber-300">
                <b>سیستم پیامک غیرفعال است.</b>
                برای فعال‌سازی، از <a href="{{ route('admin.settings.index') }}#sms" class="font-bold underline underline-offset-2">تنظیمات ← پیامک</a> درایور و اطلاعات پنل پیامکی خود را وارد کنید.
            </div>
        </div>
    @elseif(empty($driverName))
    @else
        <div class="card flex flex-wrap items-center gap-x-6 gap-y-2 p-4">
            <div class="flex items-center gap-2 text-sm">
                <span class="inline-flex size-2 rounded-full bg-emerald-500"></span>
                <span class="font-bold text-zinc-800 dark:text-zinc-100">درایور فعال:</span>
                <x-badge>{{ \App\Models\SmsTemplate::DRIVERS[$driverName] ?? $driverName }}</x-badge>
            </div>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                وضعیت ارسال بدون پترن:
                @if(in_array($driverName, ['kavenegar', 'melipayamak']))
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">پشتیبانی می‌شود (متن خام)</span>
                @else
                    <span class="font-bold text-amber-600 dark:text-amber-400">فقط پترنی — کد پترن قالب‌ها را پر کنید</span>
                @endif
            </div>
            <a href="{{ route('admin.sms.logs') }}" class="ms-auto text-sm font-bold text-brand-600 hover:underline dark:text-brand-400">لاگ ارسال‌ها ←</a>
        </div>
    @endif

    {{-- filters --}}
    <div class="card flex flex-col gap-3 p-4 lg:flex-row lg:items-center">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute start-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="جستجو در قالب‌ها…" class="input ps-10" />
        </div>
        <select wire:model.live="status" class="input lg:w-40">
            <option value="">همه وضعیت‌ها</option>
            <option value="active">فعال</option>
            <option value="inactive">غیرفعال</option>
        </select>
        <div class="relative lg:w-48">
            <x-icon name="arrow-up-down" class="pointer-events-none absolute start-3.5 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <select wire:model.live="sort" class="input ps-10" aria-label="ترتیب نمایش">
                <option value="newest">جدیدترین</option>
                <option value="oldest">قدیمی‌ترین</option>
                <option value="title">بر اساس عنوان</option>
                <option value="key">بر اساس کلید</option>
            </select>
        </div>
    </div>

    {{-- cards grid --}}
    @if($this->records->isEmpty())
        <x-empty icon="message-square" title="قالبی یافت نشد" :description="$search ? 'عبارت دیگری را جستجو کنید.' : 'با دکمه «افزودن قالب» اولین قالب را بسازید.'" />
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
            @foreach($this->records as $template)
                <div class="card flex flex-col gap-3 p-4 {{ $template->is_active ? '' : 'opacity-60' }}">
                    {{-- header: toggle + title --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-bold text-zinc-900 dark:text-zinc-50">{{ $template->title }}</h3>
                            <code dir="ltr" class="mt-0.5 block truncate font-mono text-[11px] text-zinc-400 dark:text-zinc-500">{{ $template->key }}</code>
                        </div>
                        <button type="button" wire:click="toggle({{ $template->id }})"
                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-200 {{ $template->is_active ? 'bg-brand-600' : 'bg-zinc-300 dark:bg-zinc-700' }}"
                                title="{{ $template->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}"
                                aria-label="{{ $template->is_active ? 'غیرفعال کردن قالب' : 'فعال کردن قالب' }}">
                            <span class="absolute start-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200 {{ $template->is_active ? '-translate-x-5' : '' }}"></span>
                        </button>
                    </div>

                    {{-- body preview with variable chips --}}
                    <div class="rounded-xl bg-zinc-100 p-3 text-xs leading-6 text-zinc-600 dark:bg-zinc-800/70 dark:text-zinc-300">
                        @php
                            $parts = preg_split('/({[a-z0-9_]+})/', $template->body, -1, PREG_SPLIT_DELIM_CAPTURE);
                        @endphp
                        @foreach($parts ?: [] as $part)
                            @if(preg_match('/^{[a-z0-9_]+}$/', $part))
                                <span dir="ltr" class="mx-0.5 inline-block rounded-md border border-amber-400/50 bg-amber-50 px-1.5 py-0.5 font-mono text-[10px] font-bold text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-400">{{ $part }}</span>
                            @else
                                {{ $part }}
                            @endif
                        @endforeach
                    </div>

                    {{-- pattern codes for the active driver --}}
                    @php($activePattern = $template->patternCode($driverName))
                    <div class="flex items-center gap-2 text-[11px]">
                        @if($activePattern)
                            <x-badge variant="success" icon="zap">پترن: <span dir="ltr" class="font-mono">{{ $activePattern }}</span></x-badge>
                        @else
                            <x-badge variant="neutral">بدون پترن</x-badge>
                        @endif
                    </div>

                    {{-- footer --}}
                    <div class="mt-auto flex items-center justify-between border-t border-zinc-100 pt-3 dark:border-zinc-800">
                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500">
                            آخرین بروزرسانی {{ verta_date($template->updated_at, 'Y/m/d H:i') }}
                        </span>
                        <div class="flex items-center gap-1">
                            <x-btn variant="ghost" size="sm" icon="send" wire:click="openTest({{ $template->id }})" aria-label="ارسال آزمایشی" />
                            <x-btn variant="ghost" size="sm" icon="pencil" wire:click="openEdit({{ $template->id }})" aria-label="ویرایش" />
                            @unless(in_array($template->key, \App\Models\SmsTemplate::SYSTEM_KEYS))
                                <x-btn variant="ghost" size="sm" icon="trash-2" class="text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10" wire:click="askDelete({{ $template->id }})" aria-label="حذف" />
                            @endunless
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-center gap-2">
            {{ $this->records->links() }}
        </div>
    @endif

    {{-- help box --}}
    <div class="card space-y-3 border-brand-300/40 bg-brand-50/50 p-5 dark:border-brand-400/20 dark:bg-brand-500/5">
        <h3 class="flex items-center gap-2 text-sm font-black text-zinc-800 dark:text-zinc-100">
            <x-icon name="help-circle" class="size-4.5 text-brand-600 dark:text-brand-400" />
            راهنما: قابلیت‌های مرتبط با قالب‌های پیامک چیست؟
        </h3>
        <ul class="list-inside list-disc space-y-1.5 text-xs leading-6 text-zinc-600 dark:text-zinc-400">
            <li>هر قالب یک <b>کلید سیستمی</b> دارد که در کد برنامه به آن اشاره می‌کند (مثلاً <code dir="ltr" class="font-mono">purchase_paid</code>) و متن آن کاملاً قابل ویرایش است.</li>
            <li>متغیرها با الگوی <code dir="ltr" class="font-mono">{customer_name}</code> داخل متن قرار می‌گیرند و هنگام ارسال با مقادیر واقعی (نام مشتری، مبلغ، تاریخ و…) جایگزین می‌شوند.</li>
            <li><b>کد پترن (Pattern Code):</b> اگر پنل پیامکی شما خط خدماتی است، متن قالب را باید در پنل سرویس (به‌عنوان پترن/قالب) ثبت کنید و کد آن را در همین صفحه برای درایور مربوطه وارد کنید. آنگاه ارسال از مسیر پترنی (Verify Lookup کاوه‌نگار / bodyId ملی‌پیامک / pattern_code آی‌پی‌پنل / attributes فراز / TemplateId ایده پردازان) انجام می‌شود.</li>
            <li>اگر کد پترن را خالی بگذارید و درایورتان کاوه‌نگار یا ملی‌پیامک باشد، متن رندرشده به‌صورت خام ارسال می‌شود (مناسب خطوط اشتراکی/تبلیغاتی).</li>
            <li>اشتراک‌های نزدیک به انقضای (تعداد روز = تنظیمات پیامک) و تازه منقضی‌شده، هر روز ساعت ۹ صبح با کران‌جاب <code dir="ltr" class="font-mono">sms:check-subscriptions</code> پیامک می‌گیرند؛ روی هاست یک کران‌جاب روزانه برای <code dir="ltr" class="font-mono">php artisan schedule:run</code> تنظیم کنید.</li>
        </ul>
    </div>

    {{-- create / edit modal --}}
    <x-modal wire:model="showModal" :title="$editingId ? 'ویرایش قالب پیامک' : 'قالب پیامک جدید'" size="lg">
        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="کلید قالب" hint="فقط حروف کوچک لاتین، عدد و _ — در کد برنامه استفاده می‌شود.">
                    <input type="text" wire:model="form.key" placeholder="purchase_paid" {{ $editingId !== null ? 'disabled' : '' }} dir="ltr" class="input font-mono ps-3.5 {{ $errors->has('form.key') ? 'input-error' : '' }}" />
                </x-field>
                <x-field label="عنوان (فارسی)">
                    <x-input wire:model="form.title" placeholder="پرداخت موفق خرید پکیج" :class="$errors->has('form.title') ? 'input-error' : ''" />
                </x-field>
            </div>

            <x-field label="متن پیامک" hint="متغیرها را با {نام_متغیر} داخل متن بگذارید (حداکثر ۲۰۰۰ کاراکتر).">
                <textarea wire:model="form.body" rows="6" placeholder="{customer_name} عزیز&#10;پرداخت شما …" class="input {{ $errors->has('form.body') ? 'input-error' : '' }}"></textarea>
            </x-field>

            {{-- variables --}}
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">متغیرهای قالب</span>
                    <x-btn variant="soft" size="sm" icon="plus" wire:click="addVariable">متغیر جدید</x-btn>
                </div>
                <div class="space-y-2">
                    @foreach($formVariables as $i => $var)
                        <div class="flex items-center gap-2" wire:key="var-{{ $i }}">
                            <input type="text" wire:model="formVariables.{{ $i }}" placeholder="customer_name" dir="ltr" class="input font-mono {{ $errors->has("formVariables.{$i}") ? 'input-error' : '' }}" />
                            <x-btn variant="ghost" icon="trash-2" size="sm" wire:click="removeVariable({{ $i }})" aria-label="حذف متغیر" />
                        </div>
                    @endforeach
                </div>
                <p class="mt-1.5 text-xs text-zinc-400">به‌ترتیبِ همین لیست، مقادیر به tokenهای کاوه‌نگار (token، token2، …) و attributes فراز منتقل می‌شود؛ نام متغیرها را با placeholderهای پترن ثبت‌شده در پنل خودتان هماهنگ کنید.</p>
            </div>

            {{-- pattern codes --}}
            <div>
                <span class="mb-2 block text-sm font-bold text-zinc-700 dark:text-zinc-300">کد پترن هر درایور (اختیاری)</span>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach($drivers as $driverKey => $driverLabel)
                        <x-field :label="$driverLabel" :hint="$driverKey === $driverName ? 'درایور فعال' : null">
                            <input type="text" wire:model="formPatterns.{{ $driverKey }}" placeholder="pattern-code…" dir="ltr" class="input font-mono {{ $driverKey === $driverName ? 'ring-brand-500/40' : '' }}" />
                        </x-field>
                    @endforeach
                </div>
            </div>

            <x-toggle wire:model="form.is_active" label="قالب فعال است" />
        </div>

        <x-slot:footer>
            <div class="flex justify-end gap-2">
                <x-btn variant="secondary" wire:click="$set('showModal', false)">انصراف</x-btn>
                <x-btn icon="check" wire:click="save" :loading="true">ذخیره قالب</x-btn>
            </div>
        </x-slot:footer>
    </x-modal>

    {{-- test send modal --}}
    <x-modal wire:model="testModal" title="ارسال آزمایشی" subtitle="پیامک با مقادیر نمونه برای متغیرها ارسال می‌شود." size="sm">
        <div class="space-y-4">
            <x-field label="شماره موبایل">
                <input type="text" wire:model="testMobile" placeholder="09123456789" dir="ltr" class="input font-mono {{ $errors->has('testMobile') ? 'input-error' : '' }}" />
            </x-field>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">نتیجه ارسال (موفق/ناموفق + پاسخ سرویس) در «لاگ پیامک‌ها» ثبت می‌شود.</p>
        </div>
        <x-slot:footer>
            <div class="flex justify-end gap-2">
                <x-btn variant="secondary" wire:click="$set('testModal', false)">انصراف</x-btn>
                <x-btn icon="send" wire:click="sendTest" :loading="true">ارسال آزمایشی</x-btn>
            </div>
        </x-slot:footer>
    </x-modal>

    {{-- delete confirm --}}
    <x-modal wire:model="deleteId" title="حذف قالب" size="sm">
        <p class="text-sm text-zinc-600 dark:text-zinc-300">از حذف این قالب پیامک مطمئن هستید؟ قالب‌های سیستمی قابل حذف نیستند.</p>
        <x-slot:footer>
            <div class="flex justify-end gap-2">
                <x-btn variant="secondary" wire:click="$set('deleteId', null)">انصراف</x-btn>
                <x-btn variant="danger" icon="trash-2" wire:click="delete" :loading="true">حذف کن</x-btn>
            </div>
        </x-slot:footer>
    </x-modal>
</div>
