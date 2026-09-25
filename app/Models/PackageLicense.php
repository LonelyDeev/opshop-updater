<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PackageLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_key', 'package_id', 'customer_id', 'purchase_id',
        'subscription_request_id', 'renewed_from', 'status', 'starts_at',
        'expires_at', 'duration_months', 'notes',
    ];

    protected $casts = [
        'starts_at'        => 'datetime',
        'expires_at'       => 'datetime',
        'duration_months'  => 'integer',
    ];

    public const STATUS_ACTIVE  = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    /* ---------------- Relationships ---------------- */

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PackagePurchase::class);
    }

    /** درخواست اشتراکی که باعث صدور/تمدید این لایسنس شده است */
    public function subscriptionRequest(): BelongsTo
    {
        return $this->belongsTo(SubscriptionRequest::class);
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'renewed_from');
    }

    public function downloadTokens(): HasMany
    {
        return $this->hasMany(PackageDownloadToken::class, 'license_id');
    }

    /* ---------------- Helpers ---------------- */

    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->expires_at === null) {
            return true; // نامحدود
        }
        return $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUnlimited(): bool
    {
        return $this->expires_at === null;
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if ($this->expires_at === null) {
            return null; // نامحدود
        }
        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    public function revoke(): void
    {
        $this->update(['status' => self::STATUS_REVOKED]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    public static function generateKey(): string
    {
        // FRMT-XXXX-XXXX-XXXX-XXXX
        $segments = [];
        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(Str::random(4));
        }
        return 'PKG-' . implode('-', $segments);
    }
}
