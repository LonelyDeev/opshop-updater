<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagePricingPlan extends Model
{
    use HasFactory;

    protected $table = 'package_pricing_plans';

    protected $fillable = [
        'package_id', 'name', 'duration_months', 'price',
        'discount_price', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'duration_months' => 'integer',
        'price'           => 'integer',
        'discount_price'  => 'integer',
        'is_active'       => 'boolean',
        'sort_order'      => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function purchases()
    {
        return $this->hasMany(PackagePurchase::class, 'pricing_plan_id');
    }

    public function getFinalPriceAttribute(): int
    {
        return $this->discount_price ?? $this->price;
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->discount_price !== null
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
}
