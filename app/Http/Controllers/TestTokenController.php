<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TestTokenController extends Controller
{
    /**
     * Generar un token de prueba para un usuario específico
     * SOLO para desarrollo/testing - Remover en producción
     * 
     * GET /api/test-token?user_id=1&user_type=cliente
     */
    public function generateTestToken(Request $request)
    {
        // Validar que estamos en desarrollo
        if (app()->environment('production')) {
            return response()->json([
                'error' => 'Este endpoint no está disponible en producción'
            ], 403);
        }

        $userId = $request->query('user_id', 1);
        $userType = $request->query('user_type', 'cliente');

        // Mapear tipos a IDs o emails
        $users = [
            'cliente' => 1,
            'vendedor' => 2,
            'admin' => 3,
        ];

        if (!isset($users[$userType])) {
            return response()->json([
                'error' => 'user_type inválido',
                'tipos_disponibles' => array_keys($users)
            ], 400);
        }

        $user = User::find($users[$userType]);

        if (!$user) {
            return response()->json([
                'error' => "Usuario de tipo '{$userType}' no encontrado"
            ], 404);
        }

        // Generar token
        $token = $user->createToken('test-token')->plainTextToken;

        return response()->json([
            'message' => 'Token generado para testing',
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => null,
            'instrucciones' => 'Usa este token en el header: Authorization: Bearer ' . $token
        ]);
    }
}
