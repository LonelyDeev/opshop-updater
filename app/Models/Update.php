<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Update extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'version',
        'description',
        'type',
        'status',
        'download_link',
        'release_date',
        'is_mandatory',
    ];

    protected $casts = [
        'release_date' => 'datetime',
        'is_mandatory' => 'boolean',
    ];

    const TYPE_MAJOR = 'major';
    const TYPE_MINOR = 'minor';
    const TYPE_PATCH = 'patch';

    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';

    public static function getTypes()
    {
        return [
            self::TYPE_MAJOR => 'اصلی (Major)',
            self::TYPE_MINOR => 'فرعی (Minor)',
            self::TYPE_PATCH => 'اصلاحی (Patch)',
        ];
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_DRAFT => 'پیش‌نویس',
            self::STATUS_ACTIVE => 'فعال',
            self::STATUS_ARCHIVED => 'بایگانی شده',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeLatestVersion($query)
    {
        return $query->orderBy('release_date', 'desc');
    }
}
