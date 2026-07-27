<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FoodStall extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'seller_id',
        'slug',
        'qr_path',
        'active',
        'address',
        'phone',
        'opening_time',
        'closing_time',
        'latitude',
        'longitude',
        'description'
    ];

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class, 'stall_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function pauses()
    {
        return $this->hasMany(StallPause::class, 'stall_id');
    }

    public function isOpenNow()
    {
        return Cache::remember("stall.{$this->id}.open_now", 300, function () {
            $now = now()->format('H:i');
            $opening = $this->opening_time;
            $closing = $this->closing_time;

            if ($opening < $closing) {
                $abierto = $now >= $opening && $now < $closing;
            } else {
                $abierto = $now >= $opening || $now < $closing;
            }

            if ($abierto && $this->pauses()->active()->exists()) {
                return false;
            }

            return $abierto;
        });
    }

    public function isInPauseNow()
    {
        return Cache::remember("stall.{$this->id}.in_pause", 300, function () {
            return $this->pauses()->active()->exists();
        });
    }
}
