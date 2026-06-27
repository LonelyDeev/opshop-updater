<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'website_url',
        'project_id',
        'update_code',
        'status',
    ];

    // رابطه یک‌به‌چند با مدل Update
    public function updates(): HasMany
    {
        return $this->hasMany(Update::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // تولید کد آپدیت در صورت نیاز
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->update_code)) {
                $customer->update_code = self::generateUpdateCode();
            }
        });
    }

    private static function generateUpdateCode(): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(6))); // تولید کد 12 کاراکتری
        } while (self::where('update_code', $code)->exists());

        return $code;
    }
}
