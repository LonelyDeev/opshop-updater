<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'project_id',
        'start_date',
        'end_date',
        'status',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // روابط
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // اسکوپ‌ها
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->where('status', 'active')
            ->where('end_date', '<=', Carbon::now()->addDays($days));
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere('end_date', '<', Carbon::now());
    }

    // متد کمکی برای بررسی انقضا
    public function isExpired(): bool
    {
        if ($this->status === 'expired' || $this->status === 'suspended') {
            return true;
        }
        return $this->end_date < Carbon::now();
    }

    // متد کمکی برای روزهای باقی‌مانده
    public function daysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        return Carbon::today()->diffInDays($this->end_date, false);
    }
}
