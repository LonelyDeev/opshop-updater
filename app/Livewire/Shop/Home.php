<?php

namespace App\Livewire\Shop;

use App\Models\Package;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shop')]
#[Title('فروشگاه پکیج‌ها')]
class Home extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    /** برچسب دسته‌بندی‌ها (یکسان با پنل مدیریت) */
    public const CATEGORIES = [
        'shop'         => 'فروشگاه',
        'payment'      => 'پرداخت',
        'notification' => 'اعلان',
        'seo'          => 'سئو',
        'blog'         => 'بلاگ',
        'utility'      => 'ابزار',
        'theme'        => 'قالب',
        'other'        => 'سایر',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function records()
    {
        return Package::query()
            ->with(['latestVersion', 'activePricingPlans'])
            ->where('status', Package::STATUS_ACTIVE)
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")))
            ->when($this->category !== '', fn ($q) => $q->where('category', $this->category))
            ->latest()
            ->paginate(12);
    }

    /** دسته‌بندی‌های موجود بین پکیج‌های فعال */
    #[Computed]
    public function categories(): array
    {
        return Package::query()
            ->where('status', Package::STATUS_ACTIVE)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->mapWithKeys(fn ($cat) => [$cat => self::CATEGORIES[$cat] ?? $cat])
            ->all();
    }

    /** آمار کلی فروشگاه */
    #[Computed]
    public function stats(): array
    {
        return [
            'packages' => Package::where('status', Package::STATUS_ACTIVE)->count(),
            'versions' => \App\Models\PackageVersion::where('status', \App\Models\PackageVersion::STATUS_ACTIVE)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.shop.home');
    }
}
