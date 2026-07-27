# Propuesta de Optimización de Rendimiento — Backend Altoque

> **Versión:** 1.0
> **Fecha:** Julio 2026
> **Audiencia:** Equipo de Desarrollo Backend
> **Objetivo:** Reducir tiempos de respuesta de la API de >2s a <200ms

---

## Diagnóstico de Problemas Actuales

### Línea Base

| Endpoint | Latencia Actual | Endpoint | Latencia Actual |
|----------|----------------|----------|----------------|
| `GET /api/puestos/nearby` | >3000ms | `GET /api/pedidos` | >800ms |
| `GET /api/puestos` | >1500ms | `GET /api/mis-pedidos` | >1000ms |
| `GET /api/menu/{stall}` | >500ms | `POST /api/pedidos` | >1200ms |
| `GET /api/puestos/{id}/status` | >400ms | `GET /api/comisiones/reporte` | >2000ms |

### Causas Raíz Identificadas

1. **N+1 Queries** en 8+ controladores
2. **Falta de eager loading** en relaciones críticas
3. **Queries sin paginar** que cargan miles de registros en memoria
4. **Cálculo Haversine** sobre todas las filas sin pre-filtro espacial
5. **Índices compuestos ausentes** en tablas clave
6. **Cache en archivo** (driver `file`) sin Redis
7. **Queue en sincronía** (`QUEUE_CONNECTION=sync`) bloqueando requests
8. **Sin caché de respuestas** en endpoints públicos

---

## Plan de Optimización por Fases

---

## FASE 1 — Quick Wins (Día 1-2, Alto Impacto, Bajo Riesgo)

### 1.1 Cache Driver: Migrar de `file` a `redis`

**Archivo:** `.env`
```diff
-CACHE_DRIVER=file
+CACHE_DRIVER=redis
```

**Archivo:** `config/cache.php`
```php
'default' => env('CACHE_DRIVER', 'redis'),
```

**Impacto:** Redis está ya configurado en `.env` (REDIS_HOST=127.0.0.1, REDIS_PORT=6379). Solo hay que cambiar la variable. Las operaciones de caché pasan de I/O de disco a memoria RAM (~10µs vs ~5ms por operación).

### 1.2 Queue Driver: Migrar de `sync` a `database`

**Archivo:** `.env`
```diff
-QUEUE_CONNECTION=sync
+QUEUE_CONNECTION=database
```

**Impacto:** Los pagos (`ProcessPaymentJob`), facturación electrónica y transferencias de comisiones se procesan en background. El request responde en <100ms en lugar de bloquearse esperando.

**Requisito:** Ejecutar worker:
```bash
php artisan queue:work database --queue=high,default --tries=3 --sleep=3
```

### 1.3 Eager Loading — Corregir N+1 Críticos

#### OrderController.php

| Línea | Problema | Código Actual | Código Corregido |
|-------|----------|---------------|-------------------|
| 242 | Missing `stall` | `Order::findOrFail($id)` | `Order::with('stall')->findOrFail($id)` |
| 348 | Missing `client` | `->with(['items.product', 'stall', 'payment'])` | `->with(['items.product', 'stall', 'payment', 'client'])` |
| 456 | `items.product` innecesario | `->with(['stall', 'items.product'])` | `->withCount('items')` |
| 466 | Collection `count()` | `$orden->items->count()` | `$orden->items_count` |
| 505 | `items.product` innecesario | `->with(['client', 'items.product'])` | `->with(['client'])->withCount('items')` |
| 515 | Collection `count()` | `$orden->items->count()` | `$orden->items_count` |
| 561 | Missing `stall` | `Order::findOrFail($orderId)` | `Order::with('stall')->findOrFail($orderId)` |

#### MenuController.php

| Línea | Problema | Código Actual | Código Corregido |
|-------|----------|---------------|-------------------|
| 420 | N+1 count por topping | `$topping->menuItems()->count()` | `Topping::withCount('menuItems')->...` y usar `$topping->menu_items_count` |
| 504 | Missing `stall` | `Topping::findOrFail($id)` | `Topping::with('stall')->findOrFail($id)` |
| 542 | Missing `stall` | `Topping::findOrFail($id)` | `Topping::with('stall')->findOrFail($id)` |

#### StallScheduleController.php

| Línea | Problema | Código Actual | Código Corregido |
|-------|----------|---------------|-------------------|
| 289 | Missing `stall` | `StallPause::findOrFail($id)` | `StallPause::with('stall')->findOrFail($id)` |
| 352 | Missing `stall` | `StallPause::findOrFail($id)` | `StallPause::with('stall')->findOrFail($id)` |

#### DashboardController.php

