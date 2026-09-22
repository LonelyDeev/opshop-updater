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

    public ?int $revokeId = null;

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
            ->orderByRaw('expires_at is null')
            ->orderBy('expires_at')
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

    public function render()
    {
        return view('livewire.licenses.index');
    }
}
