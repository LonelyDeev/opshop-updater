<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * لاگ ارسال پیامک
 */
class SmsLog extends Model
{
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'mobile', 'template_key', 'driver', 'message', 'pattern_code',
        'status', 'response', 'error', 'loggable_type', 'loggable_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SENT    => 'ارسال شد',
            self::STATUS_FAILED  => 'ناموفق',
            self::STATUS_SKIPPED => 'رد شد',
            default => $this->status,
        };
    }
}
