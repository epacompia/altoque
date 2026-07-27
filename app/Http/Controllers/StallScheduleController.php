<?php

namespace App\Http\Controllers;

use App\Models\FoodStall;
use App\Models\StallPause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StallScheduleController extends Controller
{
    /**
     * RF10 - MÉTODO 1: Obtener horarios actuales del puesto
     * GET /api/mis-puesto/horarios
     * FASE 2: Con caching en Redis
     */
    public function obtenerHorarios(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            // Cache solo los datos fijos del horario, no el estado actual
            $cacheKey = "horarios_puesto_{$usuario->id}";
            
            $puesto = FoodStall::where('seller_id', $usuario->id)->first();
            
            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado'
                ], 404);
            }

            $horario = Cache::remember($cacheKey, 3600, function () use ($puesto) {
                return [
                    'apertura' => $puesto->opening_time,
                    'cierre' => $puesto->closing_time,
                    'activo' => $puesto->active
                ];
            });

            return response()->json([
                'puesto_id' => $puesto->id,
                'puesto_nombre' => $puesto->name,
                'horario' => $horario,
                'estado_actual' => [
                    'abierto_ahora' => $puesto->isOpenNow(),
                    'en_pausa_ahora' => $puesto->isInPauseNow(),
                    'hora_actual' => now()->format('H:i:s')
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener horarios: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener horarios',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF10 - MÉTODO 2: Actualizar horarios de apertura/cierre
     * PUT /api/mis-puesto/horarios
     * FASE 2: Invalida caché al actualizar
     */
    public function actualizarHorarios(Request $request)
    {
        try {
            $usuario = Auth::user();

            $validado = $request->validate([
                'horario_apertura' => 'required|date_format:H:i',
                'horario_cierre' => 'required|date_format:H:i',
                'activo' => 'nullable|boolean'
            ], [
                'horario_apertura.required' => 'El horario de apertura es obligatorio',
            ]);

            $apertura = $validado['horario_apertura'];
            $cierre = $validado['horario_cierre'];

            if ($apertura === $cierre) {
                return response()->json([
                    'error' => 'El horario de apertura y cierre no pueden ser iguales'
                ], 422);
            }

            if ($apertura < $cierre) {
                $diferenciaMinutos = (strtotime($cierre) - strtotime($apertura)) / 60;
                if ($diferenciaMinutos < 60) {
                    return response()->json([
                        'error' => 'El horario de atención debe ser al menos de 1 hora'
                    ], 422);
                }
            }

            $puesto = FoodStall::where('seller_id', $usuario->id)->first();

            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado'
                ], 404);
            }

            $puesto->update([
                'opening_time' => $validado['horario_apertura'],
                'closing_time' => $validado['horario_cierre'],
                'active' => $validado['activo'] ?? $puesto->active
            ]);

            // FASE 2: Invalidar caché al actualizar
            Cache::forget("horarios_puesto_{$usuario->id}");
            Cache::forget("pausas_puesto_{$usuario->id}");
            Cache::forget("stall.{$puesto->id}.open_now");
            Cache::forget("stall.{$puesto->id}.in_pause");

            Log::info('Horarios actualizados', [
                'stall_id' => $puesto->id,
                'usuario_id' => $usuario->id
            ]);

            return response()->json([
                'message' => 'Horarios actualizados exitosamente',
                'horario' => [
                    'apertura' => $puesto->opening_time,
                    'cierre' => $puesto->closing_time,
                    'activo' => $puesto->active
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Errores de validación',
                'errores' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al actualizar horarios: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar horarios',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF10 - MÉTODO 3: Crear pausa temporal
     * POST /api/mis-puesto/pausas
     * FASE 2: Invalida caché al crear
     */
    public function crearPausa(Request $request)
    {
        try {
            $usuario = Auth::user();

            $validado = $request->validate([
                'razon' => 'nullable|string|max:255',
                'inicio' => 'required|date_format:Y-m-d H:i',
                'fin' => 'required|date_format:Y-m-d H:i|after:inicio'
            ], [
                'inicio.required' => 'La fecha/hora de inicio es obligatoria',
                'fin.after' => 'La fecha/hora de fin debe ser posterior al inicio'
            ]);

            $puesto = FoodStall::where('seller_id', $usuario->id)->first();

            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado'
                ], 404);
            }

            $pausa = StallPause::create([
                'stall_id' => $puesto->id,
                'reason' => $validado['razon'],
                'start_at' => Carbon::createFromFormat('Y-m-d H:i', $validado['inicio']),
                'end_at' => Carbon::createFromFormat('Y-m-d H:i', $validado['fin']),
                'is_active' => true
            ]);

            // FASE 2: Invalidar caché al crear pausa
            Cache::forget("pausas_puesto_{$usuario->id}");
            Cache::forget("stall.{$puesto->id}.open_now");
            Cache::forget("stall.{$puesto->id}.in_pause");

            Log::info('Pausa creada', [
                'stall_id' => $puesto->id,
                'pause_id' => $pausa->id,
                'usuario_id' => $usuario->id
            ]);

            return response()->json([
                'message' => 'Pausa temporal creada exitosamente',
                'pausa' => [
                    'id' => $pausa->id,
                    'razon' => $pausa->reason,
                    'inicio' => $pausa->start_at->format('Y-m-d H:i:s'),
                    'fin' => $pausa->end_at->format('Y-m-d H:i:s'),
                    'activa' => $pausa->is_active
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Errores de validación',
                'errores' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al crear pausa: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al crear pausa',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF10 - MÉTODO 4: Listar pausas del puesto
     * GET /api/mis-puesto/pausas
     * FASE 2: Con caching en Redis
     */
    public function listarPausas(Request $request)
    {
        try {
            $usuario = Auth::user();

            $puesto = FoodStall::where('seller_id', $usuario->id)->first();

            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado'
                ], 404);
            }

            // FASE 2: Usar caché con clave única por puesto
            $cacheKey = "pausas_puesto_{$usuario->id}";
            
            $pausasCache = Cache::remember($cacheKey, 600, function () use ($puesto) {
                return $puesto->pauses()
                             ->orderBy('start_at', 'desc')
                             ->get()
                             ->map(function ($pausa) {
                                 return [
                                     'id' => $pausa->id,
                                     'razon' => $pausa->reason,
                                     'inicio' => $pausa->start_at->format('Y-m-d H:i:s'),
                                     'fin' => $pausa->end_at->format('Y-m-d H:i:s'),
                                     'activa' => $pausa->is_active,
                                     'es_vigente' => $pausa->start_at <= now() && $pausa->end_at > now()
                                 ];
                             });
            });

            return response()->json([
                'puesto_id' => $puesto->id,
                'total_pausas' => $pausasCache->count(),
                'pausas' => $pausasCache
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al listar pausas: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al listar pausas',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF10 - MÉTODO 5: Actualizar pausa
     * PATCH /api/mis-puesto/pausas/{id}
     * FASE 2: Invalida caché al actualizar
     */
    public function actualizarPausa(Request $request, $id)
    {
        try {
            $usuario = Auth::user();

            $validado = $request->validate([
                'razon' => 'nullable|string|max:255',
                'inicio' => 'nullable|date_format:Y-m-d H:i',
                'fin' => 'nullable|date_format:Y-m-d H:i',
                'activa' => 'nullable|boolean'
            ]);

            $pausa = StallPause::with('stall')->findOrFail($id);

            // Verificar que pertenezca al puesto del usuario
            if ($pausa->stall->seller_id !== $usuario->id) {
                return response()->json([
                    'error' => 'No tienes permiso para editar esta pausa'
                ], 403);
            }

            $updates = [];
            if (isset($validado['razon'])) {
                $updates['reason'] = $validado['razon'];
            }
            if (isset($validado['inicio'])) {
                $updates['start_at'] = Carbon::createFromFormat('Y-m-d H:i', $validado['inicio']);
            }
            if (isset($validado['fin'])) {
                $updates['end_at'] = Carbon::createFromFormat('Y-m-d H:i', $validado['fin']);
            }
            if (isset($validado['activa'])) {
                $updates['is_active'] = $validado['activa'];
            }

            $pausa->update($updates);

            // FASE 2: Invalidar caché al actualizar pausa
            Cache::forget("pausas_puesto_{$usuario->id}");
            Cache::forget("stall.{$pausa->stall_id}.open_now");
            Cache::forget("stall.{$pausa->stall_id}.in_pause");

            Log::info('Pausa actualizada', [
                'pause_id' => $id,
                'usuario_id' => $usuario->id
            ]);

            return response()->json([
                'message' => 'Pausa actualizada exitosamente',
                'pausa' => [
                    'id' => $pausa->id,
                    'razon' => $pausa->reason,
                    'inicio' => $pausa->start_at->format('Y-m-d H:i:s'),
                    'fin' => $pausa->end_at->format('Y-m-d H:i:s'),
                    'activa' => $pausa->is_active
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al actualizar pausa: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar pausa',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF10 - MÉTODO 6: Eliminar pausa
     * DELETE /api/mis-puesto/pausas/{id}
     * FASE 2: Invalida caché al eliminar
     */
    public function eliminarPausa($id)
    {
        try {
            $usuario = Auth::user();

            $pausa = StallPause::with('stall')->findOrFail($id);

            // Verificar que pertenezca al puesto del usuario
            if ($pausa->stall->seller_id !== $usuario->id) {
                return response()->json([
                    'error' => 'No tienes permiso para eliminar esta pausa'
                ], 403);
            }

            $pausa->delete();

            // FASE 2: Invalidar caché al eliminar pausa
            Cache::forget("pausas_puesto_{$usuario->id}");
            Cache::forget("stall.{$pausa->stall_id}.open_now");
            Cache::forget("stall.{$pausa->stall_id}.in_pause");

            Log::info('Pausa eliminada', [
                'pause_id' => $id,
                'usuario_id' => $usuario->id
            ]);

            return response()->json([
                'message' => 'Pausa eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al eliminar pausa: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al eliminar pausa',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF13 - Configurar tiempos de preparación del puesto
     * PUT /api/mis-puesto/tiempos-preparacion
     */
    public function configurarTiemposPreparacion(Request $request)
    {
        try {
            $usuario = Auth::user();

            $validado = $request->validate([
                'base_preparation_time' => 'nullable|integer|min:5|max:60',
                'time_per_product' => 'nullable|integer|min:1|max:30',
                'time_per_active_order' => 'nullable|integer|min:1|max:30',
                'time_for_delivery' => 'nullable|integer|min:1|max:60'
            ], [
                'base_preparation_time.min' => 'El tiempo base debe ser mínimo 5 minutos',
                'time_per_product.min' => 'Tiempo por producto debe ser mínimo 1 minuto'
            ]);

            $puesto = FoodStall::where('seller_id', $usuario->id)->first();

            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado'
                ], 404);
            }

            // Actualizar solo los campos que se envíen
            if (isset($validado['base_preparation_time'])) {
                $puesto->base_preparation_time = $validado['base_preparation_time'];
            }
            if (isset($validado['time_per_product'])) {
                $puesto->time_per_product = $validado['time_per_product'];
            }
            if (isset($validado['time_per_active_order'])) {
                $puesto->time_per_active_order = $validado['time_per_active_order'];
            }
            if (isset($validado['time_for_delivery'])) {
                $puesto->time_for_delivery = $validado['time_for_delivery'];
            }

            $puesto->save();

            Log::info("Tiempos de preparación actualizados para puesto {$puesto->id}");

            $base = $puesto->base_preparation_time;
            $perProduct = $puesto->time_per_product;
            $perOrder = $puesto->time_per_active_order;
            $delivery = $puesto->time_for_delivery;

            // Órdenes activas en cola
            $ordenesActivas = \App\Models\Order::where('stall_id', $puesto->id)
                ->whereIn('status', ['confirmed', 'preparing', 'ready'])
                ->count();

            $tiempoBase = $base + ($perOrder * $ordenesActivas) + $delivery;

            return response()->json([
                'message' => 'Tiempos de preparación actualizados',
                'base_preparation_time' => $base . ' min',
                'time_per_product' => $perProduct . ' min por unidad',
                'time_per_active_order' => $perOrder . ' min',
                'time_for_delivery' => $delivery . ' min',
                'ordenes_activas_en_cola' => $ordenesActivas,
                'tiempo_estimado_actual' => $tiempoBase . ' min (base + cola + delivery) + ' . $perProduct . ' min por cada unidad pedida'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al configurar tiempos de preparación: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al configurar tiempos',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }
}

