<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Devuelve los accesos rápidos y sugerencias para el dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user ? $user->role : 'guest';

        // Accesos rápidos por rol
        $quickAccess = [
            'customer' => [
                ['icon' => 'shopping_cart', 'label' => 'Nuevo Pedido', 'route' => '/pedidos'],
                ['icon' => 'history', 'label' => 'Mis Pedidos', 'route' => '/mis-pedidos'],
                ['icon' => 'star', 'label' => 'Promociones', 'route' => '/promociones'],
                ['icon' => 'map', 'label' => 'Mapa de Puestos', 'route' => '/mapa'],
                ['icon' => 'favorite', 'label' => 'Favoritos', 'route' => '/favoritos'],
                ['icon' => 'person', 'label' => 'Perfil', 'route' => '/perfil'],
                ['icon' => 'notifications', 'label' => 'Notificaciones', 'route' => '/notificaciones'],
                ['icon' => 'help', 'label' => 'Ayuda', 'route' => '/ayuda'],
                ['icon' => 'settings', 'label' => 'Configuración', 'route' => '/configuracion'],
                ['icon' => 'receipt', 'label' => 'Comprobantes', 'route' => '/comprobantes'],
                ['icon' => 'loyalty', 'label' => 'Recompensas', 'route' => '/recompensas'],
                ['icon' => 'logout', 'label' => 'Salir', 'route' => '/logout'],
            ],
            'vendor' => [
                ['icon' => 'restaurant', 'label' => 'Mi Puesto', 'route' => '/mis-puesto'],
                ['icon' => 'menu_book', 'label' => 'Menú', 'route' => '/menu'],
                ['icon' => 'schedule', 'label' => 'Horarios', 'route' => '/horarios'],
                ['icon' => 'assignment', 'label' => 'Pedidos Activos', 'route' => '/pedidos-activos'],
                ['icon' => 'attach_money', 'label' => 'Comisiones', 'route' => '/comisiones'],
                ['icon' => 'star', 'label' => 'Promociones', 'route' => '/promociones'],
                ['icon' => 'person', 'label' => 'Perfil', 'route' => '/perfil'],
                ['icon' => 'notifications', 'label' => 'Notificaciones', 'route' => '/notificaciones'],
                ['icon' => 'help', 'label' => 'Ayuda', 'route' => '/ayuda'],
                ['icon' => 'settings', 'label' => 'Configuración', 'route' => '/configuracion'],
                ['icon' => 'receipt', 'label' => 'Comprobantes', 'route' => '/comprobantes'],
                ['icon' => 'logout', 'label' => 'Salir', 'route' => '/logout'],
            ],
        ];

        // Sugerencias dinámicas por historial de consumo (RF5)
        $suggestions = [];
        if ($user) {
            // Obtener los productos más consumidos por el usuario
            $orderItems = \App\Models\OrderItem::whereHas('order', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->select('product_id', \DB::raw('SUM(quantity) as total_consumo'))
            ->groupBy('product_id')
            ->orderByDesc('total_consumo')
            ->limit(3)
            ->get();

            $productIds = $orderItems->pluck('product_id');
            $products = \App\Models\MenuItem::whereIn('id', $productIds)->get()->keyBy('id');
            foreach ($orderItems as $item) {
                $product = $products->get($item->product_id);
                if ($product) {
                    $suggestions[] = [
                        'title' => $product->name,
                        'image' => $product->image_path ?? '/images/default.jpg',
                        'route' => '/menu/' . $product->id,
                        'description' => $product->description,
                        'total_consumo' => $item->total_consumo
                    ];
                }
            }
        }
        // Si no hay historial, mostrar sugerencias por defecto
        if (count($suggestions) < 3) {
            $default = [
                ['title' => 'Salchipapas Clásicas', 'image' => '/images/salchipapas.jpg', 'route' => '/menu/1'],
                ['title' => 'Broaster Familiar', 'image' => '/images/broaster.jpg', 'route' => '/menu/2'],
                ['title' => 'Combo Bebidas', 'image' => '/images/bebidas.jpg', 'route' => '/menu/3'],
            ];
            foreach ($default as $d) {
                if (count($suggestions) >= 3) break;
                $suggestions[] = $d;
            }
        }

        return response()->json([
            'role' => $role,
            'quick_access' => $quickAccess[$role] ?? $quickAccess['customer'],
            'suggestions' => $suggestions,
        ]);
    }
}
