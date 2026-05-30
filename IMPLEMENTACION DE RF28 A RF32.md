# IMPLEMENTACIÓN DE RF28 A RF32

Resumen
-------
Este documento describe la implementación backend de los requerimientos RF28, RF29, RF30, RF31 y RF32 (módulo de geolocalización y búsqueda, mapas, filtrado y rutas) añadida al proyecto Laravel 10.

Archivos añadidos / modificados
--------------------------------
- Nuevo controlador: `app/Http/Controllers/GeoController.php`
- Rutas añadidas / modificadas: `routes/api.php` (varias rutas públicas bajo `/api/puestos`)

Objetivo
--------
Proveer endpoints REST públicos (no almacenan ubicación del cliente) que permitan a la aplicación móvil/web:
- Buscar puestos cercanos por coordenadas (Haversine).
- Obtener marcadores ligeros para mostrar en un mapa.
- Consultar estado horario del puesto (abierto/pausa/próxima apertura).
- Recuperar el menú de un puesto desde el mapa (pre-cargado).
- Generar URL de direcciones para abrir en Google Maps.
- Estimar ruta/ETA sin usar APIs externas (fallback).

Endpoints implementados
------------------------
Base path: `/api/puestos`

1) GET /api/puestos
   - Descripción: Listado público de puestos con filtros avanzados (wrapper para UI).
   - Parámetros (query):
     - `lat` (optional, numeric)
     - `lng` (optional, numeric)
     - `radius` (optional, numeric, km, default 5)
     - `open_now` (optional, bool)
     - `sort` (optional, `distance`|`popularity`, default `distance`)
     - `page` (optional, int)
     - `per_page` (optional, int, max 50)
   - Respuesta: JSON con `meta` (paginación) y `data` (lista de puestos).

2) GET /api/puestos/nearby
   - Descripción: Búsqueda por coordenadas (Haversine).
   - Parámetros (query) obligatorios:
     - `lat` (numeric)
     - `lng` (numeric)
   - Parámetros (query) opcionales:
     - `radius` (km, default 1)
     - `open_now` (bool)
     - `page`, `per_page`, `sort`
   - Respuesta: `data` array con `id,name,address,latitude,longitude,distance_km,orders_count,open_now,active`.

3) GET /api/puestos/map-markers
   - Descripción: Marcadores ligeros para consumo directo por componentes de mapa.
   - Parámetros: mismos que `/nearby`.
   - Respuesta: `markers` array con `id,lat,lng,name,icon,distance_km`.

4) GET /api/puestos/{stallId}/map
   - Descripción: Devuelve el puesto con su `menuItems` activos (para abrir desde el mapa).
   - Respuesta: objeto `stall` con relación `menuItems` filtrada por `active = 1`.

5) GET /api/puestos/{stallId}/menu
   - Alias a `/map` para compatibilidad.

6) GET /api/puestos/{stallId}/status
   - Descripción: Estado simple del puesto: `open_now`, `in_pause`, `next_opening`.
   - Respuesta: `{ stall_id, open_now, in_pause, next_opening }`.

7) GET /api/puestos/{stallId}/directions
   - Descripción: Devuelve una URL pública de Google Maps Directions (no requiere API key).
   - Parámetros (query): `from_lat`, `from_lng`, `mode` (`driving|walking|bicycling`, default `driving`).
   - Uso: cliente abre la URL en navegador o app de mapas.

8) GET /api/puestos/{stallId}/route
   - Descripción: Estimación local de distancia (Haversine) y ETA aproximada sin usar APIs externas.
   - Parámetros (query): `from_lat`, `from_lng`, `mode`.
   - Respuesta: `{ stall_id, distance_km, estimated_time_min, mode }`.

Lógica y consideraciones técnicas
----------------------------------
- Cálculo de distancia: fórmula Haversine implementada en consultas SQL para `nearby` (retorna `distance` en km).
- Popularidad: proxy `orders_count` obtenido vía subconsulta. Ordenamiento por `orders_count` cuando `sort=popularity`.
- Horarios y pausas: uso de métodos existentes en `FoodStall` (`isOpenNow`, `isInPauseNow`) y modelo `StallPause` para verificar pausas activas.
- Privacidad: no se persiste la ubicación del cliente en la base de datos; las rutas/ETAs se calculan al vuelo.
- Paginación: parámetros `page` y `per_page` (limite máximo 50).
- Seguridad: rutas públicas — no requieren autenticación. No se exponen datos sensibles.