| Línea | Problema | Código Actual | Código Corregido |
|-------|----------|---------------|-------------------|
| 64 | N+1 find en loop | `MenuItem::find($item->product_id)` | `MenuItem::whereIn('id', $ids)->get()->keyBy('id')` |

### 1.4 Paginar Endpoints Sin Límite

#### OrderController.php — `listarMisPedidos()`

```diff
// Línea 507: Cambiar de get() a paginate()
-$ordenes = $query->orderBy('created_at', 'desc')->get();
+$ordenes = $query->orderBy('created_at', 'desc')->paginate(20);
```

#### CommissionController.php — `listarReglas()`

```diff
// Línea 133
-$reglas = $query->orderBy('type')->get();
+$reglas = $query->orderBy('type')->paginate(50);
```

#### CommissionController.php — `transferirComisiones()`

```diff
// Línea 412: Cambiar a chunk + job
-$comisiones = $query->get();
+$query->chunk(100, function ($comisiones) {
+    foreach ($comisiones as $comision) {
+        ProcessCommissionTransfer::dispatch($comision);
+    }
+});
```

### 1.5 Caché de Categorías

**Archivo:** `MenuController.php` — método `obtenerCategorias()`

```diff
-$categorias = \App\Models\Category::get()->map(...);
+$categorias = Cache::remember('categories_all', 86400, function () {
+    return \App\Models\Category::select('id', 'name')
+        ->orderBy('name')
+        ->get()
+        ->map(fn($c) => ['id' => $c->id, 'nombre' => $c->name]);
+});
```

---

## FASE 2 — Optimización de Base de Datos (Día 3-4, Alto Impacto)

### 2.1 Índices Compuestos Faltantes

Ejecutar migración:

```php
// database/migrations/YYYY_MM_DD_add_composite_indexes.php

Schema::table('orders', function (Blueprint $table) {
    $table->index(['stall_id', 'status'], 'orders_stall_status_idx');
    $table->index(['user_id', 'status'], 'orders_user_status_idx');
});

Schema::table('food_stalls', function (Blueprint $table) {
    $table->index('active', 'food_stalls_active_idx');
    $table->index(['latitude', 'longitude'], 'food_stalls_lat_lng_idx');
});

Schema::table('stall_pauses', function (Blueprint $table) {
    $table->index(['stall_id', 'is_active', 'start_at', 'end_at'], 'stall_pauses_active_range_idx');
});

Schema::table('order_items', function (Blueprint $table) {
    $table->index(['product_id', 'order_id'], 'order_items_product_order_idx');
});
```

**Impacto:** Los índices compuestos permiten a MySQL resolver filtros de varias columnas con un solo index seek en lugar de full table scans. La query `COUNT(*) WHERE stall_id=X AND status IN (confirmed,preparing,ready)` pasa de escanear miles de filas a leer exactamente las que coinciden.

### 2.2 Bounding Box para Haversine

**Archivo:** `GeoController.php`

```diff
// Antes del HAVING, agregar pre-filtro espacial
+$latDelta = $radius / 111.0;
+$lngDelta = $radius / (111.0 * cos(deg2rad($lat)));
+$baseQuery->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
+          ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);
```

**Impacto:** Reduce el set de candidatos para el Haversine de miles de filas a docenas. El cálculo trigonométrico solo se ejecuta en filas que están dentro del bounding box.

### 2.3 Conversión de `whereHas` Anidado a JOIN

**Archivo:** `CommissionController.php` — método `transferirComisiones()`

```diff
// Líneas 408-410: Reemplazar whereHas doble por JOIN
-$query->whereHas('order.foodStall', function ($q) use ($vendorId) {
-    $q->where('seller_id', $vendorId);
-});

+$query->join('orders', 'order_commissions.order_id', '=', 'orders.id')
+      ->join('food_stalls', 'orders.stall_id', '=', 'food_stalls.id')
+      ->where('food_stalls.seller_id', $vendorId)
+      ->select('order_commissions.*'); // evitar columnas duplicadas
```

**Impacto:** Los JOINs son órdenes de magnitud más rápidos que los subqueries EXISTS anidados en MySQL.

### 2.4 Agregaciones en DB en lugar de Colecciones PHP

**Archivo:** `CommissionService.php` — método `getCommissionReport()`

```diff
// Líneas 169-196: Mover SUM/COUNT/GROUP al motor SQL
-$commissions = OrderCommission::whereBetween('calculated_at', [$from, $to])->get();
+$report = OrderCommission::whereBetween('calculated_at', [$from, $to])
+    ->selectRaw('commission_rule_id, SUM(commission_amount) as total, COUNT(*) as count')
+    ->groupBy('commission_rule_id')
+    ->get();
```

