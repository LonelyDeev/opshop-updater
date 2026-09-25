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

    /** مرتب‌سازی — پیش‌فرض همان ترتیب قبلی صفحه (جدیدترین) است */
    #[Url]
    public string $sort = 'newest';

    public ?int $deleteId = null;

    /** @var array<int,int> */
    public array $selected = [];

    public bool $selectAll = false;

    public bool $showBulkModal = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
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
            ->when($this->sort === 'newest', fn ($q) => $q->latest())
            ->when($this->sort === 'oldest', fn ($q) => $q->oldest())
            ->when($this->sort === 'amount_desc', fn ($q) => $q->orderByDesc('amount'))
            ->when($this->sort === 'amount_asc', fn ($q) => $q->orderBy('amount'))
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

    /* ---------------------------------------------------------------- */
    /*  حذف تکی / چندتایی (سوابق مالی)                                   */
    /* ---------------------------------------------------------------- */

    public function delete(): void
    {
        $purchase = PackagePurchase::findOrFail($this->deleteId ?? 0);

        $purchase->delete();
        $this->deleteId = null;
        $this->toast('سابقه خرید حذف شد.');
    }

    public function updatedSelectAll(bool $value): void
    {
        $ids = $this->records()->pluck('id')->all();

        $this->selected = $value
            ? array_values(array_unique(array_merge($this->selected, $ids)))
            : array_values(array_diff($this->selected, $ids));
    }

    public function toggleSelect(int $id): void
    {
        $this->selected = in_array($id, $this->selected)
            ? array_values(array_diff($this->selected, [$id]))
            : array_values(array_merge($this->selected, [$id]));

        // همگام‌سازی چک‌باکس سربرگ با وضعیت صفحه فعلی
        $pageIds = $this->records()->pluck('id')->all();
        $this->selectAll = $pageIds !== [] && array_diff($pageIds, $this->selected) === [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function confirmBulkDelete(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->showBulkModal = true;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            $this->showBulkModal = false;

            return;
        }

        $count = 0;
        foreach (PackagePurchase::whereIn('id', $this->selected)->get() as $purchase) {
            $purchase->delete();
            $count++;
        }

        $this->clearSelection();
        $this->showBulkModal = false;
        $this->toast(fa_num($count) . ' سابقه خرید انتخاب‌شده حذف شد.');
    }

    public function render()
    {
        return view('livewire.purchases.index');
    }
}
