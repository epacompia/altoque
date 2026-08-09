<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public function __construct(public Notification $notificacion)
    {
    }

    public function handle(PushService $pushService): void
    {
        $this->notificacion->loadMissing('user');

        $datos = [
            'type' => $this->notificacion->type,
            'notifiable_id' => $this->notificacion->notifiable_id,
        ];

        $pushService->enviar(
            $this->notificacion->user_id,
            $this->notificacion->title,
            $this->notificacion->body ?? '',
            $datos
        );

        $this->notificacion->update(['pushed_at' => now()]);
    }
}