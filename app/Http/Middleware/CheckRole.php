<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if (in_array($request->user()->role, $roles)) {
            return $next($request);
        }

        return response()->json([
            'error' => 'No tienes permiso para acceder a este recurso',
            'rol_requerido' => $roles,
            'rol_actual' => $request->user()->role
        ], 403);
    }
}
