<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'repository_url',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ثابت‌های وضعیت
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_PENDING = 'pending';

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'فعال',
            self::STATUS_ARCHIVED => 'بایگانی شده',
            self::STATUS_PENDING => 'در انتظار',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? $this->status;
    }

    // رابطه با آپدیت‌ها
    public function updates(): HasMany
    {
        return $this->hasMany(Update::class);
    }

    // اسکوپ برای پروژه‌های فعال
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
