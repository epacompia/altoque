<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FoodStall;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class GeoController extends Controller
{
    /**
     * Lista puestos cercanos usando Haversine (kilómetros).
     * Query params: lat, lng, radius(km, default 1), open_now (bool), sort
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric',
        ]);

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $radius = $request->input('radius', 1);
        $openNow = filter_var($request->input('open_now', false), FILTER_VALIDATE_BOOLEAN);

        // Haversine formula (km)
        $haversine = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(5, (int) $request->input('per_page', 10)));
        $sort = $request->input('sort', 'distance'); // distance | popularity

        // agregar subconsulta para contar órdenes (popularidad)
        $ordersCountSub = DB::raw('(SELECT COUNT(*) FROM `orders` o WHERE o.stall_id = food_stalls.id AND o.status IN ("confirmed","preparing","ready","delivered")) as orders_count');

        $baseQuery = FoodStall::select('food_stalls.*', DB::raw("$haversine AS distance"), DB::raw($ordersCountSub))
            ->where('active', 1)
            ->havingRaw("distance <= ?", [$radius]);

        if ($sort === 'popularity') {
            $baseQuery->orderByDesc('orders_count')->orderBy('distance');
        } else {
            $baseQuery->orderBy('distance');
        }

        $stalls = $baseQuery->skip(($page - 1) * $perPage)->take($perPage)->get();

        if ($openNow) {
            $stalls = $stalls->filter(function ($s) {
                return $s->isOpenNow();
            })->values();
        }

        $data = $stalls->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'address' => $s->address,
                'latitude' => $s->latitude,
                'longitude' => $s->longitude,
                'distance_km' => round($s->distance, 2),
                'orders_count' => (int) ($s->orders_count ?? 0),
                'open_now' => $s->isOpenNow(),
                'active' => (bool) $s->active,
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Lista de puestos pública con filtros avanzados (wrapper más completo para UI)
     * Params: lat,lng,radius,open_now,sort,page,per_page,rating_min
     */
    public function index(Request $request)
    {
        $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'radius' => 'nullable|numeric',
            'page' => 'nullable|integer',
            'per_page' => 'nullable|integer',
        ]);

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $radius = $request->input('radius', 5);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(5, (int) $request->input('per_page', 10)));
        $openNow = filter_var($request->input('open_now', false), FILTER_VALIDATE_BOOLEAN);
        $sort = $request->input('sort', 'distance');

        // If no coords provided, fallback to all active stalls paginated
        if (!$lat || !$lng) {
            $query = FoodStall::where('active', 1);
            if ($sort === 'popularity') {
                $query->orderByDesc(DB::raw('(SELECT COUNT(*) FROM orders o WHERE o.stall_id = food_stalls.id)'));
            } else {
                $query->orderBy('name');
            }

            $total = $query->count();
            $stalls = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

            $data = $stalls->map(function ($s) use ($lat, $lng) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'address' => $s->address,
                    'latitude' => $s->latitude,
                    'longitude' => $s->longitude,
                    'open_now' => $s->isOpenNow(),
                    'active' => (bool) $s->active,
                ];
            });

            return response()->json([
                'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total],
                'data' => $data
            ]);
        }

        // Otherwise reuse nearby with pagination
        $request->merge(['page' => $page, 'per_page' => $perPage]);
        return $this->nearby($request);
    }

    /**
     * Devuelve marcadores ligeros para el mapa (min payload)
     */
    public function mapMarkers(Request $request)
    {
        $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'radius' => 'nullable|numeric',
        ]);

        // Reuse nearby to get filtered stalls
        $nearbyResponse = $this->nearby($request);
        $payload = $nearbyResponse->getData(true);

        $markers = collect($payload['data'])->map(function ($s) {
            $icon = 'marker-default';
            if ($s['open_now']) $icon = 'marker-open';
            if (!$s['active']) $icon = 'marker-closed';
            return [
                'id' => $s['id'],
                'lat' => $s['latitude'],
                'lng' => $s['longitude'],
                'name' => $s['name'],
                'icon' => $icon,
                'distance_km' => $s['distance_km'] ?? null
            ];
        });

        return response()->json(['markers' => $markers]);
    }

    /**
     * Estado simple del puesto: abierto, cerrado, en_pausa y next_opening
     */
    public function status($stallId)
    {
        $stall = FoodStall::findOrFail($stallId);

        $open = $stall->isOpenNow();
        $inPause = $stall->isInPauseNow();

        // calcular next opening (si está cerrado)
        $next = null;
        if (!$open) {
            $opening = $stall->opening_time; // H:i
            $now = now();
            $todayOpening = \Carbon\Carbon::createFromFormat('H:i', $opening)->setDate($now->year, $now->month, $now->day);
            if ($now < $todayOpening) {
                $next = $todayOpening->toDateTimeString();
            } else {
                $tomorrow = $now->copy()->addDay();
                $next = \Carbon\Carbon::createFromFormat('H:i', $opening)->setDate($tomorrow->year, $tomorrow->month, $tomorrow->day)->toDateTimeString();
            }
        }

        return response()->json([
            'stall_id' => $stall->id,
            'open_now' => $open,
            'in_pause' => $inPause,
            'next_opening' => $next
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

        $fromLat = $request->input('from_lat');
        $fromLng = $request->input('from_lng');

        $mode = $request->input('mode', 'driving'); // driving|walking|bicycling
        $mode = in_array($mode, ['driving','walking','bicycling']) ? $mode : 'driving';

        $url = "https://www.google.com/maps/dir/?api=1&origin={$fromLat},{$fromLng}&destination={$stall->latitude},{$stall->longitude}&travelmode={$mode}";

        return response()->json(['directions_url' => $url]);
    }

    /**
     * Devuelve datos del puesto junto con su menú (para abrir desde el mapa).
     */
    public function stallForMap($stallId)
    {
        $stall = FoodStall::with(['menuItems' => function ($q) {
            $q->where('active', 1);
        }])->findOrFail($stallId);

        return response()->json(["stall" => $stall]);
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
            'mode' => 'nullable|string'
        ]);

        $stall = FoodStall::findOrFail($stallId);

        $fromLat = $request->input('from_lat');
        $fromLng = $request->input('from_lng');
        $mode = $request->input('mode', 'driving');
        if (!in_array($mode, ['driving','walking','bicycling'])) { $mode = 'driving'; }

        // Haversine distance (km)
        $latFrom = deg2rad($fromLat);
        $lngFrom = deg2rad($fromLng);
        $latTo = deg2rad($stall->latitude);
        $lngTo = deg2rad($stall->longitude);

        $dlat = $latTo - $latFrom;
        $dlng = $lngTo - $lngFrom;
        $a = sin($dlat/2) * sin($dlat/2) + cos($latFrom) * cos($latTo) * sin($dlng/2) * sin($dlng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distanceKm = 6371 * $c;

        // average speeds (km/h)
        $speeds = [
            'driving' => 30.0,
            'walking' => 5.0,
            'bicycling' => 15.0
        ];

        $speed = $speeds[$mode];
        $hours = $distanceKm / $speed;
        $minutes = (int) round($hours * 60);

        return response()->json([
            'stall_id' => $stall->id,
            'distance_km' => round($distanceKm, 2),
            'estimated_time_min' => $minutes,
            'mode' => $mode
        ]);
    }
}
