<?php

namespace App\Livewire\Licenses;

use App\Livewire\Concerns\WithToasts;
use App\Models\Package;
use App\Models\PackageLicense;
use App\Services\LicenseService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('لایسنس‌ها')]
class Index extends Component
{
    use WithPagination, WithToasts;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $package_id = '';

    /** مرتب‌سازی — پیش‌فرض همان ترتیب قبلی صفحه (نزدیک‌ترین انقضا) است */
    #[Url]
    public string $sort = 'expiry_soonest';

    public ?int $revokeId = null;

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

    public function updatedPackageId(): void
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
        return PackageLicense::query()
            ->with(['package:id,name,slug', 'customer:id,name,phone'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('license_key', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($cq) => $cq
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%"))))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->package_id, fn ($q) => $q->where('package_id', (int) $this->package_id))
            ->when($this->sort === 'newest', fn ($q) => $q->latest())
            ->when($this->sort === 'oldest', fn ($q) => $q->oldest())
            ->when($this->sort === 'id_desc', fn ($q) => $q->orderByDesc('id'))
            ->when($this->sort === 'id_asc', fn ($q) => $q->orderBy('id'))
            ->when($this->sort === 'expiry_soonest', fn ($q) => $q->orderByRaw('expires_at is null')->orderBy('expires_at'))
            ->when($this->sort === 'expiry_latest', fn ($q) => $q->orderByRaw('expires_at is null desc')->orderByDesc('expires_at'))
            ->paginate(12);
    }

    #[Computed]
    public function packages()
    {
        return Package::query()->orderBy('name')->get(['id', 'name']);
    }

    /* ---------------------------------------------------------------- */
    /*  Actions (ported from old PackageLicenseController)               */
    /* ---------------------------------------------------------------- */

    public function revoke(): void
    {
        $license = PackageLicense::findOrFail($this->revokeId ?? 0);

        $license->revoke();

        $this->revokeId = null;
        $this->toast('لایسنس باطل شد.');
    }

    public function activate(int $id): void
    {
        $license = PackageLicense::findOrFail($id);

        $license->update(['status' => PackageLicense::STATUS_ACTIVE]);

        $this->toast('لایسنس فعال شد.');
    }

    public function expireOld(): void
    {
        $count = app(LicenseService::class)->expireOldLicenses();

        $this->toast(fa_num($count) . ' لایسنس منقضی به‌روزرسانی شد.');
    }

    /* ---------------------------------------------------------------- */
    /*  حذف تکی / چندتایی                                               */
    /* ---------------------------------------------------------------- */

    public function delete(): void
    {
        $license = PackageLicense::findOrFail($this->deleteId ?? 0);

        $license->delete();
        $this->deleteId = null;
        $this->toast('لایسنس حذف شد.');
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
        foreach (PackageLicense::whereIn('id', $this->selected)->get() as $license) {
            $license->delete();
            $count++;
        }

        $this->clearSelection();
        $this->showBulkModal = false;
        $this->toast(fa_num($count) . ' لایسنس انتخاب‌شده حذف شد.');
    }

    public function render()
    {
        return view('livewire.licenses.index');
    }
}
