<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * طرح اشتراک (Subscription Plan)
 *
 * طرحی پولی یا رایگان با مدت‌زمان مشخص، قابلیت‌ها و فهرست پکیج‌های همراه.
 * مشتری طرح را از فرانت/خرید می‌کند → درخواست (SubscriptionRequest) ساخته می‌شود →
 * پس از تأیید مدیر، برای پکیج‌های طرح لایسنس صادر/تمدید می‌شود.
 */
class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'duration_months', 'price', 'discount_price',
        'is_free', 'is_one_time', 'is_active', 'sort_order', 'features',
    ];

    protected $casts = [
        'duration_months' => 'integer',
        'price'           => 'integer',
        'discount_price'  => 'integer',
        'is_free'         => 'boolean',
        'is_one_time'     => 'boolean',
        'is_active'       => 'boolean',
        'sort_order'      => 'integer',
        'features'        => 'array',
    ];

    /* ---------------- Relationships ---------------- */

    /** پکیج‌های این طرح (با مدت دسترسی اختصاصی در pivot) */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'subscription_plan_package')
            ->withPivot('duration_months')
            ->withTimestamps()
            ->orderBy('subscription_plan_package.id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(SubscriptionRequest::class);
    }

    /* ---------------- Helpers ---------------- */

    public function getFinalPriceAttribute(): int
    {
        if ($this->is_free) {
            return 0;
        }

        return (int) ($this->discount_price ?? $this->price);
    }

    public function getHasDiscountAttribute(): bool
    {
        return !$this->is_free
            && $this->discount_price !== null
            && $this->discount_price < $this->price;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (!$this->has_discount || $this->price <= 0) {
            return 0;
        }

        return (int) round((1 - $this->discount_price / $this->price) * 100);
    }

    public function getDurationLabelAttribute(): string
    {
        if ($this->duration_months === 0) {
            return 'نامحدود';
        }
        if ($this->duration_months < 12) {
            return $this->duration_months . ' ماه';
        }
        $years = $this->duration_months / 12;

        return ($years == floor($years) ? (int) $years : $years) . ' سال';
    }

    /**
     * مدت دسترسی مؤثر برای پکیج مشخص در این طرح
     * (مدت اختصاصی pivot یا مدت پیش‌فرض طرح)
     */
    public function effectiveDurationFor(Package $package): ?int
    {
        $pivot = $this->packages()->where('packages.id', $package->id)->first()?->pivot;

        return $pivot ? (int) $pivot->duration_months : (int) $this->duration_months;
    }

    /**
     * آیا این مشتری قبلاً این طرحِ یک‌بارمصرف را استفاده (تأیید) کرده است؟
     */
    public function hasCustomerUsed(int $customerId): bool
    {
        if (!$this->is_one_time) {
            return false;
        }

        return $this->requests()
            ->where('customer_id', $customerId)
            ->where('status', SubscriptionRequest::STATUS_APPROVED)
            ->exists();
    }

    /**
     * آیا این مشتری درخواست در جریان (پرداخت‌شده/رایگان و در انتظار تأیید) دارد؟
     */
    public function hasCustomerPending(int $customerId): bool
    {
        return $this->requests()
            ->where('customer_id', $customerId)
            ->where('status', SubscriptionRequest::STATUS_PENDING)
            ->whereIn('payment_status', [
                SubscriptionRequest::PAYMENT_PAID,
                SubscriptionRequest::PAYMENT_FREE,
            ])
            ->exists();
    }
}