---

## FASE 3 — Caché Inteligente (Día 5-6, Medio-Alto Impacto)

### 3.1 Caché de `isOpenNow()` e `isInPauseNow()`

En `FoodStall.php`, cambiar el método para usar caché con Redis:

```php
public function isOpenNow()
{
    return Cache::remember("stall.{$this->id}.open_now", 300, function () {
        $now = now()->format('H:i');
        $opening = $this->opening_time;
        $closing = $this->closing_time;

        if ($opening < $closing) {
            $abierto = $now >= $opening && $now < $closing;
        } else {
            $abierto = $now >= $opening || $now < $closing;
        }

        if ($abierto && $this->pauses()->active()->exists()) {
            return false;
        }

        return $abierto;
    });
}
```

Y en los scopes que modifican horarios/pausas, invalidar la caché:

```php
Cache::forget("stall.{$puesto->id}.open_now");
```

### 3.2 Caché de Stall Lookup

El query `FoodStall::where('seller_id', $usuario->id)->first()` se repite en **8 métodos** de `MenuController`. Solución:

```diff
// En el constructor o como helper
+private function obtenerMiPuesto()
+{
+    $usuario = Auth::user();
+    return Cache::remember("user.{$usuario->id}.stall", 3600, function () use ($usuario) {
+        return FoodStall::where('seller_id', $usuario->id)->first();
+    });
+}
```

E invalidar al crear/modificar puesto.

### 3.3 Caché de Menú Público con Redis

El endpoint `GET /api/menu/{stallId}` ya usa `Cache::remember` con TTL de 60s. Con Redis en lugar de file, la latencia de lectura de caché baja de ~5ms a ~0.1ms.

```php
// Ya está implementado, solo funciona mejor con Redis
$cacheKey = "menu_stall_{$stallId}_" . ($request->has('nocache') ? time() : '');
$menuItems = Cache::remember($cacheKey, 300, function () use ($stallId) { ... });
```

### 3.4 Caché de Conteo de Pedidos Activos

En `calcularTiempoEstimado()`:

```diff
-$ordenesActivas = Order::where('stall_id', $puesto->id)
-    ->whereIn('status', ['confirmed','preparing','ready'])
-    ->count();
+$ordenesActivas = Cache::remember("stall.{$puesto->id}.active_orders_count", 30, function () use ($puesto) {
+    return Order::where('stall_id', $puesto->id)
+        ->whereIn('status', ['confirmed','preparing','ready'])
+        ->count();
+});
```

El TTL de 30 segundos es suficiente — el tiempo estimado no necesita ser exacto al milisegundo.

### 3.5 Caché de Reporte de Comisiones

Los reportes se basan en rangos de fechas, los datos históricos no cambian. Caché con invalidación:

```php
public function getCommissionReport($from, $to, $vendorId = null)
{
    $cacheKey = "commission_report_{$from}_{$to}_" . ($vendorId ?? 'all');
    return Cache::remember($cacheKey, 3600, function () use ($from, $to, $vendorId) {
        // ... query actual ...
    });
}
```

Invalidar cuando se cree/transfiera una comisión:
```php
Cache::tags(['commissions'])->flush();
```

---

## FASE 4 — Optimización del Código (Día 7-8, Impacto Medio)

### 4.1 Eliminar Duplicación de `isOpenNow()` en GeoController

En `nearby()`, el método `isOpenNow()` se llama 2 veces por stall (en filter y en map). Solución:

```php
// En lugar de llamar isOpenNow() que hace query
$stalls->load('pauses'); // eager load en 1 query

// Luego en filter y map:
$stall->pauses->contains(function ($pause) {
    return $pause->is_active && $pause->start_at <= now() && $pause->end_at > now();
});
```

### 4.2 Batch Loading en `crearPedido()` de OrderController

```diff
// Antes del foreach de productos:
+$productIds = array_column($validado['productos'], 'producto_id');
+$productos = MenuItem::whereIn('id', $productIds)->with('toppings')->get()->keyBy('id');
+$toppingsValidados = $productos->flatMap->toppings->keyBy('id');

// Dentro del foreach:
-$producto = MenuItem::findOrFail($prod['producto_id']);
+$producto = $productos->get($prod['producto_id']);
if (!$producto) { /* error */ }

// Para validar cremas:
-$crema = $producto->toppings()->find($cremaId);
+$crema = $toppingsValidados->get($cremaId);
```

**Impacto:** Reduce 10+ queries individuales a solo 2 queries totales.

### 4.3 Evitar Correlated Subquery en `index()`

