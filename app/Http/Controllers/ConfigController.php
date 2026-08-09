<?php

namespace App\Http\Controllers;

use App\Models\UserConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ConfigController extends Controller
{
    protected function defaults(): array
    {
        return [
            'tema' => 'sistema',
            'idioma' => 'es',
            'notificaciones' => [
                'push' => true,
                'promociones' => false,
                'sonido' => true,
                'vibracion' => false,
            ],
            'unidades' => 'metric',
            'moneda' => 'soles',
            'seguridad' => [
                'biometria' => false,
            ],
        ];
    }

    public function index(Request $request)
    {
        $usuario = Auth::user();

        $config = Cache::remember("user.{$usuario->id}.config", 30, function () use ($usuario) {
            $row = UserConfig::where('user_id', $usuario->id)->first();
            return $row ? array_merge($this->defaults(), $row->data) : $this->defaults();
        });

        return response()->json([
            'configuracion' => $config,
            'role' => $usuario->role,
        ]);
    }

    public function update(Request $request)
    {
        $usuario = Auth::user();

        $validated = $request->validate([
            'tema' => ['sometimes', Rule::in(['claro', 'oscuro', 'sistema'])],
            'idioma' => ['sometimes', 'string', Rule::in(['es', 'en'])],
            'notificaciones' => ['sometimes', 'array'],
            'notificaciones.push' => 'sometimes|boolean',
            'notificaciones.promociones' => 'sometimes|boolean',
            'notificaciones.sonido' => 'sometimes|boolean',
            'notificaciones.vibracion' => 'sometimes|boolean',
            'unidades' => ['sometimes', Rule::in(['metric', 'imperial'])],
            'moneda' => ['sometimes', Rule::in(['soles', 'dolares'])],
            'seguridad' => ['sometimes', 'array'],
            'seguridad.biometria' => 'sometimes|boolean',
        ]);

        $fila = UserConfig::firstOrNew(['user_id' => $usuario->id]);
        $fila->data = array_replace_recursive($this->defaults(), $fila->data ?? [], $validated);
        $fila->save();

        Cache::forget("user.{$usuario->id}.config");

        return response()->json([
            'message' => 'Configuración actualizada',
            'configuracion' => $fila->fresh()->data,
        ]);
    }
}