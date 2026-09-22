<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\WithToasts;
use App\Models\PackagePurchase;
use App\Services\PaymentService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('نتیجه پرداخت')]
class PaymentResult extends Component
{
    use WithToasts;

    public PackagePurchase $purchase;

    public function mount(PackagePurchase $purchase): void
    {
        $purchase->load([
            'package:id,name,slug,thumbnail,is_free',
            'customer:id,name,email',
            'license',
            'license.renewedFrom:id,license_key',
            'pricingPlan',
            'version:id,version',
        ]);

        $this->purchase = $purchase;
    }

    /**
     * بررسی مجدد وضعیت پرداخت در انتظار (callback ممکن است رسیده نباشد).
     */
    public function recheck(PaymentService $paymentService): void
    {
        if ($this->purchase->status !== PackagePurchase::STATUS_PENDING) {
            $this->toast('این خرید در انتظار تأیید نیست.');
            return;
        }

        if (!$this->purchase->transaction_id) {
            $this->toast('شناسه تراکنش برای این خرید ثبت نشده است.', 'error');
            return;
        }

        $result = $paymentService->verifyPayment($this->purchase->transaction_id, renew: true);

        $this->purchase->refresh();
        $this->purchase->load(['license', 'license.renewedFrom:id,license_key']);

        if ($result['paid'] ?? false) {
            $this->toast($result['message'] ?? 'پرداخت با موفقیت تأیید شد.');
        } else {
            $this->toast($result['message'] ?? 'پرداخت هنوز تأیید نشده است.', 'error');
        }
    }

    public function render()
    {
        return view('livewire.shop.payment-result');
    }
}
