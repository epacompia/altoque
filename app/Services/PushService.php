<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\UserConfig;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;

class PushService
{
    protected function factory()
    {
        $path = config('services.firebase.credentials', env('FIREBASE_CREDENTIALS', ''));

        if (!$path || !file_exists($path)) {
            throw new \RuntimeException('Firebase credentials no configuradas (FIREBASE_CREDENTIALS).');
        }

        return (new \Kreait\Firebase\Factory())
            ->withServiceAccount($path);
    }

    public function estaConfigurado(): bool
    {
        $path = config('services.firebase.credentials', env('FIREBASE_CREDENTIALS', ''));
        return !empty($path) && file_exists($path);
    }

    /**
     * Envía push a todos los dispositivos del usuario respetando su configuración.
     */
    public function enviar(int $userId, string $titulo, string $cuerpo, array $datos = []): array
    {
        $resultado = ['enviados' => 0, 'omitidos' => 0];

        if (!$this->estaConfigurado()) {
            Log::warning("PushService: Firebase no configurado, push para usuario {$userId} omitido.");
            return $resultado;
        }

        // Switch maestro: si el usuario apagó notificaciones push, no enviamos
        $prefs = UserConfig::where('user_id', $userId)->first()->data ?? [];
        $notifPrefs = array_merge(config('app.user_config_defaults.notificaciones'), $prefs['notificaciones'] ?? []);

        if (isset($notifPrefs['push']) && !$notifPrefs['push']) {
            $resultado['omitidos'] = DeviceToken::where('user_id', $userId)->count();
            return $resultado;
        }

        $tokens = DeviceToken::where('user_id', $userId)->pluck('fcm_token');
        if ($tokens->isEmpty()) {
            return $resultado;
        }

        $sonido = (bool) ($notifPrefs['sonido'] ?? true);
        $vibracion = (bool) ($notifPrefs['vibracion'] ?? true);

        $data = array_merge($datos, [
            'sonido' => $sonido ? '1' : '0',
            'vibracion' => $vibracion ? '1' : '0',
        ]);

        $messaging = $this->factory()->createMessaging();

        $mensaje = CloudMessage::new()
            ->withNotification([
                'title' => $titulo,
                'body' => $cuerpo,
            ])
            ->withData($data);

        if ($sonido) {
            $mensaje = $mensaje->withAndroidConfig([
                'notification' => [
                    'sound' => 'default',
                    'vibrate_timings' => $vibracion ? [0, 500] : [],
                ],
            ])->withApnsConfig([
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                    ],
                ],
            ]);
        }

        foreach ($tokens as $token) {
            try {
                $messaging->send($mensaje->withChangedTarget($token));
                $resultado['enviados']++;
            } catch (\Throwable $e) {
                Log::error("PushService: error enviando a token {$token}: " . $e->getMessage());
            }
        }

        return $resultado;
    }
}