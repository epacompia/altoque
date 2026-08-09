<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Services\PushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    public function registrar(Request $request)
    {
        $usuario = Auth::user();
        $usuarioId = $usuario->id;

        $validado = $request->validate([
            'fcm_token' => 'required|string',
            'platform' => 'nullable|in:android,ios',
        ]);

        // Evitar tokens duplicados; updateOrCreate por (user_id, fcm_token)
        $token = DeviceToken::updateOrCreate(
            ['user_id' => $usuarioId, 'fcm_token' => $validado['fcm_token']],
            ['platform' => $validado['platform'] ?? null]
        );

        return response()->json([
            'message' => 'Token registrado exitosamente',
            'token' => $token,
        ], 201);
    }

    public function eliminar(Request $request)
    {
        $usuario = Auth::user();

        $tokens = $request->input('fcm_tokens');
        $tokens = is_array($tokens) ? $tokens : [$tokens];

        $cantidad = 0;
        foreach ($tokens as $token) {
            if (is_string($token) && $token !== '') {
                $cantidad += DeviceToken::where('user_id', $usuario->id)
                    ->where('fcm_token', $token)
                    ->delete();
            }
        }

        return response()->json([
            'message' => 'Token(s) eliminado(s)',
            'eliminados' => $cantidad,
        ]);
    }

    public function listar()
    {
        $tokens = DeviceToken::where('user_id', Auth::id())->get();

        return response()->json([
            'tokens' => $tokens,
        ]);
    }

    public function probarPush(Request $request)
    {
        $usuario = Auth::user();

        $validado = $request->validate([
            'titulo' => 'sometimes|string|max:255',
            'cuerpo' => 'sometimes|string|max:1000',
        ]);

        $titulo = $validado['titulo'] ?? '🔔 Notificación de prueba Altoque';
        $cuerpo = $validado['cuerpo'] ?? 'Si ves este mensaje, el push está funcionando correctamente.';

        $resultado = app(PushService::class)->enviar(
            $usuario->id,
            $titulo,
            $cuerpo,
            ['type' => 'push_test', 'notifiable_id' => '0']
        );

        if ($resultado['enviados'] === 0) {
            return response()->json([
                'message' => 'El push no se envió. Revisa que Firebase esté configurado y tengas un token registrado.',
                'detalle' => $resultado,
            ], 400);
        }

        return response()->json([
            'message' => 'Push de prueba enviado',
            'detalle' => $resultado,
        ]);
    }
}