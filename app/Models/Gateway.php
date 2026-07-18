<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $guarded = ['id'];

    public function configs()
    {
        return $this->hasMany(GatewayConfig::class, 'gateway_id');
    }

    public function scopeActive($query)
    {
        $supported_gateways = array_keys(config('general.supported_gateways'));

        return $query->whereIn('key', $supported_gateways)
            ->where('is_active', true);
    }

    public function config($key)
    {
        $config = $this->configs()->where('gateway_configs.key', $key)->first();

        return $config ? $config->value : null;
    }
}
