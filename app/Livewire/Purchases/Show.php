<?php

namespace App\Livewire\Purchases;

use App\Models\PackagePurchase;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('جزئیات خرید')]
class Show extends Component
{
    public PackagePurchase $purchase;

    public function mount(PackagePurchase $purchase): void
    {
        $purchase->load([
            'package:id,name,slug',
            'customer',
            'license:id,license_key,status,expires_at,duration_months',
            'pricingPlan:id,name,price,discount_price,duration_months,is_one_time',
            'version:id,version',
        ]);

        $this->purchase = $purchase;
    }

    public function render()
    {
        return view('livewire.purchases.show');
    }
}
