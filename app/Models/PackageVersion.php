<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PackageVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id', 'version', 'type', 'changelog', 'what_added',
        'what_changed', 'what_fixed', 'file_path', 'file_size', 'file_hash',
        'min_project_version','min_php_version', 'min_laravel_version', 'dependencies',
        'is_mandatory', 'status', 'downloads_count', 'release_date',
    ];

    protected $casts = [
        'is_mandatory'    => 'boolean',
        'downloads_count' => 'integer',
        'release_date'    => 'datetime',
        'dependencies'    => 'array',
    ];

    public const STATUS_DRAFT    = 'draft';
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_MAJOR = 'major';
    public const TYPE_MINOR = 'minor';
    public const TYPE_PATCH = 'patch';

    /* ---------------- Relationships ---------------- */

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function downloadTokens(): HasMany
    {
        return $this->hasMany(PackageDownloadToken::class);
    }

    /* ---------------- Helpers ---------------- */

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function incrementDownloads(): void
    {
        $this->increment('downloads_count');
    }

    public function getAbsoluteFilePath(): string
    {
        return Storage::disk('local')->path($this->file_path);
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('local')->url($this->file_path) : null;
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return '—';
        }
        $bytes = is_numeric($this->file_size) ? (int) $this->file_size : 0;
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor] ?? 'B');
    }
}
