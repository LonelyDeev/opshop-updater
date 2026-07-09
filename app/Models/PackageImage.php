<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PackageImage extends Model
{
    use HasFactory;

    protected $table = 'package_images';

    protected $fillable = [
        'package_id', 'path', 'original_name', 'alt',
        'size', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'size'       => 'integer',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];
    protected $appends = ['url'];
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /* ---------------- Accessors ---------------- */

    public function getUrlAttribute(): string
    {
        if (!$this->path) {
            return asset('back/assets/images/default-image.png');
        }

        // اگر URL کامل است
        if (preg_match('#^https?://#', $this->path)) {
            return $this->path;
        }

        // اگر در پوشه public است
        if (file_exists(public_path($this->path))) {
            return asset($this->path);
        }

        return asset('back/assets/images/default-image.png');
    }

    public function getSizeHumanAttribute(): string
    {
        if (!$this->size) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $this->size) - 1) / 3);
        return sprintf("%.2f %s", $this->size / pow(1024, $factor), $units[$factor] ?? 'B');
    }
}
