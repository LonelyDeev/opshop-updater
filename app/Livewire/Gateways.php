<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithToasts;
use App\Models\Gateway;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('درگاه‌های پرداخت')]
class Gateways extends Component
{
    use WithToasts;

    /** @var array<int, array<string, mixed>> */
    public array $gateways = [];

    public function mount(): void
    {
        $this->loadGateways();
    }

    /* ---------------------------------------------------------------- */
    /*  Actions (پورت‌شده از SettingController@updateGateways)           */
    /* ---------------------------------------------------------------- */

    public function save(): void
    {
        $this->validate([
            'gateways.*.name' => ['required', 'string', 'max:255'],
            'gateways.*.ordering' => ['nullable', 'integer', 'min:0'],
        ], [
            'gateways.*.name.required' => 'عنوان درگاه الزامی است.',
            'gateways.*.ordering.integer' => 'ترتیب نمایش باید عدد باشد.',
        ]);

        foreach ($this->gateways as $gateway) {
            $model = Gateway::find($gateway['id']);

            if (! $model) {
                continue;
            }

            $model->update([
                'name' => $gateway['name'],
                'ordering' => $gateway['ordering'] === '' || $gateway['ordering'] === null ? null : (int) $gateway['ordering'],
                'is_active' => (bool) $gateway['is_active'],
            ]);

            foreach ($gateway['configs'] as $key => $value) {
                $model->configs()->updateOrCreate(
                    ['key' => $key],
                    ['value' => (string) $value]
                );
            }
        }

        $this->toast('تنظیمات درگاه‌های پرداخت با موفقیت ذخیره شد.');
    }

    public function render()
    {
        return view('livewire.gateways');
    }

    /* ---------------------------------------------------------------- */
    /*  Helpers                                                          */
    /* ---------------------------------------------------------------- */

    /**
     * ساختار درگاه‌ها و فیلدهای پیکربندی آن‌ها (پورت gwList در ویو قدیمی).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function schema(): array
    {
        return [
            'payir' => ['label' => 'درگاه pay.ir', 'fields' => [
                ['name' => 'merchantId', 'label' => 'api کد', 'type' => 'text'],
            ]],
            'behpardakht' => ['label' => 'درگاه بانک ملت', 'fields' => [
                ['name' => 'username', 'label' => 'نام کاربری', 'type' => 'text'],
                ['name' => 'password', 'label' => 'رمز عبور', 'type' => 'text'],
                ['name' => 'terminalId', 'label' => 'کد پذیرنده', 'type' => 'text'],
            ]],
            'zarinpal' => ['label' => 'درگاه زرین پال', 'fields' => [
                ['name' => 'merchantId', 'label' => 'کد درگاه پرداخت', 'type' => 'text'],
            ]],
            'toman' => ['label' => 'درگاه تومن', 'fields' => [
                ['name' => 'shop_slug', 'label' => 'shop slug', 'type' => 'text'],
                ['name' => 'auth_code', 'label' => 'auth code', 'type' => 'text'],
            ]],
            'payping' => ['label' => 'درگاه پی پینگ', 'fields' => [
                ['name' => 'merchantId', 'label' => 'کد درگاه پرداخت', 'type' => 'text'],
            ]],
            'irankish' => ['label' => 'درگاه ایران کیش', 'fields' => [
                ['name' => 'terminalId', 'label' => 'کد پایانه', 'type' => 'text'],
                ['name' => 'acceptorId', 'label' => 'کد پذیرنده', 'type' => 'text'],
                ['name' => 'password', 'label' => 'کلمه عبور', 'type' => 'text'],
                ['name' => 'pubKey', 'label' => 'کلید عمومی', 'type' => 'textarea'],
            ]],
            'idpay' => ['label' => 'درگاه idpay', 'fields' => [
                ['name' => 'merchantId', 'label' => 'کد درگاه پرداخت', 'type' => 'text'],
            ]],
            'sepehr' => ['label' => 'درگاه سپهر (بانک صادرات)', 'fields' => [
                ['name' => 'terminalId', 'label' => 'کد پذیرنده', 'type' => 'text'],
            ]],
            'saman' => ['label' => 'درگاه سامان', 'fields' => [
                ['name' => 'merchantId', 'label' => 'کد پذیرنده', 'type' => 'text'],
            ]],
            'sadad' => ['label' => 'درگاه بانک ملی', 'fields' => [
                ['name' => 'terminalId', 'label' => 'شماره پذیرنده', 'type' => 'text'],
                ['name' => 'merchantId', 'label' => 'کد پذیرنده', 'type' => 'text'],
                ['name' => 'key', 'label' => 'کلید تراکنش', 'type' => 'text'],
            ]],
            'zibal' => ['label' => 'درگاه زیبال', 'fields' => [
                ['name' => 'merchantId', 'label' => 'کد پذیرنده', 'type' => 'text'],
            ]],
        ];
    }

    /**
     * بارگذاری درگاه‌ها از دیتابیس (پورت SettingController@showGateways).
     */
    protected function loadGateways(): void
    {
        // اطمینان از وجود همه درگاه‌های پشتیبانی‌شده
        foreach (config('general.supported_gateways') as $key => $name) {
            Gateway::firstOrCreate(
                ['key' => $key],
                ['name' => $name]
            );
        }

        $rows = Gateway::query()->with('configs')->get()->keyBy('key');

        $this->gateways = [];

        foreach (static::schema() as $key => $meta) {
            $gateway = $rows->get($key);

            if (! $gateway) {
                continue;
            }

            $stored = $gateway->configs->pluck('value', 'key')->all();

            $configs = [];
            foreach ($meta['fields'] as $field) {
                $configs[$field['name']] = (string) ($stored[$field['name']] ?? '');
            }

            $this->gateways[] = [
                'id' => (int) $gateway->id,
                'key' => (string) $gateway->key,
                'name' => (string) $gateway->name,
                'ordering' => $gateway->ordering,
                'is_active' => (bool) $gateway->is_active,
                'configs' => $configs,
            ];
        }
    }
}
