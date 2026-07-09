<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PackageDownloadToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token', 'license_id', 'version_id', 'customer_id',
        'expires_at', 'used_at', 'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    /* ---------------- Relationships ---------------- */

    public function license(): BelongsTo
    {
        return $this->belongsTo(PackageLicense::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PackageVersion::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /* ---------------- Helpers ---------------- */

    public function isValid(): bool
    {
        return $this->used_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public function markAsUsed(string $ip = null): void
    {
        $this->update([
            'used_at'    => now(),
            'ip_address' => $ip,
        ]);
    }

    public static function generate(): string
    {
        return Str::random(64);
    }
}
