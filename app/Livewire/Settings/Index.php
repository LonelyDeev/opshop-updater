<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\WithToasts;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('تنظیمات')]
class Index extends Component
{
    use WithToasts;

    /** @var array<string, mixed> کلیدها مطابق SettingController قدیمی */
    public array $form = [
        'site_name' => 'پنل مدیریت آپدیت',
        'site_description' => '',
        'site_logo' => '',
        'theme_color' => '#10b981',
        'mail_enabled' => false,
        'mail_from_address' => '',
        'mail_from_name' => '',
        'mail_host' => '',
        'mail_port' => '587',
        'mail_username' => '',
        'mail_password' => '',
    ];

    public bool $confirmCache = false;
    public bool $confirmOptimize = false;

    /** گروه ذخیره هر کلید (مطابق ساختار جدول settings) */
    protected const GROUPS = [
        'site_name' => 'general',
        'site_description' => 'general',
        'site_logo' => 'general',
        'theme_color' => 'general',
        'mail_enabled' => 'email',
        'mail_from_address' => 'email',
        'mail_from_name' => 'email',
        'mail_host' => 'email',
        'mail_port' => 'email',
        'mail_username' => 'email',
        'mail_password' => 'email',
    ];

    public function mount(): void
    {
        $stored = Setting::query()
            ->whereIn('group', ['general', 'email'])
            ->pluck('value', 'key');

        foreach (array_keys($this->form) as $key) {
            if ($stored->has($key)) {
                $this->form[$key] = $key === 'mail_enabled'
                    ? (bool) filter_var($stored->get($key), FILTER_VALIDATE_BOOLEAN)
                    : (string) $stored->get($key);
            }
        }
    }

    /* ---------------------------------------------------------------- */
    /*  Actions (پورت‌شده از SettingController)                          */
    /* ---------------------------------------------------------------- */

    /** ذخیره تنظیمات عمومی و ایمیل (پورت SettingController@update) */
    public function save(): void
    {
        $this->validate();

        foreach (self::GROUPS as $key => $group) {
            $value = $this->form[$key];

            if ($key === 'mail_enabled') {
                $value = $value ? '1' : '0';
            }

            Setting::set($key, (string) $value, 'string', $group);
        }

        // پاک کردن کش تنظیمات (مطابق کنترلر قدیمی)
        Cache::forget('settings');

        $this->toast('تنظیمات با موفقیت ذخیره شد.');
    }

    /** پاک‌سازی کش سیستم (پورت SettingController@clearCache) */
    public function clearCache(): void
    {
        $this->confirmCache = false;

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            $this->toast('کش سیستم با موفقیت پاک شد.');
        } catch (\Throwable $e) {
            $this->toast('خطا در پاک‌سازی کش: '.$e->getMessage(), 'error');
        }
    }

    /** بهینه‌سازی سیستم (پورت SettingController@optimize) */
    public function optimize(): void
    {
        $this->confirmOptimize = false;

        $failed = [];
        foreach (['config:cache', 'route:cache', 'view:cache'] as $command) {
            try {
                Artisan::call($command);
            } catch (\Throwable) {
                $failed[] = $command;
            }
        }

        if ($failed !== []) {
            $this->toast('بهینه‌سازی انجام شد اما این مراحل ناموفق بود: '.implode('، ', $failed), 'warning');
        } else {
            $this->toast('سیستم بهینه‌سازی شد.');
        }
    }

    public function render()
    {
        return view('livewire.settings.index');
    }

    /** @return array<string, array<int, string>|string> */
    protected function rules(): array
    {
        return [
            'form.site_name' => ['nullable', 'string', 'max:255'],
            'form.site_description' => ['nullable', 'string', 'max:2000'],
            'form.site_logo' => ['nullable', 'string', 'max:255'],
            'form.theme_color' => ['nullable', 'string', 'max:32'],
            'form.mail_from_address' => ['nullable', 'email', 'max:255'],
            'form.mail_from_name' => ['nullable', 'string', 'max:255'],
            'form.mail_host' => ['nullable', 'string', 'max:255'],
            'form.mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'form.mail_username' => ['nullable', 'string', 'max:255'],
            'form.mail_password' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.mail_from_address.email' => 'آدرس ایمیل فرستنده معتبر نیست.',
            'form.mail_port.integer' => 'پورت باید عدد باشد.',
            'form.mail_port.max' => 'شماره پورت معتبر نیست.',
        ];
    }
}
