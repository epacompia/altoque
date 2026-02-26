<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\FoodStall;
// ...existing code...
use App\Models\Topping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    /**
     * RF9: Descargar menú offline para el vendedor (solo productos activos)
     * GET /api/mis-productos/offline
     */
    public function descargarMenuOffline(Request $request)
    {
        try {
            $usuario = Auth::user();
            $puesto = FoodStall::where('seller_id', $usuario->id)->first();
            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado'
                ], 404);
            }
            $productos = MenuItem::where('stall_id', $puesto->id)
                ->where('active', true)
                ->with(['category', 'toppings' => function($query) {
                    $query->where('active', true);
                }])
                ->orderByRaw('featured DESC, name ASC')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nombre' => $item->name,
                        'descripcion' => $item->description,
                        'precio' => $item->price,
                        'categoria' => $item->category?->name,
                        'categoria_id' => $item->category_id,
                        'destacado' => $item->featured,
                        'imagen' => $item->image_path,
                        'cremas' => $item->toppings->map(function($topping) {
                            return [
                                'id' => $topping->id,
                                'nombre' => $topping->name,
                                'precio_adicional' => $topping->price,
                                'requerida' => $topping->pivot->required ?? false
                            ];
                        })->values()
                    ];
                });
            return response()->json([
                'puesto_id' => $puesto->id,
                'puesto_nombre' => $puesto->name,
                'productos' => $productos
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al descargar menú offline: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al descargar menú offline',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }
    // MÉTODO 1: Ver todos los productos del vendedor (ACTUALIZADO CON CREMAS)
    public function obtenerMisProductos(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            // Obtener puesto del vendedor
            $puesto = FoodStall::where('seller_id', $usuario->id)->first();
            
            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado'
                ], 404);
            }
            
            // Obtener todos los productos (activos e inactivos) CON CREMAS
            $productos = MenuItem::where('stall_id', $puesto->id)
                                  ->with(['category', 'toppings' => function($query) {
                                      $query->where('active', true);
                                  }])
                                  ->orderByRaw('featured DESC, name ASC')
                                  ->get()
                                  ->map(function ($item) {
                                      return [
                                          'id' => $item->id,
                                          'nombre' => $item->name,
                                          'descripcion' => $item->description,
                                          'precio' => $item->price,
                                          'categoria' => $item->category?->name,
                                          'categoria_id' => $item->category_id,
                                          'activo' => $item->active,
                                          'destacado' => $item->featured,
                                          'imagen' => $item->image_path,
                                          'creado_en' => $item->created_at,
                                          'cremas' => $item->toppings->map(function($topping) {
                                              return [
                                                  'id' => $topping->id,
                                                  'nombre' => $topping->name,
                                                  'precio_adicional' => $topping->price,
                                                  'requerida' => $topping->pivot->required ?? false
                                              ];
                                          })->values()
                                      ];
                                  });
            
            return response()->json([
                'puesto_id' => $puesto->id,
                'puesto_nombre' => $puesto->name,
                'total_productos' => $productos->count(),
                'productos' => $productos
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener productos: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener productos',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    // MÉTODO 2: Crear nuevo producto (ACTUALIZADO CON IMAGEN)
    public function crearProducto(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            // Validar datos
            $validado = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string|max:1000',
                'precio' => 'required|numeric|min:0.01',
                'categoria_id' => 'required|exists:categories,id',
                'activo' => 'nullable|boolean',
                'destacado' => 'nullable|boolean',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ], [
                'nombre.required' => 'El nombre del producto es obligatorio',
                'precio.required' => 'El precio es obligatorio',
                'precio.numeric' => 'El precio debe ser un número',
                'categoria_id.required' => 'La categoría es obligatoria',
                'categoria_id.exists' => 'La categoría seleccionada no existe',
                'imagen.image' => 'El archivo debe ser una imagen válida',
                'imagen.max' => 'La imagen no debe exceder 2MB'
            ]);
            
            // Obtener puesto del vendedor
            $puesto = FoodStall::where('seller_id', $usuario->id)->first();
            
            if (!$puesto) {
                return response()->json([
                    'error' => 'No tienes un puesto registrado'
                ], 404);
            }
            
            // Procesar imagen si existe
            $imagenPath = null;
            if ($request->hasFile('imagen')) {
                $imagen = $request->file('imagen');
                $nombreImagen = 'productos/' . $puesto->id . '_' . time() . '.' . $imagen->getClientOriginalExtension();
                $imagenPath = $imagen->storeAs('/', $nombreImagen, 'public');
            }
            
            // Crear producto
            $producto = MenuItem::create([
                'stall_id' => $puesto->id,
                'name' => $validado['nombre'],
                'description' => $validado['descripcion'],
                'price' => $validado['precio'],
                'category_id' => $validado['categoria_id'],
                'active' => $validado['activo'] ?? true,
                'featured' => $validado['destacado'] ?? false,
                'image_path' => $imagenPath
            ]);
            
            // Limpiar caché del menú
            Cache::forget("menu_stall_{$puesto->id}");
            
            Log::info('Producto creado', [
                'producto_id' => $producto->id,
                'stall_id' => $puesto->id,
                'usuario_id' => $usuario->id
            ]);
            
            return response()->json([
                'message' => 'Producto creado exitosamente',
                'producto' => [
                    'id' => $producto->id,
                    'nombre' => $producto->name,
                    'precio' => $producto->price,
                    'activo' => $producto->active,
                    'destacado' => $producto->featured,
                    'imagen' => $imagenPath ? Storage::disk('public')->url($imagenPath) : null
                ]
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Errores de validación',
                'errores' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error al crear producto: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al crear producto',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    // MÉTODO 3: Actualizar producto (activar/desactivar/destacar)
    public function actualizarProducto(Request $request, $id)
    {
        try {
            $usuario = Auth::user();
            
            // Validar datos
            $validado = $request->validate([
                'nombre' => 'nullable|string|max:255',
                'descripcion' => 'nullable|string|max:1000',
                'precio' => 'nullable|numeric|min:0.01',
                'categoria_id' => 'nullable|exists:categories,id',
                'activo' => 'nullable|boolean',
                'destacado' => 'nullable|boolean'
            ]);
            
            // Obtener producto
            $producto = MenuItem::with('stall')->findOrFail($id);
            
            // Verificar que sea dueño del puesto
            if ($producto->stall->seller_id !== $usuario->id) {
                return response()->json([
                    'error' => 'No tienes permiso para editar este producto'
                ], 403);
            }
            
            // Actualizar producto
            $producto->update([
                'name' => $validado['nombre'] ?? $producto->name,
                'description' => $validado['descripcion'] ?? $producto->description,
                'price' => $validado['precio'] ?? $producto->price,
                'category_id' => $validado['categoria_id'] ?? $producto->category_id,
                'active' => $validado['activo'] ?? $producto->active,
                'featured' => $validado['destacado'] ?? $producto->featured
            ]);
            
            // Limpiar caché
            Cache::forget("menu_stall_{$producto->stall_id}");
            
            Log::info('Producto actualizado', [
                'producto_id' => $id,
                'cambios' => array_filter($validado),
                'usuario_id' => $usuario->id
            ]);
            
            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'producto' => [
                    'id' => $producto->id,
                    'nombre' => $producto->name,
                    'precio' => $producto->price,
                    'activo' => $producto->active,
                    'destacado' => $producto->featured
                ]
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Errores de validación',
                'errores' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar producto',
                'detalles' => $e->getMessage()
            ], 500);
        }
    }

    // MÉTODO 4: Ver menú público (cliente sin login) - ACTUALIZADO CON CREMAS
    public function verMenu(Request $request, $stallId)
    {
        try {
            // Obtener puesto por ID o slug
            $puesto = FoodStall::where('id', $stallId)
                              ->orWhere('slug', $stallId)
                              ->where('active', true)
                              ->firstOrFail();
            
            // Usar caché para optimizar
            $cacheKey = "menu_stall_{$puesto->id}";
            $productos = Cache::remember($cacheKey, 60, function () use ($puesto) {
                return MenuItem::where('stall_id', $puesto->id)
                               ->where('active', true)
                               ->with(['category', 'toppings' => function($query) {
                                   $query->where('active', true);
                               }])
                               ->orderByRaw('featured DESC, name ASC')
                               ->get();
            });
            
            // Mapear respuesta CON CREMAS
            $productosFormato = $productos->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->name,
                    'precio' => $item->price,
                    'descripcion' => $item->description,
                    'categoria' => $item->category?->name,
                    'destacado' => $item->featured,
                    'imagen' => $item->image_path ? Storage::disk('public')->url($item->image_path) : null,
                    'cremas' => $item->toppings->map(function($topping) {
                        return [
                            'id' => $topping->id,
                            'nombre' => $topping->name,
                            'precio_adicional' => $topping->price,
                            'requerida' => $topping->pivot->required ?? false
                        ];
                    })->values()
                ];
            });
            
            // Verificar si hay token y validar autenticación
            $usuarioAutenticado = false;
            if ($request->bearerToken()) {
                $usuario = Auth::guard('sanctum')->user();
                if ($usuario) {
                    $usuarioAutenticado = true;
                }
            }
            
            return response()->json([
                'puesto' => [
                    'id' => $puesto->id,
                    'nombre' => $puesto->name,
                    'slug' => $puesto->slug,
                    'direccion' => $puesto->address,
                    'telefono' => $puesto->phone,
                    'horario_apertura' => $puesto->opening_time,
                    'horario_cierre' => $puesto->closing_time,
                    'activo' => $puesto->active,
                    'descripcion' => $puesto->description
                ],
                'productos' => $productosFormato,
                'total_productos' => $productosFormato->count(),
                'usuario_autenticado' => $usuarioAutenticado
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al ver menú: ' . $e->getMessage());
            return response()->json([
                'error' => 'Puesto no encontrado o no disponible'
            ], 404);
        }
    }

    // ========== RF8: GESTIÓN DE CREMAS/ACOMPAÑAMIENTOS ==========

    // MÉTODO 5: Obtener todas las cremas del vendedor
    public function obtenerCremas(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            $puesto = FoodStall::where('seller_id', $usuario->id)->first();
            if (!$puesto) {
                return response()->json(['error' => 'No tienes un puesto registrado'], 404);
            }
            
            $cremas = Topping::where('stall_id', $puesto->id)
                             ->orderBy('name')
                             ->get()
                             ->map(function($topping) {
                                 return [
                                     'id' => $topping->id,
                                     'nombre' => $topping->name,
                                     'precio_adicional' => $topping->price,
                                     'descripcion' => $topping->description,
                                     'activa' => $topping->active,
                                     'productos_asociados' => $topping->menuItems()->count()
                                 ];
                             });
            
            return response()->json([
                'puesto_id' => $puesto->id,
                'total_cremas' => $cremas->count(),
                'cremas' => $cremas
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener cremas: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener cremas'], 500);
        }
    }

    // MÉTODO 6: Crear nueva crema/acompañamiento
    public function crearCrema(Request $request)
    {
        try {
            $usuario = Auth::user();
            
            $validado = $request->validate([
                'nombre' => 'required|string|max:255',
                'precio_adicional' => 'required|numeric|min:0',
                'descripcion' => 'nullable|string|max:500',
                'activa' => 'nullable|boolean'
            ]);
            
            $puesto = FoodStall::where('seller_id', $usuario->id)->first();
            if (!$puesto) {
                return response()->json(['error' => 'No tienes un puesto registrado'], 404);
            }
            
            $crema = Topping::create([
                'stall_id' => $puesto->id,
                'name' => $validado['nombre'],
                'price' => $validado['precio_adicional'],
                'description' => $validado['descripcion'],
                'active' => $validado['activa'] ?? true
            ]);
            
            Cache::forget("menu_stall_{$puesto->id}");
            
            Log::info('Crema creada', [
                'topping_id' => $crema->id,
                'stall_id' => $puesto->id,
                'usuario_id' => $usuario->id
            ]);
            
            return response()->json([
                'message' => 'Crema creada exitosamente',
                'crema' => [
                    'id' => $crema->id,
                    'nombre' => $crema->name,
                    'precio_adicional' => $crema->price,
                    'activa' => $crema->active
                ]
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Errores de validación',
                'errores' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear crema: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear crema'], 500);
        }
    }

    // MÉTODO 7: Actualizar crema
    public function actualizarCrema(Request $request, $id)
    {
        try {
            $usuario = Auth::user();
            
            $validado = $request->validate([
                'nombre' => 'nullable|string|max:255',
                'precio_adicional' => 'nullable|numeric|min:0',
                'descripcion' => 'nullable|string|max:500',
                'activa' => 'nullable|boolean'
            ]);
            
            $crema = Topping::findOrFail($id);
            
            // Verificar permiso
            if ($crema->stall->seller_id !== $usuario->id) {
                return response()->json(['error' => 'No tienes permiso para editar esta crema'], 403);
            }
            
            $crema->update([
                'name' => $validado['nombre'] ?? $crema->name,
                'price' => $validado['precio_adicional'] ?? $crema->price,
                'description' => $validado['descripcion'] ?? $crema->description,
                'active' => $validado['activa'] ?? $crema->active
            ]);
            
            Cache::forget("menu_stall_{$crema->stall_id}");
            
            return response()->json([
                'message' => 'Crema actualizada exitosamente',
                'crema' => [
                    'id' => $crema->id,
                    'nombre' => $crema->name,
                    'precio_adicional' => $crema->price,
                    'activa' => $crema->active
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar crema: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar crema'], 500);
        }
    }

    // MÉTODO 7B: Eliminar crema
    public function eliminarCrema($id)
    {
        try {
            $usuario = Auth::user();
            
            $crema = Topping::findOrFail($id);
            
            // Verificar permiso
            if ($crema->stall->seller_id !== $usuario->id) {
                return response()->json(['error' => 'No tienes permiso para eliminar esta crema'], 403);
            }
            
            // Desasociar de todos los productos
            $crema->menuItems()->detach();
            
            // Eliminar crema
            $crema->delete();
            
            Cache::forget("menu_stall_{$crema->stall_id}");
            
            Log::info('Crema eliminada', [
                'topping_id' => $id,
                'usuario_id' => $usuario->id
            ]);
            
            return response()->json([
                'message' => 'Crema eliminada exitosamente'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al eliminar crema: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar crema'], 500);
        }
    }

    // MÉTODO 8: Asociar crema a producto (RF8)
    public function asociarCremaAProducto(Request $request, $productoId)
    {
        try {
            $usuario = Auth::user();
            
            $validado = $request->validate([
                'crema_id' => 'required|exists:toppings,id',
                'requerida' => 'nullable|boolean'
            ]);
            
            $producto = MenuItem::with('stall')->findOrFail($productoId);
            
            // Verificar permiso
            if ($producto->stall->seller_id !== $usuario->id) {
                return response()->json(['error' => 'No tienes permiso para editar este producto'], 403);
            }
            
            // Verificar que la crema pertenezca al mismo puesto
            $crema = Topping::where('id', $validado['crema_id'])
                           ->where('stall_id', $producto->stall_id)
                           ->first();
            
            if (!$crema) {
                return response()->json([
                    'error' => 'La crema no existe o no pertenece a tu puesto'
                ], 404);
            }
            
            // Asociar crema al producto
            $producto->toppings()->syncWithoutDetaching([
                $crema->id => ['required' => $validado['requerida'] ?? false]
            ]);
            
            Cache::forget("menu_stall_{$producto->stall_id}");
            
            Log::info('Crema asociada', [
                'producto_id' => $producto->id,
                'crema_id' => $crema->id,
                'usuario_id' => $usuario->id
            ]);
            
            return response()->json([
                'message' => 'Crema asociada al producto',
                'producto_id' => $producto->id,
                'crema_id' => $crema->id,
                'requerida' => $validado['requerida'] ?? false
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Errores de validación',
                'errores' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al asociar crema: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al asociar crema',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    // MÉTODO 9: Desasociar crema de producto
    public function desasociarCremaDeProducto(Request $request, $productoId, $cremaId)
    {
        try {
            $usuario = Auth::user();
            
            $producto = MenuItem::with('stall')->findOrFail($productoId);
            
            // Verificar permiso
            if ($producto->stall->seller_id !== $usuario->id) {
                return response()->json(['error' => 'No tienes permiso para editar este producto'], 403);
            }
            
            // Verificar que la crema exista y esté asociada
            $cremaAsociada = $producto->toppings()->where('topping_id', $cremaId)->exists();
            
            if (!$cremaAsociada) {
                return response()->json([
                    'error' => 'La crema no está asociada a este producto'
                ], 404);
            }
            
            // Desasociar crema
            $producto->toppings()->detach($cremaId);
            
            Cache::forget("menu_stall_{$producto->stall_id}");
            
            Log::info('Crema desasociada', [
                'producto_id' => $productoId,
                'crema_id' => $cremaId,
                'usuario_id' => $usuario->id
            ]);
            
            return response()->json([
                'message' => 'Crema desasociada del producto'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al desasociar crema: ' . $e->getMessage());
            return response()->json(['error' => 'Error al desasociar crema'], 500);
        }
    }

    // MÉTODO 10: Obtener cremas de un producto específico
    public function obtenerCremasDeProducto(Request $request, $productoId)
    {
        try {
            $producto = MenuItem::with(['toppings' => function($query) {
                $query->where('active', true);
            }])->findOrFail($productoId);
            
            $cremas = $producto->toppings->map(function($topping) {
                return [
                    'id' => $topping->id,
                    'nombre' => $topping->name,
                    'precio_adicional' => $topping->price,
                    'descripcion' => $topping->description,
                    'requerida' => $topping->pivot->required ?? false
                ];
            });
            
            return response()->json([
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->name,
                'total_cremas' => $cremas->count(),
                'cremas' => $cremas
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error al obtener cremas del producto: ' . $e->getMessage());
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }
    }

    // MÉTODO ANTIGUO: Mantener para compatibilidad
    public function showMenu($stallId)
    {
        $stall = FoodStall::where('id', $stallId)->where('active', true)->firstOrFail();

        $cacheKey = "menu_stall_{$stallId}";
        $menuItems = Cache::remember($cacheKey, 60, function () use ($stallId) {
            return MenuItem::with('category')
                ->where('stall_id', $stallId)
                ->where('active', true)
                ->orderByDesc('featured')
                ->get();
        });

        return response()->json([
            'stall' => ['id' => $stall->id, 'name' => $stall->name],
            'menu_items' => $menuItems,
        ]);
    }

    // MÉTODO ANTIGUO: Mantener para compatibilidad
    public function updateMenuItem(Request $request, $itemId)
    {
        $data = $request->validate([
            'active'      => 'nullable|boolean',
            'featured'    => 'nullable|boolean',
            'price'       => 'nullable|numeric|min:0',
            'name'        => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $menuItem = MenuItem::with('stall')->findOrFail($itemId);

        // Verifica que el usuario autenticado sea dueño del puesto
        if ($request->user()->id !== $menuItem->stall->seller_id) {
            abort(403, 'No autorizado');
        }

        $menuItem->fill($data)->save();
        Cache::forget("menu_stall_{$menuItem->stall_id}");

        return response()->json(['message' => 'Plato actualizado', 'menu_item' => $menuItem]);
    }
}