```diff
// En GeoController, línea 100:
-$query->orderByDesc(DB::raw('(SELECT COUNT(*) FROM orders o WHERE o.stall_id = food_stalls.id)'));
+$query->leftJoinSub(
+    Order::select('stall_id', DB::raw('COUNT(*) as total_orders'))
+        ->groupBy('stall_id'),
+    'order_counts',
+    'food_stalls.id',
+    '=',
+    'order_counts.stall_id'
+)->orderByDesc('order_counts.total_orders');
```

O mejor: desnormalizar y cachear el contador en `food_stalls.orders_count` actualizado vía eventos.

---

## FASE 5 — Infraestructura y Monitoreo (Día 9-10)

### 5.1 Habilitar OPCache

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### 5.2 Configurar JIT (PHP 8.x)

```ini
; php.ini
opcache.jit=1255
opcache.jit_buffer_size=100M
```

### 5.3 Optimizar Conexión MySQL

```ini
; my.cnf
innodb_buffer_pool_size = 1G     # 70-80% de la RAM disponible
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_type = 0              # Deshabilitar (deprecated en MySQL 8)
```

### 5.4 Monitoreo con Laravel Debugbar

En desarrollo, instalar `barryvdh/laravel-debugbar` para identificar N+1 queries visualmente.

### 5.5 Logging de Queries Lentas

```ini
; my.cnf
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 0.5
```

---

## FASE 6 — Arquitectura a Futuro (Mediano Plazo)

### 6.1 Desnormalizar `orders_count` en `food_stalls`

Agregar columna `orders_count` en `food_stalls` y actualizarla vía eventos de Eloquent:

```php
// app/Observers/OrderObserver.php
public function created(Order $order)
{
    $order->stall->increment('orders_count');
}
```

### 6.2 Vistas Materializadas para Reportes

Para comisiones y reportes de dashboard, crear tablas de resumen actualizadas por jobs nocturnos.

### 6.3 API Resource caching con `spatie/laravel-responsecache`

Cachear respuestas completas de endpoints públicos (menú, puestos) por 5-10 minutos:

```bash
composer require spatie/laravel-responsecache
```

---

## Resumen de Impacto Estimado

| Fase | Acción | Reducción de Latencia Estimada |
|------|--------|-------------------------------|
| 1 | Eager Loading + Paginación | 40-60% |
| 1 | Queue asíncrono | 80-90% en writes |
| 1 | Redis como cache driver | 20-30% |
| 2 | Índices compuestos | 50-80% en queries filtradas |
| 2 | Bounding Box Haversine | 90-95% en nearby |
| 3 | Caché de isOpenNow | 90-100% en status checks |
| 3 | Caché de conteos | 80-90% |
| 4 | Batch loading en crearPedido | 60-80% |
| 5 | OPCache + JIT | 15-25% |
| **Total Estimado** | | **85-95% de reducción** |

---

## Prioridad de Ejecución Recomendada

1. **Inmediato (hoy):** Fase 1 (Redis, Queue, Eager Loading, Paginación)
2. **En 1-2 días:** Fase 2 (Índices, Bounding Box, JOINs)
3. **En 3-4 días:** Fase 3 (Caché inteligente)
4. **En 5-6 días:** Fase 4 (Refactor de código)
5. **En 7-8 días:** Fase 5 (Infraestructura)
6. **Mediano plazo:** Fase 6 (Arquitectura)

---

## Checklist de Implementación

- [ ] Cambiar `CACHE_DRIVER=redis`
- [ ] Cambiar `QUEUE_CONNECTION=database`
- [ ] Agregar `with('stall')` en OrderController líneas 242, 348, 561
- [ ] Agregar `with('client')` en OrderController línea 348
- [ ] Reemplazar `items.product` por `withCount('items')` en líneas 456, 505
- [ ] Agregar `with('stall')` en MenuController líneas 504, 542
- [ ] Agregar `withCount('menuItems')` en MenuController línea 420
- [ ] Reemplazar N+1 por `whereIn` en DashboardController línea 64
- [ ] Paginar `listarMisPedidos` con `paginate(20)`
- [ ] Paginar `listarReglas` con `paginate(50)`
- [ ] Chunk `transferirComisiones` con jobs
- [ ] Cachear categorías en `obtenerCategorias`
- [ ] Ejecutar migración de índices compuestos
- [ ] Agregar bounding box en GeoController::nearby()
- [ ] Convertir `whereHas` a `JOIN` en CommissionController
- [ ] Agregar `Cache::remember` en `isOpenNow()`
- [ ] Batch loading en `crearPedido()`
- [ ] Migrar subquery a JOIN en GeoController::index()
- [ ] Iniciar worker: `php artisan queue:work database`
