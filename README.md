# 🍽️ ALTOQUE — API de Pedidos para Puestos de Comida

**ALTOQUE** es una plataforma backend construida con **Laravel 11** que conecta clientes con puestos de comida (food stalls) permitiendo explorar menús, realizar pedidos con geoposicionamiento, pagos asíncronos, comisiones automáticas y comprobantes electrónicos.

---

## 📋 TABLA DE CONTENIDOS

1. [Stack Tecnológico](#stack)
2. [Arquitectura](#arquitectura)
3. [Modelos de Datos](#modelos)
4. [API Endpoints](#endpoints)
5. [Autenticación y Roles](#auth)
6. [Servicios de Negocio](#servicios)
7. [Jobs en Cola](#jobs)
8. [Requerimientos Funcionales](#rf)
9. [Instalación y Configuración](#instalacion)
10. [Despliegue](#despliegue)
11. [Testing](#testing)
12. [Autores](#autores)

---

## <a name="stack"></a>1. STACK TECNOLÓGICO

| Componente | Tecnología | Versión |
|------------|-----------|---------|
| Framework | Laravel | 11.x |
| Lenguaje | PHP | 8.1+ |
| Base de Datos | MySQL | 8.0+ |
| Cache | Redis / File | — |
| Auth API | Laravel Sanctum | — |
| OAuth | Laravel Socialite (Google) | — |
| Cola de Jobs | Laravel Queue (database) | — |
| SMS | Twilio SDK | — |
| QR | SimpleSoftwareIO/QrCode | — |
| Facturación Electrónica | OSE Adapter (Mock) | — |

---

## <a name="arquitectura"></a>2. ARQUITECTURA

```
┌──────────────────────────────────────────────┐
│           FRONTEND (React/Vue/etc)            │
│          Aplicación Cliente/Mobile            │
└──────────────────────┬───────────────────────┘
                       │ HTTP/JSON + Bearer Token
┌──────────────────────▼───────────────────────┐
│         API GATEWAY (Nginx)                  │
│     SSL Termination · Load Balancing         │
└──────────────────────┬───────────────────────┘
                       │
┌──────────────────────▼───────────────────────┐
│         APPLICATION LAYER (Laravel 11)        │
│                                              │
│  ┌─────────────────────────────────────────┐ │
│  │         CONTROLLERS                     │ │
│  │  Auth · Stall · Menu · Order · Geo     │ │
│  │  Invoice · Commission · Dashboard       │ │
│  └─────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────┐ │
│  │          SERVICES (Lógica de Negocio)    │ │
│  │  Payment · Commission · Delivery        │ │
│  │  Invoice · Transfer · SMS · PDF · OSE   │ │
│  └─────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────┐ │
│  │       JOBS (Procesamiento Asíncrono)    │ │
│  │  ProcessPayment · GenerateInvoice      │ │
│  │  ExecuteTransfer                        │ │
│  └─────────────────────────────────────────┘ │
│  ┌─────────────────────────────────────────┐ │
│  │        MIDDLEWARE                       │ │
│  │  auth:sanctum · role · throttle         │ │
│  └─────────────────────────────────────────┘ │
└──────────────────────┬───────────────────────┘
                       │
         ┌─────────────┼─────────────┐
         │             │             │
┌────────▼───┐ ┌───────▼───────┐ ┌───▼──────────┐
│  MySQL 8.0 │ │    Redis      │ │ Queue (DB)   │
│ (Principal)│ │   (Cache)     │ │ (jobs table) │
└────────────┘ └───────────────┘ └──────────────┘
```

---

## <a name="modelos"></a>3. MODELOS DE DATOS

### 3.1 Core

| Modelo | Tabla | Propósito |
|--------|-------|-----------|
| `User` | `users` | Clientes, vendedores y admins |
| `FoodStall` | `food_stalls` | Puestos de comida con ubicación y horarios |
| `MenuItem` | `menu_items` | Productos del menú |
| `Category` | `categories` | Categorías de productos |
| `Topping` | `toppings` | Cremas/acompañamientos |
| `Order` | `orders` | Pedidos con estados y delivery |
| `OrderItem` | `order_items` | Items individuales del pedido |
| `Payment` | `payments` | Pagos con método y transacción |
| `StallPause` | `stall_pauses` | Pausas temporales del puesto |

### 3.2 Comisiones y Facturación

| Modelo | Tabla | Propósito |
|--------|-------|-----------|
| `CommissionRule` | `commission_rules` | Reglas de comisión configurables |
| `OrderCommission` | `order_commissions` | Comisión calculada por pedido |
| `CommissionAuditLog` | `commission_audit_logs` | Auditoría de cambios en reglas |
| `CompanyAccountEntry` | `company_account_entries` | Contabilidad (créditos/débitos) |
| `TransferAttempt` | `transfer_attempts` | Intentos de transferencia a vendor |
| `Invoice` | `invoices` | Comprobantes electrónicos |

### 3.3 Relaciones Principales

```
User (1) ──── (N) Order
User (1) ──── (1) FoodStall  [seller]
FoodStall (1) ──── (N) MenuItem
FoodStall (1) ──── (N) StallPause
MenuItem (N) ──── (N) Topping  [many-to-many con pivot]
Order (1) ──── (N) OrderItem
Order (1) ──── (1) Payment
OrderItem (N) ──── (1) MenuItem
Order (1) ──── (1) OrderCommission
CommissionRule (1) ──── (N) OrderCommission
```

### 3.4 Migraciones (29)

Las migraciones cubren desde la tabla `users` inicial hasta tablas de delivery, comisiones, facturación y contabilidad. Incluyen modificaciones sucesivas para agregar campos como `password_nullable`, `role`, `first_name`, `last_name`, `featured`, `image_path`, `toppings`, tiempos de preparación, `commission_rules`, `invoices` y `company_account_entries`.

---

## <a name="endpoints"></a>4. API ENDPOINTS

### 4.1 Autenticación (RF1-RF3)

| Método | Ruta | Middleware | Descripción |
|--------|------|-----------|-------------|
| POST | `/api/register` | — | Registro de usuario (role: client por defecto) |
| POST | `/api/login` | — | Inicio de sesión |
| POST | `/api/logout` | `auth:sanctum` | Cerrar sesión y revocar token |
| GET | `/api/user` | `auth:sanctum` | Obtener usuario autenticado |
| PUT | `/api/user/update` | `auth:sanctum` | Actualizar perfil |

### 4.2 Recuperación de Contraseña

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/send-otp` | Enviar OTP por SMS |
| POST | `/api/verify-otp` | Verificar código OTP |
| POST | `/api/reset-password` | Resetear contraseña |

### 4.3 Conversión a Vendedor

| Método | Ruta | Middleware | Descripción |
|--------|------|-----------|-------------|
| POST | `/api/convertirse-vendedor` | `auth:sanctum` + `role:client` | Cliente se convierte en vendedor |

### 4.4 Gestión del Puesto (RF6-RF10)

| Método | Ruta | Middleware | Descripción |
|--------|------|-----------|-------------|
| GET | `/api/mis-puesto` | `auth:sanctum` + `role:vendor` | Obtener mi puesto |
| POST | `/api/mis-puesto/generar-qr` | `auth:sanctum` + `role:vendor` | Generar QR del menú |
| GET | `/api/mis-puesto/horarios` | `auth:sanctum` + `role:vendor` | Obtener horarios |
| PUT | `/api/mis-puesto/horarios` | `auth:sanctum` + `role:vendor` | Actualizar horarios |
| POST | `/api/mis-puesto/pausas` | `auth:sanctum` + `role:vendor` | Crear pausa |
| GET | `/api/mis-puesto/pausas` | `auth:sanctum` + `role:vendor` | Listar pausas |
| PATCH | `/api/mis-puesto/pausas/{id}` | `auth:sanctum` + `role:vendor` | Actualizar pausa |
| DELETE | `/api/mis-puesto/pausas/{id}` | `auth:sanctum` + `role:vendor` | Eliminar pausa |
| PUT | `/api/mis-puesto/tiempos-preparacion` | `auth:sanctum` + `role:vendor` | Configurar tiempos de preparación |

### 4.5 Menú y Productos (RF7-RF9)

| Método | Ruta | Middleware | Descripción |
|--------|------|-----------|-------------|
| GET | `/api/mis-productos` | `auth:sanctum` + `role:vendor` | Listar productos del vendedor |
| GET | `/api/mis-productos/offline` | `auth:sanctum` + `role:vendor` | Menú offline (solo activos) |
| POST | `/api/mis-productos` | `auth:sanctum` + `role:vendor` | Crear producto |
| PATCH | `/api/mis-productos/{id}` | `auth:sanctum` + `role:vendor` | Actualizar producto |
| GET | `/api/mis-categorias` | `auth:sanctum` + `role:vendor` | Listar categorías |
| POST | `/api/mis-categorias` | `auth:sanctum` + `role:vendor` | Crear categoría |
| GET | `/api/mis-cremas` | `auth:sanctum` + `role:vendor` | Listar cremas/acompañamientos |
| POST | `/api/mis-cremas` | `auth:sanctum` + `role:vendor` | Crear crema |
| PATCH | `/api/mis-cremas/{id}` | `auth:sanctum` + `role:vendor` | Actualizar crema |
| DELETE | `/api/mis-cremas/{id}` | `auth:sanctum` + `role:vendor` | Eliminar crema |
| POST | `/api/mis-productos/{id}/cremas` | `auth:sanctum` + `role:vendor` | Asociar crema a producto |
| DELETE | `/api/mis-productos/{id}/cremas/{cremaId}` | `auth:sanctum` + `role:vendor` | Desasociar crema |
| GET | `/api/mis-productos/{id}/cremas` | `auth:sanctum` + `role:vendor` | Cremas de un producto |
| GET | `/api/menu/{stallId}` | — | Menú público (con caché) |

### 4.6 Pedidos y Pagos (RF11-RF17)

| Método | Ruta | Middleware | Descripción |
|--------|------|-----------|-------------|
| POST | `/api/pedidos` | `auth:sanctum` + `role:client` | Crear pedido (borrador) |
| POST | `/api/pedidos/{id}/pagar` | `auth:sanctum` + `role:client` | Pagar pedido (async) |
| GET | `/api/pedidos` | `auth:sanctum` + `role:client` | Listar mis pedidos |
| GET | `/api/pedidos/{id}` | `auth:sanctum` + `role:client` | Detalle del pedido + timeline |
| PATCH | `/api/pedidos/{id}/cancelar` | `auth:sanctum` + `role:client` | Cancelar pedido |
| PATCH | `/api/pedidos/{id}/cambiar-direccion` | `auth:sanctum` + `role:client` | Cambiar dirección de entrega |
| GET | `/api/mis-pedidos` | `auth:sanctum` + `role:vendor` | Pedidos activos del vendedor |
| PATCH | `/api/pedidos/{id}/cambiar-estado` | `auth:sanctum` + `role:vendor` | Avanzar estado del pedido |

### 4.7 Geo / Mapa (RF28-RF32)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/puestos` | Listar puestos con filtros |
| GET | `/api/puestos/nearby` | Puestos cercanos (Haversine) |
| GET | `/api/puestos/map-markers` | Marcadores ligeros para mapa |
| GET | `/api/puestos/{id}/status` | Estado (abierto/cerrado) + próxima apertura |
| GET | `/api/puestos/{id}/map` | Datos del puesto + menú |
| GET | `/api/puestos/{id}/menu` | Menú del puesto (alias) |
| GET | `/api/puestos/{id}/directions` | URL Google Maps Directions |
| GET | `/api/puestos/{id}/route` | Estimación de ruta y ETA |

### 4.8 Comisiones (RF18-RF20)

| Método | Ruta | Middleware | Descripción |
|--------|------|-----------|-------------|
| POST | `/api/comisiones/calcular` | `auth:sanctum` | Calcular comisión de un pedido |
| GET | `/api/comisiones/pedido/{orderId}` | `auth:sanctum` | Obtener comisión de pedido |
| GET | `/api/comisiones/reglas` | `auth:sanctum` + `role:admin` | Listar reglas de comisión |
| POST | `/api/comisiones/reglas` | `auth:sanctum` + `role:admin` | Crear regla |
| PUT | `/api/comisiones/reglas/{id}` | `auth:sanctum` + `role:admin` | Actualizar regla |
| DELETE | `/api/comisiones/reglas/{id}` | `auth:sanctum` + `role:admin` | Desactivar regla |
| GET | `/api/comisiones/reporte` | `auth:sanctum` + `role:admin` | Reporte por fechas |
| GET | `/api/comisiones/auditoria` | `auth:sanctum` + `role:admin` | Auditoría de cambios |
| POST | `/api/comisiones/transferir` | `auth:sanctum` + `role:admin` | Transferir comisiones a vendedores |

### 4.9 Comprobantes Electrónicos (RF21)

| Método | Ruta | Middleware | Descripción |
|--------|------|-----------|-------------|
| POST | `/api/comprobantes/generar/{orderId}` | `auth:sanctum` | Solicitar comprobante |
| GET | `/api/comprobantes` | `auth:sanctum` | Listar comprobantes |
| GET | `/api/comprobantes/{id}` | `auth:sanctum` | Detalle del comprobante |
| GET | `/api/comprobantes/{id}/pdf` | `auth:sanctum` | Descargar PDF |
| GET | `/api/comprobantes/{id}/xml` | `auth:sanctum` | Descargar XML |
| POST | `/api/comprobantes/{id}/reintentar` | `auth:sanctum` | Reintentar generación |

### 4.10 Dashboard

| Método | Ruta | Middleware | Descripción |
|--------|------|-----------|-------------|
| GET | `/api/dashboard` | `auth:sanctum` | Dashboard del usuario |

---

## <a name="auth"></a>5. AUTENTICACIÓN Y ROLES

### 5.1 Métodos de Autenticación

- **Email + Password**: Registro y login tradicional con Sanctum
- **Google OAuth**: Via `GoogleController` con Socialite
- **OTP por SMS**: Recuperación de contraseña mediante código SMS

### 5.2 Roles del Sistema

| Rol | Slug | Permisos |
|-----|------|----------|
| **Cliente** | `client` | Crear pedidos, pagar, ver historial, cancelar, cambiar dirección |
| **Vendedor** | `vendor` | Gestionar puesto, menú, cremas, horarios, cambiar estados de pedidos |
| **Admin** | `admin` | CRUD reglas de comisión, reportes, auditoría, transferencias |

### 5.3 Middleware de Autorización

- `auth:sanctum` — Protege rutas con token Bearer
- `role:client|vendor|admin` — Middleware `CheckRole` que verifica el campo `role` del usuario

---

## <a name="servicios"></a>6. SERVICIOS DE NEGOCIO

### PaymentService
Procesa pagos dentro de una transacción de BD:
1. Valida estado `pending` del pedido
2. Simula gateway de pago
3. Crea registro `Payment`
4. Actualiza pedido a `confirmed`
5. Registra entrada en cuenta institucional (`CompanyAccountEntry` crédito)
6. Calcula comisión (`CommissionService`)
7. Transfiere en tiempo real si está configurado
8. Emite comprobante si se solicitó

### CommissionService
Calcula comisiones aplicando reglas por prioridad:
1. **price_range** — Rango de precio del pedido
2. **category** — Categoría del primer producto
3. **vendor_type** — Tipo de vendedor
4. **base** — Regla por defecto (10%)

Soporta creación, actualización y desactivación de reglas con auditoría completa.

### DeliveryService
- Cálculo de distancia Haversine entre dos coordenadas
- Cálculo de costo de delivery basado en distancia y tarifa del puesto
- Tarifa mínima configurable

### InvoiceService
- Creación de comprobantes (boleta/factura)
- Cálculo de IGV (18%)
- Dispatch de job `GenerateElectronicInvoiceJob` para procesamiento asíncrono

### TransferService
- Transferencia de comisiones a vendedores vía adaptador (mock)
- Soporte para múltiples métodos (Yape, Plin, mock)

### OseAdapterMock
- Simula envío a OSE (Operador de Servicios Electrónicos)
- Genera XML de factura/boleta electrónica
- Retorna ticket y hash de SUNAT

### InvoicePdfGenerator
- Genera PDF profesional del comprobante electrónico

### SmsService
- Integración con Twilio para envío de SMS (OTP, notificaciones)

---

## <a name="jobs"></a>7. JOBS EN COLA

| Job | Queue | Propósito | Reintentos |
|-----|-------|-----------|------------|
| `ProcessPaymentJob` | `default` | Procesar pago en background | 3 intentos |
| `GenerateElectronicInvoiceJob` | `invoices` | Generar PDF/XML + enviar email | Configurable (default 3) |
| `ExecuteTransferJob` | `default` | Transferir comisión al vendedor | 3 intentos |

### Comandos Artisan

| Comando | Descripción |
|---------|-------------|
| `php artisan retry:failed-invoices` | Reintentar facturas fallidas |
| `php artisan transfer:pending-commissions` | Transferir comisiones pendientes |

---

## <a name="rf"></a>8. REQUERIMIENTOS FUNCIONALES

| RF | Nombre | Descripción | Endpoints |
|----|--------|-------------|-----------|
| RF1 | Registro | Registro de usuario con email + password | `POST /api/register` |
| RF2 | Login | Autenticación con credenciales | `POST /api/login` |
| RF3 | Logout | Cierre de sesión y revocación de token | `POST /api/logout` |
| RF4 | Dashboard | Vista de resumen para el usuario autenticado | `GET /api/dashboard` |
| RF5 | Recuperación | Restablecimiento de contraseña vía OTP por SMS | `send-otp`, `verify-otp`, `reset-password` |
| RF6 | Puesto | Gestión del puesto de comida con QR | `mis-puesto/*`, `generar-qr` |
| RF7 | Menú | CRUD de productos del menú con categorías | `mis-productos/*` |
| RF8 | Cremas | Gestión de cremas/acompañamientos | `mis-cremas/*` |
| RF9 | Offline | Descarga del menú para modo offline | `mis-productos/offline` |
| RF10 | Horarios | Configuración de horarios y pausas | `horarios/*`, `pausas/*` |
| RF11 | Pedido | Creación de pedido + pago asíncrono | `POST /api/pedidos`, `POST /pedidos/{id}/pagar` |
| RF12 | Tracking | Historial y detalle de pedidos con timeline | `GET /pedidos`, `GET /pedidos/{id}` |
| RF13 | Tiempo | Tiempo estimado de preparación y entrega | `tiempos-preparacion`, `estimated_delivery_at` |
| RF14 | Estados | Máquina de estados del pedido (vendedor) | `PATCH /pedidos/{id}/cambiar-estado` |
| RF15 | Cancelar | Cancelación de pedido por el cliente | `PATCH /pedidos/{id}/cancelar` |
| RF16 | Dirección | Cambio de dirección de entrega | `PATCH /pedidos/{id}/cambiar-direccion` |
| RF17 | Vendor | Lista de pedidos activos del vendedor | `GET /api/mis-pedidos` |
| RF18 | Comisiones | Cálculo y consulta de comisiones por pedido | `comisiones/calcular`, `comisiones/pedido/{id}` |
| RF19 | Reglas | CRUD de reglas de comisión (admin) | `comisiones/reglas/*` |
| RF20 | Transferir | Transferencia de comisiones a vendedores | `POST /comisiones/transferir` |
| RF21 | Facturación | Comprobantes electrónicos (boleta/factura) | `comprobantes/*` |
| RF28 | Lista | Listado público de puestos con filtros | `GET /api/puestos` |
| RF29 | Mapa | Marcadores ligeros para mapa | `GET /api/puestos/map-markers` |
| RF30 | Menú | Menú del puesto desde el mapa | `GET /api/puestos/{id}/menu` |
| RF31 | Estado | Estado del puesto (abierto/cerrado) | `GET /api/puestos/{id}/status` |
| RF32 | Nearby | Puestos cercanos con Haversine | `GET /api/puestos/nearby` |

---

## <a name="instalacion"></a>9. INSTALACIÓN Y CONFIGURACIÓN

### 9.1 Requisitos

- PHP 8.1+
- Composer 2.x
- MySQL 8.0+
- Node.js (opcional, para frontend)
- Redis (opcional, para cache)

### 9.2 Instalación

```bash
# Clonar repositorio
git clone <repo-url> altoque
cd altoque

# Instalar dependencias PHP
composer install

# Copiar configuración
cp .env.example .env
php artisan key:generate

# Configurar base de datos en .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=altoque
DB_USERNAME=root
DB_PASSWORD=

# Ejecutar migraciones
php artisan migrate

# Publicar recursos (QR, storage)
php artisan storage:link

# Optimizar (producción)
php artisan config:cache
php artisan route:cache
```

### 9.3 Variables de Entorno Clave

```
APP_NAME=ALTOQUE
APP_URL=http://localhost
FRONTEND_URL=http://localhost:3000

SANCTUM_STATEFUL_DOMAINS=localhost

QUEUE_CONNECTION=database
CACHE_DRIVER=file   # o redis

# Twilio (SMS)
TWILIO_SID=
TWILIO_AUTH_TOKEN=
TWILIO_VERIFY_SID=

# OSE / Facturación
INVOICING_MAX_RETRIES=3
INVOICING_ADMIN_EMAIL=admin@altoque.com

# Pagos
PAYMENTS_TRANSFER_MODE=scheduled   # o real_time
PAYMENTS_DEFAULT_TRANSFER_METHOD=mock

# Delivery
DELIVERY_RATE_PER_KM=3.00
DELIVERY_MIN_COST=3.00
```

### 9.4 Worker de Cola

```bash
# Iniciar worker para jobs de pago
php artisan queue:work --queue=default,invoices

# Supervisor config (producción)
[program:altoque-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/altoque/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=2
```

---

## <a name="despliegue"></a>10. DESPLIEGUE

### Entornos

| Entorno | URL | Base de Datos | Workers |
|---------|-----|---------------|---------|
| Desarrollo | `http://localhost` | Local | 1 |
| Staging | `https://staging-api.altoque.com` | `staging_db` | 1 |
| Producción | `https://api.altoque.com` | `prod_db` + Read Replicas | 2-4 |

### Checklist de Despliegue

1. Code review y tests pasando
2. Migraciones de BD verificadas
3. `.env` configurado con valores de producción
4. Cache configurada (Redis recomendado)
5. Configuración de Nginx validada
6. Certificado SSL actualizado (Let's Encrypt)
7. Workers de Supervisor reiniciados
8. Health checks pasando
9. Monitoreo activo (logs + alertas)
10. Procedimiento de rollback documentado

---

## <a name="testing"></a>11. TESTING

```bash
# Ejecutar tests
php artisan test

# Tests específicos
php artisan test --filter=OrderTest

# Testing manual con CURL (ver documentación en /ejemplos_requests/)
```

El proyecto incluye documentación detallada de testing:
- `CHECKLIST_TESTING.md` — Checklist completo
- `CURL_TESTING_COMPLETO.md` — Ejemplos CURL
- `POSTMAN_TESTING_COMPLETO.md` — Colección Postman
- `tests/Feature/` — Tests de funcionalidad
- `tests/Unit/` — Tests unitarios

---

## <a name="autores"></a>12. AUTORES

**ALTOQUE** — Plataforma de Pedidos para Puestos de Comida

- **Arquitecto / Desarrollador Principal:** MVP Dev Team
- **Versión:** 2.0 (Post-Fase 2 Optimizaciones)
- **Última actualización:** Febrero 2026
- **Licencia:** MIT

---

*Documentación generada a partir del código fuente. Para más detalle sobre módulos específicos, revisar la documentación técnica en `ARQUITECTURA_TECNICA.md` y los archivos `RF*_*.md` en la raíz del proyecto.*
