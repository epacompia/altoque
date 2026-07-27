<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCommissionTransfer;
use App\Models\CommissionRule;
use App\Models\OrderCommission;
use App\Models\CommissionAuditLog;
use App\Models\Order;
use App\Services\CommissionService;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CommissionController extends Controller
{
    private $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
        $this->middleware('auth:sanctum');
    }

    /**
     * RF18 - Test 1: Calcular comisión para un pedido
     * POST /api/comisiones/calcular
     */
    public function calcularComision(Request $request)
    {
        try {
            $validado = $request->validate([
                'order_id' => 'required|exists:orders,id'
            ]);

            $order = Order::findOrFail($validado['order_id']);

            // Verificar que el pedido esté confirmado (pagado)
            if ($order->status !== 'confirmed') {
                return response()->json([
                    'error' => 'No puedes calcular comisión de un pedido sin pagar',
                    'estado_actual' => $order->status,
                    'detalles' => 'El pedido debe estar confirmado/pagado'
                ], 409);
            }

            // Calcular comisión
            $orderCommission = $this->commissionService->calculateCommissionForOrder($order);

            return response()->json([
                'exito' => true,
                'mensaje' => 'Comisión calculada exitosamente',
                'datos' => [
                    'comision_id' => $orderCommission->id,
                    'pedido_id' => $orderCommission->order_id,
                    'monto_pedido' => $orderCommission->order_total,
                    'porcentaje_comision' => $orderCommission->commission_percentage . '%',
                    'monto_comision' => $orderCommission->commission_amount,
                    'monto_neto_vendedor' => $orderCommission->net_amount,
                    'regla_aplicada' => $orderCommission->rule_applied,
                    'estado' => $orderCommission->status,
                    'calculada_en' => $orderCommission->calculated_at
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al calcular comisión',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF18 - Test 2: Obtener comisión de un pedido
     * GET /api/comisiones/pedido/{order_id}
     */
    public function obtenerComisionPedido($orderId)
    {
        try {
            $commission = OrderCommission::where('order_id', $orderId)->first();

            if (!$commission) {
                return response()->json([
                    'error' => 'No existe comisión calculada para este pedido'
                ], 404);
            }

            return response()->json([
                'exito' => true,
                'datos' => [
                    'comision_id' => $commission->id,
                    'pedido_id' => $commission->order_id,
                    'monto_pedido' => $commission->order_total,
                    'porcentaje_comision' => $commission->commission_percentage . '%',
                    'monto_comision' => $commission->commission_amount,
                    'monto_neto_vendedor' => $commission->net_amount,
                    'regla_aplicada' => $commission->rule_applied,
                    'estado' => $commission->status,
                    'calculada_en' => $commission->calculated_at,
                    'transferida_en' => $commission->transferred_at
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener comisión',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF19 - Test 1: Listar todas las reglas de comisión
     * GET /api/comisiones/reglas
     * Solo admin
     */
    public function listarReglas(Request $request)
    {
        try {
            // Verificar que sea admin (esto debería estar en middleware)
            $query = CommissionRule::query();

            // Filtros
            if ($request->has('activas')) {
                $query->where('is_active', $request->boolean('activas'));
            }

            if ($request->has('tipo')) {
                $query->where('type', $request->input('tipo'));
            }

            $reglas = $query->orderBy('type')->paginate(50);

            return response()->json([
                'exito' => true,
                'total' => $reglas->total(),
                'pagina' => $reglas->currentPage(),
                'por_pagina' => $reglas->perPage(),
                'datos' => collect($reglas->items())->map(fn ($r) => [
                    'id' => $r->id,
                    'nombre' => $r->name,
                    'tipo' => $r->type,
                    'porcentaje' => $r->commission_percentage . '%',
                    'activa' => $r->is_active ? 'Sí' : 'No',
                    'vigencia_desde' => $r->valid_from,
                    'vigencia_hasta' => $r->valid_until,
                    'creada_por' => $r->created_by,
                    'notas' => $r->notes,
                    'creada_en' => $r->created_at,
                    'actualizada_en' => $r->updated_at
                ])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar reglas',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF19 - Test 2: Crear nueva regla de comisión
     * POST /api/comisiones/reglas
     * Solo admin
     */
    public function crearRegla(Request $request)
    {
        try {
            $validado = $request->validate([
                'nombre' => 'required|string|max:255',
                'tipo' => 'required|in:base,category,price_range,vendor_type',
                'porcentaje_comision' => 'required|numeric|min:10|max:25',
                'category_id' => 'nullable|string',
                'monto_minimo' => 'nullable|numeric|min:0',
                'monto_maximo' => 'nullable|numeric|min:0',
                'tipo_vendedor' => 'nullable|string',
                'vigencia_desde' => 'nullable|date',
                'vigencia_hasta' => 'nullable|date',
                'activa' => 'boolean',
                'notas' => 'nullable|string'
            ], [
                'porcentaje_comision.min' => 'El porcentaje de comisión debe ser al menos 10%',
                'porcentaje_comision.max' => 'El porcentaje de comisión no puede exceder 25%',
                'porcentaje_comision.required' => 'El porcentaje de comisión es requerido',
                'porcentaje_comision.numeric' => 'El porcentaje debe ser un número',
            ]);

            $rule = $this->commissionService->createRule([
                'name' => $validado['nombre'],
                'type' => $validado['tipo'],
                'commission_percentage' => $validado['porcentaje_comision'],
                'category_id' => $validado['category_id'] ?? null,
                'min_amount' => $validado['monto_minimo'] ?? null,
                'max_amount' => $validado['monto_maximo'] ?? null,
                'vendor_type' => $validado['tipo_vendedor'] ?? null,
                'valid_from' => $validado['vigencia_desde'] ?? null,
                'valid_until' => $validado['vigencia_hasta'] ?? null,
                'is_active' => $validado['activa'] ?? true,
                'notes' => $validado['notas'] ?? null
            ], Auth::user()->email);

            return response()->json([
                'exito' => true,
                'mensaje' => 'Regla de comisión creada exitosamente',
                'datos' => [
                    'id' => $rule->id,
                    'nombre' => $rule->name,
                    'tipo' => $rule->type,
                    'porcentaje' => $rule->commission_percentage . '%'
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear regla',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF19 - Test 3: Actualizar regla de comisión
     * PUT /api/comisiones/reglas/{rule_id}
     * Solo admin
     */
    public function actualizarRegla(Request $request, $ruleId)
    {
        try {
            $rule = CommissionRule::findOrFail($ruleId);

            $validado = $request->validate([
                'nombre' => 'string|max:255',
                'porcentaje_comision' => 'numeric|min:10|max:25',
                'vigencia_desde' => 'nullable|date',
                'vigencia_hasta' => 'nullable|date',
                'activa' => 'boolean',
                'notas' => 'nullable|string'
            ], [
                'porcentaje_comision.min' => 'El porcentaje de comisión debe ser al menos 10%',
                'porcentaje_comision.max' => 'El porcentaje de comisión no puede exceder 25%',
                'porcentaje_comision.numeric' => 'El porcentaje debe ser un número',
            ]);

            $data = [];
            if (isset($validado['nombre'])) $data['name'] = $validado['nombre'];
            if (isset($validado['porcentaje_comision'])) $data['commission_percentage'] = $validado['porcentaje_comision'];
            if (isset($validado['vigencia_desde'])) $data['valid_from'] = $validado['vigencia_desde'];
            if (isset($validado['vigencia_hasta'])) $data['valid_until'] = $validado['vigencia_hasta'];
            if (isset($validado['activa'])) $data['is_active'] = $validado['activa'];
            if (isset($validado['notas'])) $data['notes'] = $validado['notas'];

            $rule = $this->commissionService->updateRule($rule, $data, Auth::user()->email);

            return response()->json([
                'exito' => true,
                'mensaje' => 'Regla actualizada exitosamente',
                'datos' => [
                    'id' => $rule->id,
                    'nombre' => $rule->name,
                    'tipo' => $rule->type,
                    'porcentaje' => $rule->commission_percentage . '%',
                    'actualizada_en' => $rule->updated_at
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar regla',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF19 - Test 4: Eliminar regla de comisión (soft delete)
     * DELETE /api/comisiones/reglas/{rule_id}
     * Solo admin
     */
    public function eliminarRegla($ruleId)
    {
        try {
            $rule = CommissionRule::findOrFail($ruleId);

            $oldData = $rule->toArray();
            $rule->update(['is_active' => false]);

            CommissionAuditLog::log(
                'deleted',
                $rule->id,
                $oldData,
                $rule->toArray(),
                Auth::user()->email,
                request()->ip(),
                'Regla de comisión desactivada'
            );

            return response()->json([
                'exito' => true,
                'mensaje' => 'Regla desactivada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar regla',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF18 - Test 3: Obtener reporte de comisiones
     * GET /api/comisiones/reporte
     * Solo admin
     */
    public function reporteComisiones(Request $request)
    {
        try {
            $validado = $request->validate([
                'desde' => 'required|date',
                'hasta' => 'required|date'
            ]);

            $reporte = $this->commissionService->getCommissionReport(
                $validado['desde'],
                $validado['hasta']
            );

            return response()->json([
                'exito' => true,
                'reporte' => [
                    'periodo' => $reporte['periodo'],
                    'resumen' => [
                        'total_pedidos' => $reporte['total_pedidos'],
                        'monto_total_pedidos' => 'S/' . number_format($reporte['monto_total_pedidos'], 2),
                        'comisiones_totales' => 'S/' . number_format($reporte['comisiones_totales'], 2),
                        'monto_neto_vendedores' => 'S/' . number_format($reporte['monto_neto_vendedores'], 2)
                    ],
                    'por_estado' => $reporte['por_estado'],
                    'por_regla' => $reporte['por_regla']->map(fn ($item) => [
                        'cantidad' => $item['cantidad'],
                        'comision_total' => 'S/' . number_format($item['comision_total'], 2),
                        'porcentaje' => $item['porcentaje'] . '%',
                        'monto_neto' => 'S/' . number_format($item['monto_neto'], 2)
                    ])
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar reporte',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF18 - Test 4: Listar auditoría de cambios en comisiones
     * GET /api/comisiones/auditoria
     * Solo admin
     */
    public function auditoria(Request $request)
    {
        try {
            $query = CommissionAuditLog::query();

            if ($request->has('desde')) {
                $query->whereDate('created_at', '>=', $request->input('desde'));
            }

            if ($request->has('hasta')) {
                $query->whereDate('created_at', '<=', $request->input('hasta'));
            }

            if ($request->has('accion')) {
                $query->where('action', $request->input('accion'));
            }

            $logs = $query->orderBy('created_at', 'desc')->paginate(50);

            return response()->json([
                'exito' => true,
                'total' => $logs->total(),
                'datos' => $logs->items()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener auditoría',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF20: Transferir comisiones pendientes a vendedores
     * POST /api/comisiones/transferir
     * Solo admin
     * Opcional: puede recibir vendor_id para transferir solo a un vendedor
     */
    public function transferirComisiones(Request $request)
    {
        $request->validate([
            'vendor_id' => 'nullable|integer|exists:users,id',
        ]);
        $query = OrderCommission::select('order_commissions.*')
            ->join('orders', 'order_commissions.order_id', '=', 'orders.id')
            ->join('food_stalls', 'orders.stall_id', '=', 'food_stalls.id')
            ->where('order_commissions.status', 'pending');
        if ($request->filled('vendor_id')) {
            $vendorId = $request->input('vendor_id');
            $query->where('food_stalls.seller_id', $vendorId);
        }
        $total = (clone $query)->count();

        if ($total === 0) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'No hay comisiones pendientes para transferir.'
            ], 200);
        }

        $method = $request->input('method', 'mock');
        $query->chunk(100, function ($comisiones) use ($method) {
            foreach ($comisiones as $comision) {
                ProcessCommissionTransfer::dispatch($comision, $method);
            }
        });

        return response()->json([
            'exito' => true,
            'mensaje' => "Transferencia iniciada para {$total} comisiones. Los resultados se procesarán en segundo plano.",
            'total_procesadas' => $total
        ], 200);
    }
}
