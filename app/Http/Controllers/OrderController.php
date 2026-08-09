<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\MenuItem;
use App\Models\FoodStall;
use App\Models\Notification;
use App\Jobs\ProcessPaymentJob;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * RF11 - MÉTODO 1: Crear pedido (SIN PAGO)
     * POST /api/pedidos
     * Crea carrito/borrador de pedido
     */
    public function crearPedido(Request $request)
    {
        try {
            $usuario = Auth::user();

            $validado = $request->validate([
                'stall_id' => 'required|exists:food_stalls,id',
                'productos' => 'required|array|min:1',
                'productos.*.producto_id' => 'required|exists:menu_items,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.cremas' => 'nullable|array', // IDs de cremas opcionales
                'direccion' => 'required|string|max:255',
                'with_delivery' => 'nullable|boolean',
                'delivery_latitude' => 'nullable|numeric',
                'delivery_longitude' => 'nullable|numeric',
                'notas' => 'nullable|string|max:500',
                // Invoice fields (RF21)
                'invoice_requested' => 'nullable|boolean',
                'invoice_type' => 'nullable|in:boleta,factura',
                'customer_document_type' => 'nullable|in:DNI,RUC',
                'customer_document_number' => 'nullable|string|max:20',
                'customer_name' => 'nullable|string|max:255'
            ], [
                'stall_id.required' => 'El puesto es obligatorio',
                'productos.required' => 'Debes agregar al menos un producto',
                'direccion.required' => 'La dirección de entrega es obligatoria'
            ]);

            // Verificar que el puesto está abierto
            $puesto = FoodStall::findOrFail($validado['stall_id']);
            
            if (!$puesto->isOpenNow()) {
                return response()->json([
                    'error' => 'El puesto no está abierto en este momento',
                    'horario_apertura' => $puesto->opening_time,
                    'horario_cierre' => $puesto->closing_time
                ], 409);
            }

            if ($puesto->isInPauseNow()) {
                return response()->json([
                    'error' => 'El puesto está en pausa temporal'
                ], 409);
            }

            $subtotal = 0;
            $items = [];

            // Batch loading: cargar todos los productos y sus toppings en 2 queries
            $productIds = array_column($validado['productos'], 'producto_id');
            $productos = MenuItem::whereIn('id', $productIds)->with('toppings')->get()->keyBy('id');
            $toppingsPorProducto = $productos->mapWithKeys(function ($p) {
                return [$p->id => $p->toppings->keyBy('id')];
            });

            foreach ($validado['productos'] as $prod) {
                $producto = $productos->get($prod['producto_id']);

                if (!$producto || !$producto->active) {
                    return response()->json([
                        'error' => "El producto '{$producto->name}' no está disponible"
                    ], 409);
                }

                $cantidad = $prod['cantidad'];
                $precio_unitario = $producto->price;
                $item_subtotal = $cantidad * $precio_unitario;
                $subtotal += $item_subtotal;

                // Procesar cremas seleccionadas desde colección en memoria
                $toppingsCosto = 0;
                $cremasSolicitadas = [];
                $toppingsDelProducto = $toppingsPorProducto->get($producto->id, collect());
                if (isset($prod['cremas']) && is_array($prod['cremas'])) {
                    foreach ($prod['cremas'] as $cremaId) {
                        $crema = $toppingsDelProducto->get($cremaId);
                        if ($crema) {
                            $toppingsCosto += $crema->price * $cantidad;
                            $cremasSolicitadas[] = [
                                'id' => $crema->id,
                                'nombre' => $crema->name,
                                'precio' => $crema->price
                            ];
                        }
                    }
                }

                $subtotal += $toppingsCosto;

                $items[] = [
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio_unitario,
                    'subtotal' => $item_subtotal + $toppingsCosto,
                    'cremas' => $cremasSolicitadas
                ];
            }

            // Calcular IGV (18%) solo para factura
            $igv = (!empty($validado['invoice_requested']) && ($validado['invoice_type'] ?? '') === 'factura')
                   ? $subtotal * 0.18
                   : 0;

            // Calcular delivery: si se indica with_delivery intentamos calcular distancia
            $delivery_cost = 0;
            $delivery_distance = null;
            $deliveryLat = $validado['delivery_latitude'] ?? null;
            $deliveryLng = $validado['delivery_longitude'] ?? null;
            $withDelivery = $validado['with_delivery'] ?? false;

            if ($withDelivery) {
                $deliveryService = new DeliveryService();
                $calc = $deliveryService->calculateDeliveryCost($puesto, $deliveryLat, $deliveryLng);
                $delivery_cost = $calc['cost'] ?? 0;
                $delivery_distance = $calc['distance_km'] ?? null;
            }

            // Total final
            $total = $subtotal + $igv + $delivery_cost;

            // RF13: Calcular tiempo estimado de entrega
            $totalUnidades = array_sum(array_column($items, 'cantidad'));
            $tiempoEstimado = $this->calcularTiempoEstimado(
                $puesto,
                $totalUnidades,
                ($validado['with_delivery'] ?? false)
            );

            // Validar coherencia de comprobante: si solicita factura debe tener RUC
            if (!empty($validado['invoice_requested']) && ($validado['invoice_type'] ?? '') === 'factura') {
                if (empty($validado['customer_document_type']) || strtoupper($validado['customer_document_type']) !== 'RUC') {
                    return response()->json(['error' => 'Para emitir factura se requiere tipo de documento RUC y número'], 422);
                }
            }

            // Crear orden en estado PENDING (sin pagar)
            $orden = Order::create([
                'user_id' => $usuario->id,
                'stall_id' => $validado['stall_id'],
                'status' => 'pending', // ← Pendiente de pago
                'subtotal' => $subtotal,
                'igv' => $igv,
                'delivery_cost' => $delivery_cost,
                'with_delivery' => $withDelivery,
                'delivery_latitude' => $deliveryLat,
                'delivery_longitude' => $deliveryLng,
                'delivery_distance_km' => $delivery_distance,
                'total' => $total,
                'payment_method' => null, // Se define en el pago
                'delivery_address' => $validado['direccion'],
                'client_notes' => $validado['notas'] ?? null,
                // RF21 invoice fields
                'invoice_requested' => $validado['invoice_requested'] ?? false,
                'invoice_type' => $validado['invoice_type'] ?? null,
                'customer_document_type' => $validado['customer_document_type'] ?? null,
                'customer_document_number' => $validado['customer_document_number'] ?? null,
                'customer_name' => $validado['customer_name'] ?? ($usuario->name ?? null),
                'estimated_delivery_at' => $tiempoEstimado
            ]);

            // Crear items del pedido
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $orden->id,
                    'product_id' => $item['producto_id'],
                    'quantity' => $item['cantidad'],
                    'price_per_unit' => $item['precio_unitario'],
                    'subtotal' => $item['subtotal'],
                    'toppings' => $item['cremas']
                ]);
            }

            Log::info('Pedido creado pendiente de pago', [
                'order_id' => $orden->id,
                'user_id' => $usuario->id,
                'total' => $total
            ]);

            return response()->json([
                'message' => 'Pedido creado. Procede al pago.',
                'orden' => [
                    'id' => $orden->id,
                    'estado' => $orden->status,
                    'puesto_nombre' => $puesto->name,
                    'subtotal' => $subtotal,
                    'igv' => $igv,
                    'delivery' => $delivery_cost,
                    'total' => $total,
                    'direccion_entrega' => $validado['direccion']
                ],
                'items_pedido' => $items,
                'siguiente_paso' => 'POST /api/pedidos/' . $orden->id . '/pagar'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Errores de validación',
                'errores' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al crear pedido: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al crear pedido',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * RF11 - MÉTODO 1B: Procesar pago de un pedido (ASYNC con Queue)
     * POST /api/pedidos/{id}/pagar
     */
    public function pagarPedido(Request $request, $id)
    {
        try {
            $usuario = Auth::user();

            $validado = $request->validate([
                'metodo_pago' => 'required|in:mock,yape,plin'
            ], [
                'metodo_pago.required' => 'El método de pago es obligatorio'
            ]);

            $orden = Order::with('stall')->findOrFail($id);

            // Verificar que el usuario es el dueño del pedido
            if ($orden->user_id !== $usuario->id) {
                return response()->json([
                    'error' => 'No tienes permiso para pagar este pedido'
                ], 403);
            }

            // Verificar que el pedido está pendiente
            if ($orden->status !== 'pending') {
                return response()->json([
                    'error' => 'Este pedido ya fue pagado o cancelado',
                    'estado_actual' => $orden->status
                ], 409);
            }

            // Guardar el método de pago en la orden
            $orden->update([
                'payment_method' => $validado['metodo_pago']
            ]);

            // FASE 2: Enviar procesamiento a queue (async)
            // El pago se procesará en background, no bloqueará la respuesta
            ProcessPaymentJob::dispatch($orden, $validado['metodo_pago']);

            Log::info('Pago enviado a queue para procesamiento', [
                'order_id' => $orden->id,
                'user_id' => $usuario->id,
                'metodo_pago' => $validado['metodo_pago'],
                'total' => $orden->total
            ]);

            // Respuesta inmediata sin esperar a que procese
            return response()->json([
                'message' => 'Pago en procesamiento',
                'orden' => [
                    'id' => $orden->id,
                    'estado' => 'pending', // Aún pendiente hasta que el job procese
                    'estado_pago' => 'procesando',
                    'puesto_nombre' => $orden->stall->name,
                    'total' => $orden->total,
                    'metodo_pago' => $validado['metodo_pago'],
                    'nota' => 'El pago se está procesando. Verifica el estado en GET /api/pedidos/' . $orden->id
                ]
            ], 202); // 202 Accepted - procesamiento en background

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Errores de validación',
                'errores' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error al enviar pago a queue: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al procesar pago',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar pago según método (Mock, Yape, Plin)
     */
    private function procesarPago($orden, $metodo)
    {
        if ($metodo === 'mock') {
            // Simular pago exitoso
            $paymentId = 'MOCK-' . strtoupper(Str::random(4)) . '-' . time();

            Payment::create([
                'order_id' => $orden->id,
                'method' => 'mock',
                'amount' => $orden->total,
                'status' => 'completed',
                'transaction_id' => $paymentId,
                'response' => [
                    'simulator' => true,
                    'message' => 'Pago simulado exitosamente'
                ]
            ]);

            return [
                'status' => 'completed',
                'payment_id' => $paymentId
            ];
        }

        // Para Yape/Plin: retornar pendiente (requeriría integración real)
        return [
            'status' => 'pending',
            'payment_id' => null,
            'error' => 'Integración de ' . $metodo . ' aún no disponible'
        ];
    }

    /**
     * RF12 - MÉTODO 2: Obtener detalle de un pedido
     * GET /api/pedidos/{id}
     */
    public function obtenerPedido($id)
    {
        try {
            $usuario = Auth::user();

            $orden = Order::with(['items.product', 'stall', 'payment', 'client'])
                         ->findOrFail($id);

            // Verificar que el usuario sea el cliente o el vendedor del puesto
            if ($orden->user_id !== $usuario->id && $orden->stall->seller_id !== $usuario->id) {
                return response()->json([
                    'error' => 'No tienes permiso para ver este pedido'
                ], 403);
            }

            $items = $orden->items->map(function ($item) {
                return [
                    'producto' => $item->product->name,
                    'cantidad' => $item->quantity,
                    'precio_unitario' => $item->price_per_unit,
                    'subtotal' => $item->subtotal,
                    'cremas' => $item->toppings ?? []
                ];
            });

            // Construir timeline de estados
            $timeline = [];
            if ($orden->created_at) {
                $timeline['pending'] = [
                    'estado' => 'pending',
                    'descripcion' => 'Pedido creado',
                    'fecha' => $orden->created_at
                ];
            }
            if ($orden->confirmed_at) {
                $timeline['confirmed'] = [
                    'estado' => 'confirmed',
                    'descripcion' => 'Pago confirmado',
                    'fecha' => $orden->confirmed_at
                ];
            }
            if ($orden->preparing_at) {
                $timeline['preparing'] = [
                    'estado' => 'preparing',
                    'descripcion' => 'En preparación',
                    'fecha' => $orden->preparing_at
                ];
            }
            if ($orden->ready_at) {
                $timeline['ready'] = [
                    'estado' => 'ready',
                    'descripcion' => 'Listo para entrega/recojo',
                    'fecha' => $orden->ready_at
                ];
            }
            if ($orden->en_camino_at) {
                $timeline['en_camino'] = [
                    'estado' => 'en_camino',
                    'descripcion' => 'En camino de entrega',
                    'fecha' => $orden->en_camino_at
                ];
            }
            if ($orden->delivered_at) {
                $timeline['delivered'] = [
                    'estado' => 'delivered',
                    'descripcion' => 'Entregado',
                    'fecha' => $orden->delivered_at
                ];
            }

            return response()->json([
                'pedido' => [
                    'id' => $orden->id,
                    'estado' => $orden->status,
                    'puesto_nombre' => $orden->stall->name,
                    'cliente_nombre' => $orden->client->name,
                    'direccion_entrega' => $orden->delivery_address,
                    'subtotal' => $orden->subtotal,
                    'igv' => $orden->igv,
                    'delivery' => $orden->delivery_cost,
                    'total' => $orden->total,
                    'metodo_pago' => $orden->payment_method,
                    'notas' => $orden->client_notes,
                    'created_at' => $orden->created_at,
                    'estimated_delivery_at' => $orden->estimated_delivery_at
                ],
                'items' => $items,
                'pago' => $orden->payment ? [
                    'metodo' => $orden->payment->method,
                    'estado' => $orden->payment->status,
                    'transaction_id' => $orden->payment->transaction_id
                ] : null,
                'timeline' => array_values($timeline) // Retorna array ordenado
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener pedido: ' . $e->getMessage());
            return response()->json([
                'error' => 'Pedido no encontrado'
            ], 404);
        }
    }

    /**
     * RF12 - MÉTODO 3: Listar pedidos del cliente
     * GET /api/pedidos
     */
    public function listarPedidosCliente(Request $request)
    {
        try {
            $usuario = Auth::user();

            $pedidos = Order::where('user_id', $usuario->id)
                           ->with('stall')
                           ->withCount('items')
                           ->orderBy('created_at', 'desc')
                           ->paginate(10);

            $pedidosFormato = $pedidos->getCollection()->map(function ($orden) {
                return [
                    'id' => $orden->id,
                    'puesto_nombre' => $orden->stall->name,
                    'estado' => $orden->status,
                    'total' => $orden->total,
                    'cantidad_items' => $orden->items_count,
                    'created_at' => $orden->created_at
                ];
            });

            return response()->json([
                'total' => $pedidos->total(),
                'pagina' => $pedidos->currentPage(),
                'por_pagina' => $pedidos->perPage(),
                'pedidos' => $pedidosFormato
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al listar pedidos: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al listar pedidos'
            ], 500);
        }
    }

    /**
     * RF17 - MÉTODO 4: Listar pedidos activos del vendedor
     * GET /api/mis-pedidos
     */
    public function listarMisPedidos(Request $request)
    {
        try {
            $usuario = Auth::user();

            $puesto = FoodStall::where('seller_id', $usuario->id)->first();

            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado'
                ], 404);
            }

            $pedidos = Order::where('stall_id', $puesto->id)
                           ->whereIn('status', ['confirmed', 'preparing', 'ready'])
                           ->with('client')
                           ->withCount('items')
                           ->orderBy('created_at', 'asc')
                           ->paginate(20);

            $pedidosFormato = collect($pedidos->items())->map(function ($orden) {
                return [
                    'id' => $orden->id,
                    'cliente_nombre' => $orden->client->name,
                    'cliente_telefono' => $orden->client->phone,
                    'estado' => $orden->status,
                    'total' => $orden->total,
                    'items_count' => $orden->items_count,
                    'direccion_entrega' => $orden->delivery_address,
                    'notas' => $orden->client_notes,
                    'created_at' => $orden->created_at,
                    'estimated_delivery_at' => $orden->estimated_delivery_at
                ];
            });

            return response()->json([
                'puesto_nombre' => $puesto->name,
                'pedidos_activos' => $pedidos->total(),
                'pagina' => $pedidos->currentPage(),
                'por_pagina' => $pedidos->perPage(),
                'total' => $pedidos->total(),
                'pedidos' => $pedidosFormato
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al listar mis pedidos: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al listar pedidos'
            ], 500);
        }
    }

    /**
     * RF14 - Cambiar estado del pedido (SOLO VENDEDOR)
     * PATCH /api/pedidos/{id}/cambiar-estado
     * Estados: confirmed → preparing → ready → en_camino → delivered
     */
    public function cambiarEstado(Request $request, $orderId)
    {
        try {
            $usuario = Auth::user();
            
            // Solo vendedores pueden cambiar estados
            if ($usuario->role !== 'vendor') {
                return response()->json([
                    'error' => 'Solo vendedores pueden cambiar estados de pedidos'
                ], 403);
            }

            $validado = $request->validate([
                'nuevo_estado' => 'required|in:confirmed,preparing,ready,en_camino,delivered,cancelled'
            ], [
                'nuevo_estado.required' => 'Debe indicar el nuevo estado',
                'nuevo_estado.in' => 'Estado no válido. Estados permitidos: confirmed, preparing, ready, en_camino, delivered, cancelled'
            ]);

            $orden = Order::with('stall')->findOrFail($orderId);

            // Verificar que el vendedor es dueño del puesto
            $puesto = $orden->stall;
            if ($puesto->seller_id !== $usuario->id) {
                return response()->json([
                    'error' => 'No tienes permiso para cambiar el estado de este pedido'
                ], 403);
            }

            $nuevoEstado = $validado['nuevo_estado'];
            $estadoActual = $orden->status;

            // Validar transiciones permitidas
            $transicionesValidas = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['preparing', 'cancelled'],
                'preparing' => ['ready', 'confirmed', 'cancelled'],
                'ready' => ['en_camino', 'delivered', 'cancelled'],
                'en_camino' => ['delivered', 'cancelled'],
                'delivered' => [],
                'cancelled' => []
            ];

            if (!isset($transicionesValidas[$estadoActual]) || 
                !in_array($nuevoEstado, $transicionesValidas[$estadoActual])) {
                return response()->json([
                    'error' => "No se puede pasar de '{$estadoActual}' a '{$nuevoEstado}'",
                    'estado_actual' => $estadoActual,
                    'transiciones_permitidas' => $transicionesValidas[$estadoActual] ?? []
                ], 409);
            }

            // Actualizar estado y timestamp correspondiente
            $orden->status = $nuevoEstado;
            
            // Registrar timestamp según el nuevo estado
            $timestampField = $nuevoEstado . '_at';
            if (in_array($timestampField, ['confirmed_at', 'preparing_at', 'ready_at', 'en_camino_at', 'delivered_at'])) {
                $orden->{$timestampField} = now();
            }

            $orden->save();

            $this->crearNotificacionPedido($orden, $nuevoEstado);

            Log::info("Pedido #{$orderId} cambió de '{$estadoActual}' a '{$nuevoEstado}' por vendedor {$usuario->id}");

            return response()->json([
                'message' => 'Estado actualizado correctamente',
                'orden_id' => $orden->id,
                'estado_anterior' => $estadoActual,
                'estado_nuevo' => $nuevoEstado,
                'actualizado_en' => now(),
                'orden' => $this->formatearOrden($orden)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del pedido: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al cambiar estado del pedido',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método auxiliar para formatear orden con timeline de estados
     */
    private function formatearOrden($orden)
    {
        return [
            'id' => $orden->id,
            'user_id' => $orden->user_id,
            'stall_id' => $orden->stall_id,
            'status' => $orden->status,
            'subtotal' => $orden->subtotal,
            'igv' => $orden->igv,
            'delivery_cost' => $orden->delivery_cost,
            'total' => $orden->total,
            'direccion' => $orden->delivery_address,
            'notas' => $orden->client_notes,
            'timeline' => [
                'pending' => $orden->created_at,
                'confirmed' => $orden->confirmed_at,
                'preparing' => $orden->preparing_at,
                'ready' => $orden->ready_at,
                'en_camino' => $orden->en_camino_at,
                'delivered' => $orden->delivered_at
            ]
        ];
    }

    /**
     * RF13 - Calcular tiempo estimado de entrega
     * Basado en: carga del puesto, cantidad de unidades, si hay delivery
     */
    private function calcularTiempoEstimado($puesto, $totalUnidades, $tieneDelivery)
    {
        // Obtener órdenes activas (confirmed, preparing, ready) con caché de 30s
        $ordenesActivas = Cache::remember("stall.{$puesto->id}.active_orders_count", 30, function () use ($puesto) {
            return Order::where('stall_id', $puesto->id)
                ->whereIn('status', ['confirmed', 'preparing', 'ready'])
                ->count();
        });

        // Fórmula de cálculo:
        $tiempo_minutos = $puesto->base_preparation_time          // Base: 15 min
                        + ($totalUnidades * $puesto->time_per_product)  // +5 min por unidad
                        + ($ordenesActivas * $puesto->time_per_active_order) // +5 min por pedido activo
                        + ($tieneDelivery ? $puesto->time_for_delivery : 0); // +10 min si delivery

        // Retornar timestamp de cuando estaría listo
        return now()->addMinutes($tiempo_minutos);
    }

    /**
     * RF15 - Cancelar pedido
     * PATCH /api/pedidos/{id}/cancelar
     * Solo cliente puede cancelar, solo en estado pending
     */
    public function cancelarPedido(Request $request, $orderId)
    {
        try {
            $usuario = Auth::user();

            $orden = Order::findOrFail($orderId);

            // Verificar que el cliente es el dueño del pedido
            if ($orden->user_id !== $usuario->id) {
                return response()->json([
                    'error' => 'No tienes permiso para cancelar este pedido'
                ], 403);
            }

            // Solo se puede cancelar si está pendiente de pago
            if ($orden->status !== 'pending') {
                return response()->json([
                    'error' => "No se puede cancelar un pedido en estado '{$orden->status}'",
                    'estado_actual' => $orden->status,
                    'razon' => 'Solo se pueden cancelar pedidos pendientes de pago'
                ], 409);
            }

            $estadoAnterior = $orden->status;
            $orden->status = 'cancelled';
            $orden->save();

            $this->crearNotificacionPedido($orden, 'cancelled', $orden->stall->seller_id);

            Log::info("Pedido #{$orderId} cancelado por cliente {$usuario->id}", [
                'estado_anterior' => $estadoAnterior,
                'motivo' => $request->input('motivo', 'No especificado')
            ]);

            return response()->json([
                'message' => 'Pedido cancelado exitosamente',
                'orden_id' => $orden->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'cancelled',
                'total_cancelado' => $orden->total,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al cancelar pedido: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al cancelar pedido'
            ], 500);
        }
    }

    /**
     * RF16 - Cambiar dirección de entrega del pedido
     * PATCH /api/pedidos/{id}/cambiar-direccion
     * Solo cliente puede cambiar, solo en estados: pending, confirmed
     */
    public function cambiarDireccion(Request $request, $orderId)
    {
        try {
            $usuario = Auth::user();

            $validado = $request->validate([
                'nueva_direccion' => 'required|string|max:255|min:10'
            ], [
                'nueva_direccion.required' => 'Debe proporcionar una nueva dirección',
                'nueva_direccion.max' => 'La dirección no puede exceder 255 caracteres',
                'nueva_direccion.min' => 'La dirección debe tener al menos 10 caracteres'
            ]);

            $orden = Order::findOrFail($orderId);

            // Verificar que el cliente es el dueño del pedido
            if ($orden->user_id !== $usuario->id) {
                return response()->json([
                    'error' => 'No tienes permiso para cambiar la dirección de este pedido'
                ], 403);
            }

            // Solo se puede cambiar dirección si está en pending o confirmed
            // Si ya está en preparación, no se puede cambiar
            if (in_array($orden->status, ['preparing', 'ready', 'en_camino', 'delivered', 'cancelled'])) {
                return response()->json([
                    'error' => "No se puede cambiar la dirección de un pedido en estado '{$orden->status}'",
                    'estado_actual' => $orden->status,
                    'razon' => 'El vendedor ya comenzó a preparar o el pedido está en camino'
                ], 409);
            }

            // Guardar dirección anterior para auditoría
            $direccionAnterior = $orden->delivery_address;

            // Actualizar dirección
            $orden->delivery_address = $validado['nueva_direccion'];
            $orden->save();

            Log::info("Dirección cambada para pedido #{$orderId} por cliente {$usuario->id}", [
                'direccion_anterior' => $direccionAnterior,
                'direccion_nueva' => $validado['nueva_direccion']
            ]);

            return response()->json([
                'message' => 'Dirección actualizada exitosamente',
                'orden_id' => $orden->id,
                'direccion_anterior' => $direccionAnterior,
                'direccion_nueva' => $validado['nueva_direccion'],
                'estado_pedido' => $orden->status
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al cambiar dirección del pedido: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al cambiar dirección del pedido'
            ], 500);
        }
    }

    private function crearNotificacionPedido($orden, $tipo, $usuarioId = null)
    {
        $mensajes = [
            'confirmed' => [
                'title' => 'Nuevo pedido confirmado',
                'body' => "Pedido #{$orden->id} de {$orden->client->name} por S/ {$orden->total}",
            ],
            'preparing' => [
                'title' => 'Pedido en preparación',
                'body' => "Tu pedido #{$orden->id} en {$orden->stall->name} ya se está preparando",
            ],
            'ready' => [
                'title' => 'Pedido listo',
                'body' => "Tu pedido #{$orden->id} de {$orden->stall->name} está listo",
            ],
            'en_camino' => [
                'title' => 'Pedido en camino',
                'body' => "Tu pedido #{$orden->id} de {$orden->stall->name} va en camino",
            ],
            'delivered' => [
                'title' => 'Pedido entregado',
                'body' => "Pedido #{$orden->id} de {$orden->stall->name} entregado — ¡Buen provecho!",
            ],
            'cancelled' => [
                'title' => 'Pedido cancelado',
                'body' => "Pedido #{$orden->id} de {$orden->stall->name} fue cancelado",
            ],
        ];

        $info = $mensajes[$tipo] ?? ['title' => 'Pedido actualizado', 'body' => "Pedido #{$orden->id}"];
        $destinatarioId = $usuarioId ?? $orden->user_id;

        Notification::create([
            'user_id' => $destinatarioId,
            'type' => "order_{$tipo}",
            'title' => $info['title'],
            'body' => $info['body'],
            'notifiable_id' => $orden->id,
            'notifiable_type' => Order::class,
        ]);

        Cache::forget("user.{$destinatarioId}.notificaciones_no_leidas");
    }
}