Ejemplos de uso (curl)
---------------------
Obtener puestos cercanos:

```bash
curl -G 'http://localhost:8000/api/puestos/nearby' \
  --data-urlencode "lat=-12.0464" \
  --data-urlencode "lng=-77.0428" \
  --data-urlencode "radius=1" \
  --data-urlencode "page=1" \
  --data-urlencode "per_page=10"
```

Obtener marcadores:

```bash
curl -G 'http://localhost:8000/api/puestos/map-markers' --data-urlencode "lat=-12.0464" --data-urlencode "lng=-77.0428"
```

Estado y próxima apertura:

```bash
curl 'http://localhost:8000/api/puestos/2/status'
```

Estimar ruta/ETA:

```bash
curl -G 'http://localhost:8000/api/puestos/2/route' --data-urlencode "from_lat=-12.0464" --data-urlencode "from_lng=-77.0428" --data-urlencode "mode=walking"
```

Obtener URL de direcciones (abrir en navegador):

```bash
curl -G 'http://localhost:8000/api/puestos/2/directions' --data-urlencode "from_lat=-12.0464" --data-urlencode "from_lng=-77.0428" --data-urlencode "mode=driving"
```

Limitaciones actuales y pasos recomendados
-----------------------------------------
1. Ratings / Filtrado por calificación: no se encontró en el repositorio un modelo de reseñas/valoraciones (`Review`). Si deseas filtrar por `rating_min` o mostrar `rating_average`, debo crear:
   - migración `reviews` (user_id, stall_id, rating, comment, created_at)
   - modelo `Review` y endpoints CRUD (POST /api/puestos/{id}/reviews, GET /api/puestos/{id}/reviews)
   - agregar cálculo y columna `rating_average` (cacheada) o una agregación SQL.

2. Performance: la búsqueda Haversine en SQL funciona bien para datasets pequeños/medianos. Para producción con mayor carga recomiendo:
   - índices en columnas `latitude` y `longitude` y `active`.
   - uso de MySQL Spatial Index (GIS) y consultas `ST_Distance_Sphere` para mayor precisión y rendimiento.
   - caching por tiles/coord con TTL corto (ej. Redis) para reducir carga.

3. ETA realista con tráfico: integrar Google Directions API (o Mapbox Directions) mejora ETA y rutas reales, pero requiere API key y puede incurrir en coste. Implementación sugerida:
   - añadir `config/services.php` entry para `google_maps` con `key` desde `env`.
   - endpoint opcional que consulta Directions API y retorna `route_polyline, duration, distance`.

4. Tests: agregar pruebas PHPUnit para los endpoints geográficos (unit + feature) y un set de fixtures con varios `FoodStall` cerca/lejos.

5. Seguridad/Rate-limiting: poner `throttle` middleware en endpoints públicos de mapa para evitar abuso (ej. `throttle:60,1`).

Archivo(s) modificados
----------------------
- `app/Http/Controllers/GeoController.php` (nuevo/extendido)
- `routes/api.php` (rutas añadidas)

Checklist de verificación rápida (para ejecutar localmente)
------------------------------------------------------
1. `php artisan migrate` (si aplicas migraciones adicionales más adelante)
2. Levantar servidor: `php artisan serve --host=127.0.0.1 --port=8000`
3. Ejecutar los `curl` mostrados arriba y validar estructura JSON.

Próximos pasos opcionales (puedo implementar)
--------------------------------------------
- Crear migración y modelo `reviews` + endpoints de reseñas (para RF31 filtrado por calificación).
- Añadir tests PHPUnit para los endpoints geográficos.
- Integrar Google Directions API cuando proveas API key.
- Añadir índices/migraciones recomendadas para lat/lng.

Si quieres que proceda con alguna de las tareas anteriores, dime cuál y la implemento (creaciones y cambios mínimos sin tocar otras funcionalidades existentes).

Fecha: 2026-03-09
Autor: Equipo backend (implementado por Copilot sobre tu repo)
