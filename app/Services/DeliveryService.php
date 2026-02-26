<?php

namespace App\Services;

use App\Models\FoodStall;

class DeliveryService
{
    /**
     * Calcula distancia en kilómetros entre dos coordenadas (Haversine)
     */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $d = $earthRadius * $c;

        return round($d, 2);
    }

    /**
     * Calcular costo de delivery basado en distancia y tarifas del puesto
     * Si el puesto no tiene coordenadas o no se proporcionan coords del cliente,
     * retorna null (no calculado) para que caller use tarifa por defecto.
     */
    public function calculateDeliveryCost(FoodStall $stall, ?float $deliveryLat, ?float $deliveryLng): array
    {
        // Default tarifa por km en el puesto (o 3 S/ por km)
        $rate = $stall->delivery_rate_per_km ?? config('delivery.rate_per_km', 3.00);
        $min = $stall->delivery_min_cost ?? config('delivery.min_cost', 3.00);

        if (!$stall->latitude || !$stall->longitude || !$deliveryLat || !$deliveryLng) {
            return [
                'distance_km' => null,
                'cost' => max($min, $rate) // fallback mínimo
            ];
        }

        $distance = $this->distanceKm((float)$stall->latitude, (float)$stall->longitude, $deliveryLat, $deliveryLng);

        $cost = round($distance * (float)$rate, 2);
        if ($cost < $min) $cost = (float)$min;

        return [
            'distance_km' => $distance,
            'cost' => $cost,
            'rate_per_km' => (float)$rate,
            'min_cost' => (float)$min
        ];
    }
}
