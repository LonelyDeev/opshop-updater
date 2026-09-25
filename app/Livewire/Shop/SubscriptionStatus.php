<?php

namespace App\Livewire\Shop;

use App\Livewire\Concerns\WithToasts;
use App\Models\SubscriptionRequest;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('وضعیت درخواست اشتراک')]
class SubscriptionStatus extends Component
{
    use WithToasts;

    public SubscriptionRequest $request;

    /**
     * پارامتر مسیر {request} به‌صورت خودکار توسط Livewire به مدل
     * SubscriptionRequest تبدیل می‌شود (implicit binding)؛ رکورد ناموجود
     * → ModelNotFoundException → HTTP 404.
     */
    public function mount(SubscriptionRequest $request): void
    {
        $request->loadMissing([
            'plan:id,name,description,duration_months,price,discount_price,is_free,is_one_time',
            'plan.packages:id,name,slug',
            'customer:id,name,email',
        ]);

        $this->request = $request;
    }

    /** لایسنس‌های صادرشده برای پکیج‌های طرح (پس از تأیید مدیر) */
    #[Computed]
    public function licenses()
    {
        return $this->request->licenses()
            ->with('package:id,name,slug')
            ->get();
    }

    /**
     * به‌روزرسانی وضعیت (دکمه دستی + poll خودکار هنگام انتظار) —
     * callback درگاه و تأیید مدیر ممکن است جدا از این تب رخ داده باشند.
     * toast فقط هنگام «تازه تأییدشده» صادر می‌شود تا poll بی‌سروصدا بماند.
     */
    public function refreshStatus(): void
    {
        $wasApproved = $this->request->isApproved();

        $this->request->refresh();
        $this->request->load([
            'plan:id,name,description,duration_months,price,discount_price,is_free,is_one_time',
            'plan.packages:id,name,slug',
            'customer:id,name,email',
        ]);

        if (!$wasApproved && $this->request->isApproved()) {
            $this->toast('درخواست شما تأیید شد؛ طرح شما فعال شد.');
        }
    }

    public function render()
    {
        return view('livewire.shop.subscription-status');
    }
}
