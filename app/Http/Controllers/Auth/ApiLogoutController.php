<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiLogoutController extends Controller
{
    /**
     * Handle an incoming logout request for API.
     */
    public function destroy(Request $request): JsonResponse
    {
        // Revocar todos los tokens del usuario
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente',
        ], 200);
    }
}
