<?php

namespace App\Models;

use App\Jobs\SendPushNotificationJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'notifiable_id',
        'notifiable_type',
        'read_at',
        'pushed_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'pushed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $notificacion) {
            SendPushNotificationJob::dispatch($notificacion)
                ->onQueue('high');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function scopeNoLeidas($query)
    {
        return $query->whereNull('read_at');
    }

    public function getIsReadAttribute()
    {
        return !is_null($this->read_at);
    }
}
