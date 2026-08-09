<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodStall;
use App\Models\Order;
use App\Services\DeliveryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GeoController extends Controller
{
    private const MAX_RADIUS_KM = 50;
    private const NEARBY_CACHE_TTL = 120; // segundos

    /**
     * Lista puestos cercanos usando Haversine (kilómetros).
     * Query params: lat, lng, radius(km, default 1), open_now (bool), sort
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric|min:0.1|max:' . self::MAX_RADIUS_KM,
        ]);

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        $radius = (float) $request->input('radius', 1);
        $openNow = filter_var($request->input('open_now', false), FILTER_VALIDATE_BOOLEAN);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(5, (int) $request->input('per_page', 10)));
        $sort = $request->input('sort', 'distance');

        return response()->json($this->resolveNearby($lat, $lng, $radius, $openNow, $page, $perPage, $sort));
    }

    /**
     * Lista de puestos pública con filtros avanzados (wrapper más completo para UI)
     * Params: lat,lng,radius,open_now,sort,page,per_page
     */
    public function index(Request $request)
    {
        $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'radius' => 'nullable|numeric|min:0.1|max:' . self::MAX_RADIUS_KM,
            'page' => 'nullable|integer',
            'per_page' => 'nullable|integer',
        ]);

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $radius = (float) $request->input('radius', 5);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(5, (int) $request->input('per_page', 10)));
        $openNow = filter_var($request->input('open_now', false), FILTER_VALIDATE_BOOLEAN);
        $sort = $request->input('sort', 'distance');

        // If no coords provided, fallback to all active stalls paginated
        if ($lat === null || $lng === null) {
            $query = FoodStall::where('active', 1);
            if ($sort === 'popularity') {
                $ordersCountSub = Order::select('stall_id', DB::raw('COUNT(*) as total_orders'))
                    ->groupBy('stall_id');
                $query->leftJoinSub($ordersCountSub, 'order_counts', 'food_stalls.id', '=', 'order_counts.stall_id')
                    ->orderByDesc('order_counts.total_orders');
            } else {
                $query->orderBy('name');
            }

            $all = $query->get();

            if ($openNow) {
                $all = $all->filter(function ($s) {
                    return $s->isOpenNow();
                })->values();
            }

            $total = $all->count();
            $slice = $all->slice(($page - 1) * $perPage, $perPage)->values();

            $data = $slice->map(function ($s) {
                $open = $s->isOpenNow();
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'address' => $s->address,
                    'latitude' => $s->latitude,
                    'longitude' => $s->longitude,
                    'open_now' => $open,
                    'active' => (bool) $s->active,
                ];
            });

            return response()->json([
                'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => (int) ceil($total / $perPage)],
                'data' => $data,
            ]);
        }

        return response()->json($this->resolveNearby((float) $lat, (float) $lng, $radius, $openNow, $page, $perPage, $sort));
    }

    /**
     * Devuelve marcadores ligeros para el mapa (min payload)
     */
    public function mapMarkers(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric|min:0.1|max:' . self::MAX_RADIUS_KM,
            'open_now' => 'nullable|boolean',
        ]);

        $result = $this->resolveNearby(
            (float) $request->input('lat'),
            (float) $request->input('lng'),
            (float) $request->input('radius', 1),
            filter_var($request->input('open_now', false), FILTER_VALIDATE_BOOLEAN),
            max(1, (int) $request->input('page', 1)),
            min(50, max(5, (int) $request->input('per_page', 10))),
            $request->input('sort', 'distance')
        );

        $markers = collect($result['data'])->map(function ($s) {
            $icon = 'marker-default';
            if ($s['open_now']) $icon = 'marker-open';
            if (!$s['active']) $icon = 'marker-closed';
            return [
                'id' => $s['id'],
                'lat' => $s['latitude'],
                'lng' => $s['longitude'],
                'name' => $s['name'],
                'icon' => $icon,
                'distance_km' => $s['distance_km'] ?? null,
            ];
        })->values();

        return response()->json(['meta' => $result['meta'], 'markers' => $markers]);
    }

    /**
     * Estado simple del puesto: abierto, cerrado, en_pausa, next_opening y horario de hoy.
     */
    public function status($stallId)
    {
        $stall = FoodStall::findOrFail($stallId);

        $open = $stall->isOpenNow();
        $inPause = $stall->isInPauseNow();

        // calcular next_opening (si está cerrado y tiene horario configurado)
        $next = null;
        if (!$open && $stall->opening_time) {
            $opening = $stall->opening_time; // H:i
            $now = now();
            $todayOpening = Carbon::createFromFormat('H:i', $opening)->setDate($now->year, $now->month, $now->day);
            if ($now < $todayOpening) {
                $next = $todayOpening->toDateTimeString();
            } else {
                $tomorrow = $now->copy()->addDay();
                $next = Carbon::createFromFormat('H:i', $opening)->setDate($tomorrow->year, $tomorrow->month, $tomorrow->day)->toDateTimeString();
            }
        }

        return response()->json([
            'stall_id' => $stall->id,
            'open_now' => $open,
            'in_pause' => $inPause,
            'next_opening' => $next,
            'opening_time' => $stall->opening_time,
            'closing_time' => $stall->closing_time,
        ]);
    }

    /**
     * Alias: obtener menu del puesto (compatibilidad)
     */
    public function menu($stallId)
    {
        return $this->stallForMap($stallId);
    }

    /**
     * Devuelve una URL pública de Google Maps Directions (cliente abre en app/web).
     * Query params: from_lat, from_lng
     */
    public function directions(Request $request, $stallId)
    {
        $request->validate([
            'from_lat' => 'required|numeric',
            'from_lng' => 'required|numeric',
        ]);

        $stall = FoodStall::findOrFail($stallId);

        if (!$stall->latitude || !$stall->longitude) {
            return response()->json(['error' => 'El puesto no tiene coordenadas configuradas'], 422);
        }

        $fromLat = $request->input('from_lat');
        $fromLng = $request->input('from_lng');

        $mode = $request->input('mode', 'driving'); // driving|walking|bicycling
        $mode = in_array($mode, ['driving', 'walking', 'bicycling']) ? $mode : 'driving';

        $url = "https://www.google.com/maps/dir/?api=1&origin={$fromLat},{$fromLng}&destination={$stall->latitude},{$stall->longitude}&travelmode={$mode}";

        return response()->json(['directions_url' => $url]);
    }

    /**
     * Devuelve datos del puesto junto con su menú (para abrir desde el mapa).
     */
    public function stallForMap($stallId)
    {
        $stall = FoodStall::with(['menuItems' => function ($q) {
            $q->where('active', 1)->with('category');
        }])->findOrFail($stallId);

        $payload = $stall->toArray();
        $payload['open_now'] = $stall->isOpenNow();
        $payload['in_pause'] = $stall->isInPauseNow();

        return response()->json(['stall' => $payload]);
    }

    /**
     * Estima ruta y ETA sin usar APIs externas (fallback). No guarda ubicación del cliente.
     * Query params: from_lat, from_lng, mode (driving|walking|bicycling)
     */
    public function route(Request $request, $stallId)
    {
        $request->validate([
            'from_lat' => 'required|numeric',
            'from_lng' => 'required|numeric',
            'mode' => 'nullable|string',
        ]);

        $stall = FoodStall::findOrFail($stallId);

        if (!$stall->latitude || !$stall->longitude) {
            return response()->json(['error' => 'El puesto no tiene coordenadas configuradas'], 422);
        }

        $fromLat = (float) $request->input('from_lat');
        $fromLng = (float) $request->input('from_lng');
        $mode = $request->input('mode', 'driving');
        if (!in_array($mode, ['driving', 'walking', 'bicycling'])) {
            $mode = 'driving';
        }

        // Reutilizar la fórmula única del proyecto (DeliveryService)
        $distanceKm = (new DeliveryService())->distanceKm($fromLat, $fromLng, (float)$stall->latitude, (float)$stall->longitude);

        // velocidad promedio (km/h)
        $speeds = [
            'driving' => 30.0,
            'walking' => 5.0,
            'bicycling' => 15.0,
        ];

        $hours = $distanceKm / $speeds[$mode];
        $minutes = (int) round($hours * 60);

        return response()->json([
            'stall_id' => $stall->id,
            'distance_km' => round($distanceKm, 2),
            'estimated_time_min' => $minutes,
            'mode' => $mode,
        ]);
    }

    /**
     * Núcleo compartido: puestos cercanos con Haversine, filtro open_now ANTES de
     * paginar (correcto) y caché corta por coordenadas/filtros.
     */
    private function resolveNearby(float $lat, float $lng, float $radius, bool $openNow, int $page, int $perPage, string $sort): array
    {
        $cacheKey = sprintf(
            'geo.nearby.%.4f.%.4f.%s.%d.%s.%d.%d',
            $lat, $lng, $radius, $openNow ? 1 : 0, $sort, $page, $perPage
        );

        return Cache::remember($cacheKey, self::NEARBY_CACHE_TTL, function () use ($lat, $lng, $radius, $openNow, $page, $perPage, $sort) {
            // Bounding box: pre-filtro espacial para reducir filas antes del Haversine
            $latDelta = $radius / 111.0;
            $lngDelta = $radius / (111.0 * cos(deg2rad($lat)));

            // Haversine formula (km) con bindings para cache de plan de ejecución
            $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

            // subconsulta para contar órdenes (popularidad)
            $ordersCountSub = DB::raw('(SELECT COUNT(*) FROM `orders` o WHERE o.stall_id = food_stalls.id AND o.status IN ("confirmed","preparing","ready","delivered")) as orders_count');

            $baseQuery = FoodStall::select('food_stalls.*', DB::raw("$haversine AS distance"), DB::raw($ordersCountSub))
                ->where('active', 1)
                ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
                ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
                ->havingRaw("distance <= ?", [$radius])
                ->addBinding([$lat, $lng, $lat], 'select');

            if ($sort === 'popularity') {
                $baseQuery->orderByDesc('orders_count')->orderBy('distance');
            } else {
                $baseQuery->orderBy('distance');
            }

            $allStalls = $baseQuery->get();

            // Filtro open_now ANTES de paginar para que la paginación sea correcta
            if ($openNow) {
                $allStalls = $allStalls->filter(function ($s) {
                    return $s->isOpenNow();
                })->values();
            }

            $total = $allStalls->count();
            $slice = $allStalls->slice(($page - 1) * $perPage, $perPage)->values();

            $data = $slice->map(function ($s) {
                $open = $s->isOpenNow(); // se calcula una sola vez por puesto
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'address' => $s->address,
                    'latitude' => $s->latitude,
                    'longitude' => $s->longitude,
                    'distance_km' => round((float) $s->distance, 2),
                    'orders_count' => (int) ($s->orders_count ?? 0),
                    'open_now' => $open,
                    'active' => (bool) $s->active,
                ];
            })->values();

            return [
                'meta' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $perPage),
                ],
                'data' => $data,
            ];
        });
    }
}