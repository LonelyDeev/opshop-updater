<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * درخواست خرید/فعال‌سازی طرح اشتراک
 *
 * چرخه: مشتری درخواست می‌دهد → پرداخت از درگاه (یا طرح رایگان) →
 * در انتظار تأیید مدیر → تأیید (فعال‌سازی لایسنس‌های پکیج‌های طرح) یا رد.
 */
class SubscriptionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'subscription_plan_id', 'gateway', 'amount',
        'transaction_id', 'payment_url', 'callback_url', 'payment_status',
        'status', 'admin_note', 'paid_at', 'approved_at', 'rejected_at', 'meta',
    ];

    protected $casts = [
        'amount'      => 'integer',
        'paid_at'     => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'meta'        => 'array',
    ];

    /* وضعیت پرداخت */
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID    = 'paid';
    public const PAYMENT_FAILED  = 'failed';
    public const PAYMENT_FREE    = 'free';

    /* وضعیت درخواست */
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /* ---------------- Relationships ---------------- */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /** لایسنس‌هایی که با تأیید همین درخواست صادر/تمدید شده‌اند */
    public function licenses(): HasMany
    {
        return $this->hasMany(PackageLicense::class);
    }

    /* ---------------- Helpers ---------------- */

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isFreePlan(): bool
    {
        return $this->payment_status === self::PAYMENT_FREE;
    }

    /** پرداخت انجام شده یا طرح رایگان است */
    public function isPaymentSettled(): bool
    {
        return in_array($this->payment_status, [self::PAYMENT_PAID, self::PAYMENT_FREE], true);
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->isPaymentSettled();
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function markAsPaid(?string $gateway = null): void
    {
        $this->update([
            'payment_status' => self::PAYMENT_PAID,
            'paid_at'        => now(),
            'gateway'        => $gateway ?? $this->gateway,
        ]);
    }

    public function markPaymentFailed(?string $reason = null): void
    {
        $this->update([
            'payment_status' => self::PAYMENT_FAILED,
            'meta'           => array_merge($this->meta ?? [], ['fail_reason' => $reason]),
        ]);
    }

    /* برچسب‌های فارسی برای UI */

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'تأیید شده',
            self::STATUS_REJECTED => 'رد شده',
            default => $this->isPaymentSettled()
                ? 'در انتظار تأیید'
                : 'در انتظار پرداخت',
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PAID => 'پرداخت شده',
            self::PAYMENT_FAILED => 'ناموفق',
            self::PAYMENT_FREE => 'رایگان',
            default => 'در انتظار',
        };
    }

    /** رنگ بج وضعیت برای کامپوننت x-badge */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => $this->isPaymentSettled() ? 'warning' : 'neutral',
        };
    }

    public function getPaymentBadgeAttribute(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PAID => 'success',
            self::PAYMENT_FAILED => 'danger',
            self::PAYMENT_FREE => 'info',
            default => 'neutral',
        };
    }
}
