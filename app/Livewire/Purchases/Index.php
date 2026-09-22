<?php

namespace App\Livewire\Purchases;

use App\Livewire\Concerns\WithToasts;
use App\Models\PackagePurchase;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('خریدها')]
class Index extends Component
{
    use WithPagination, WithToasts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function records()
    {
        return PackagePurchase::query()
            ->with(['package:id,name,slug', 'customer:id,name,phone', 'version:id,version'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('transaction_id', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($cq) => $cq
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%"))))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(12);
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'revenue' => (int) PackagePurchase::where('status', PackagePurchase::STATUS_PAID)->sum('amount'),
            'pending' => PackagePurchase::where('status', PackagePurchase::STATUS_PENDING)->count(),
            'failed'  => PackagePurchase::where('status', PackagePurchase::STATUS_FAILED)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.purchases.index');
    }
}
