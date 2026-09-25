<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\WithToasts;
use App\Models\Customer;
use App\Models\Gateway;
use App\Models\SubscriptionOrder;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('طرح‌های اشتراک')]
class SubscriptionsIndex extends Component
{
    use WithToasts;

    /** کد آپدیت مشتری */
    public string $updateCode = '';

    /** درگاه انتخابی برای خرید */
    public string $gatewayKey = '';

    /** طرح در حال خرید (slug) */
    public string $buyingSlug = '';

    public function mount(): void
    {
        $this->updateCode = (string) session('shop_update_code', '');
        $this->gatewayKey = (string) $this->gateways->first()?->key ?? '';
    }

    #[Computed]
    public function plans()
    {
        return SubscriptionPlan::query()
            ->active()
            ->with(['packages:id,name,slug,category'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    #[Computed]
    public function gateways()
    {
        return Gateway::query()
            ->active()
            ->orderBy('ordering')
            ->orderBy('name')
            ->get();
    }

    /** وضعیت سفارش‌های این مشتری برای هر طرح (یک‌بارمصرف/قبلاً خریداری) */
    #[Computed]
    public function myOrders()
    {
        $code = trim($this->updateCode);

        if ($code === '') {
            return collect();
        }

        $customer = Customer::where('update_code', $code)->first();

        if (!$customer) {
            return collect();
        }

        return SubscriptionOrder::query()
            ->where('customer_id', $customer->id)
            ->whereIn('admin_status', [SubscriptionOrder::ADMIN_STATUS_PENDING, SubscriptionOrder::ADMIN_STATUS_APPROVED, SubscriptionOrder::ADMIN_STATUS_REJECTED])
            ->get()
            ->keyBy('subscription_plan_id');
    }

    public function setBuying(string $slug): void
    {
        $this->buyingSlug = $this->buyingSlug === $slug ? '' : $slug;
    }

    public function buy(SubscriptionService $service): void
    {
        $this->validate([
            'updateCode' => ['required', 'string', 'max:64'],
        ], [
            'updateCode.required' => 'کد آپدیت الزامی است.',
        ]);

        $plan = SubscriptionPlan::active()->where('slug', trim($this->buyingSlug))->first();

        if (!$plan) {
            $this->toast('طرح انتخاب‌شده معتبر نیست.', 'error');

            return;
        }

        $customer = Customer::query()
            ->where('update_code', trim($this->updateCode))
            ->first();

        if (!$customer) {
            $this->toast('کد آپدیت نامعتبر است.', 'error');

            return;
        }

        if ($customer->status !== 'active') {
            $this->toast('حساب این مشتری غیرفعال است.', 'error');

            return;
        }

        try {
            $result = $service->purchase(
                $customer,
                $plan,
                $plan->final_price > 0 ? $this->gatewayKey : null,
                null
            );
        } catch (\Throwable $e) {
            $this->toast($e->getMessage(), 'error');

            return;
        }

        session()->put('shop_update_code', trim($this->updateCode));

        $order = $result['order'];

        // رایگان → مستقیم صفحه نتیجه (در انتظار تأیید مدیر)
        if (($result['is_free'] ?? false)) {
            $this->redirect(route('subscription.result', $order), navigate: true);

            return;
        }

        // پولی → درگاه
        $this->redirect($result['payment_url'], navigate: false);
    }

    public function render()
    {
        return view('livewire.shop.subscriptions-index');
    }
}
