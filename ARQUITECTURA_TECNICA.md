# 🏗️ ARQUITECTURA TÉCNICA - MVP + FASE 2

**Versión:** 2.0 (Post-Fase 2 Optimizaciones)  
**Última actualización:** 3 de Febrero de 2026

---

## 📋 TABLA DE CONTENIDOS

1. [Stack Tecnológico](#stack)
2. [Arquitectura de Capas](#capas)
3. [Data Models](#modelos)
4. [Auth & Security](#auth)
5. [API Endpoints](#endpoints)
6. [Performance](#performance)
7. [Deployment](#deployment)

---

## <a name="stack"></a>1. STACK TECNOLÓGICO

### Backend
- **Framework:** Laravel 11
- **PHP:** 8.1+
- **Database:** MySQL 8.0+
- **Cache:** Redis
- **Queue:** Laravel Queue (database driver)
- **Auth:** Laravel Sanctum + OAuth2 (Socialite)
- **SMS:** Twilio SDK

### Frontend (Cliente)
- **Framework:** React/Vue (no especificado)
- **Auth:** Bearer token (Sanctum)
- **API:** REST HTTP/JSON

### DevOps
- **Server:** Ubuntu 22.04 LTS
- **Web Server:** Nginx
- **Process Manager:** Supervisor
- **SSL:** Let's Encrypt
- **Monitoring:** (Recomendado: NewRelic, DataDog)

---

## <a name="capas"></a>2. ARQUITECTURA DE CAPAS

```
┌──────────────────────────────────────────────────────────┐
│                  PRESENTATION LAYER                       │
│              (Frontend Web/Mobile Apps)                    │
└────────────────────────┬─────────────────────────────────┘
                         │ HTTP/REST
┌────────────────────────▼─────────────────────────────────┐
│              API GATEWAY LAYER                            │
│  ┌─────────────────────────────────────────────────────┐ │
│  │ Nginx (Load Balancer, SSL Termination)              │ │
│  └─────────────────────────────────────────────────────┘ │
└────────────────────────┬─────────────────────────────────┘
                         │
┌────────────────────────▼─────────────────────────────────┐
│           APPLICATION LAYER (Laravel)                     │
│  ┌──────────────────────────────────────────────────┐   │
│  │ Controllers (HTTP Requests)                       │   │
│  │ ├─ OrderController                              │   │
│  │ ├─ StallScheduleController                      │   │
│  │ ├─ UserController                               │   │
│  │ └─ ... (otros controllers)                      │   │
│  └──────────────────────────────────────────────────┘   │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │ Service Layer (Business Logic)                    │   │
│  │ ├─ PaymentService                               │   │
│  │ ├─ OrderService (calculaciones)                 │   │
│  │ ├─ AuthService                                   │   │
│  │ └─ ... (otros services)                         │   │
│  └──────────────────────────────────────────────────┘   │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │ Queue/Jobs (Background Processing)                │   │
│  │ ├─ ProcessPaymentJob                            │   │
│  │ └─ ... (otros jobs)                             │   │
│  └──────────────────────────────────────────────────┘   │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │ Middleware (Authentication, Validation)          │   │
│  │ ├─ Auth Guard (Sanctum)                         │   │
│  │ ├─ Role Authorization                           │   │
│  │ └─ CORS, Rate Limiting                          │   │
│  └──────────────────────────────────────────────────┘   │
└────────────────────────┬─────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        │                │                │
┌───────▼──────┐ ┌──────▼──────┐ ┌──────▼──────────┐
│  MySQL 8.0   │ │   Redis     │ │  Jobs Queue    │
│  (BD)        │ │   (Cache)   │ │  (DB)          │
│              │ │             │ │                │
│ - Orders     │ │ - Horarios  │ │ - Payment Jobs │
│ - Users      │ │ - Pausas    │ │ - Retry Queue  │
│ - Payments   │ │ - Sessions  │ │ - Failed Jobs  │
│ - Etc.       │ │             │ │                │
└──────────────┘ └─────────────┘ └────────────────┘
```

---

## <a name="modelos"></a>3. DATA MODELS

### Core Entities

```
┌─────────────────┐
│     User        │
├─────────────────┤
│ id              │
│ name            │
│ email (unique)  │
│ phone (unique)  │
│ role            │◄─────┐
│ password        │      │
│ profile_pic     │      │
│ created_at      │      │
└────────┬────────┘      │
         │               │ Many-to-One
         │ One-to-Many   │
         │               │
    ┌────▼─────────────────────────┬──────────────┐
    │                              │              │
    │                    ┌─────────▼───────┐     │
    │                    │   FoodStall     │     │
    │                    ├─────────────────┤     │
    │                    │ id              │     │
    │                    │ seller_id (FK)  │────┼─ seller
    │                    │ name            │     │
    │                    │ status          │     │
    │                    │ opening_time    │     │
    │                    │ closing_time    │     │
    │                    │ address         │     │
    │                    │ ...             │     │
    │                    └────────┬────────┘     │
    │                             │              │
    │ ┌──────────────────────────┼──────────────┼─ customer
    │ │ One-to-Many              │ Many-to-One  │
    │ │                           │              │
    │ ▼                           ▼              │
┌──────────────┐         ┌─────────────────┐   │
│   Order      │         │   MenuItem      │   │
├──────────────┤         ├─────────────────┤   │
│ id           │         │ id              │   │
│ user_id (FK) │◄────────│ stall_id (FK)   │   │
│ stall_id(FK) │         │ name            │   │
│ status       │         │ price           │   │
│ total        │         │ category        │   │
│ ...          │         │ ...             │   │
└──────┬───────┘         └─────────────────┘   │
       │ One-to-Many                            │
       │                                        │
       ▼                                        │
┌─────────────────┐     ┌────────────────┐    │
│  OrderItem      │────▶│   Topping      │    │
├─────────────────┤     ├────────────────┤    │
│ id              │     │ id             │    │
│ order_id (FK)   │     │ stall_id (FK)  │────┼─ stall
│ product_id (FK) │     │ name           │    │
│ quantity        │     │ price          │    │
│ price_per_unit  │     │ ...            │    │
│ toppings (JSON) │     └────────────────┘    │
└─────────────────┘                            │
       │ One-to-Many                           │
       │                                       │
       ▼                                       │
┌─────────────────┐   ┌──────────────────┐   │
│   Payment       │   │  StallPause      │   │
├─────────────────┤   ├──────────────────┤   │
│ id              │   │ id               │   │
│ order_id (FK)   │   │ stall_id (FK)    │───┼─ stall
│ method          │   │ reason           │   │
│ amount          │   │ start_at         │   │
│ status          │   │ end_at           │   │
│ transaction_id  │   │ is_active        │   │
│ ...             │   │ ...              │   │
└─────────────────┘   └──────────────────┘   │
                                              │
                                   └──────────┘
```

### Relaciones Principales
- **User → Order** (1:N): Cliente tiene muchos pedidos
- **FoodStall → Order** (1:N): Puesto recibe muchos pedidos
- **Order → OrderItem** (1:N): Pedido tiene múltiples items
- **MenuItem → OrderItem** (1:N): Producto en varios items
- **FoodStall → MenuItem** (1:N): Puesto vende múltiples items
- **FoodStall → StallPause** (1:N): Puesto tiene múltiples pausas
- **Order → Payment** (1:1): Cada orden tiene un pago

---

## <a name="auth"></a>4. AUTH & SECURITY

### Autenticación Multi-Canales
```
┌─────────────────────────────────────────┐
│         LOGIN ENDPOINTS                 │
├─────────────────────────────────────────┤
│                                         │
│ 1. Google OAuth                         │
│    POST /api/auth/google                │
│    Body: {token_google}                 │
│    → Crear/obtener usuario              │
│                                         │
│ 2. Email + Password                     │
│    POST /api/auth/email-login           │
│    Body: {email, password}              │
│    → Validar credenciales               │
│                                         │
│ 3. Phone + OTP                          │
│    POST /api/auth/phone                 │
│    Body: {phone}                        │
│    → Enviar SMS con OTP                 │
│    POST /api/auth/verify-otp            │
│    Body: {phone, otp_code}              │
│    → Verificar y autenticar             │
│                                         │
└─────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│    SANCTUM TOKEN GENERATION             │
│    (API Token Authentication)            │
├─────────────────────────────────────────┤
│                                         │
│ Retorna:                                │
│ {                                       │
│   "token": "abcd1234...",              │
│   "user": {...},                        │
│   "roles": ["cliente", "vendedor"]     │
│ }                                       │
│                                         │
└─────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│ BEARER TOKEN (Para requests)            │
│ Authorization: Bearer abcd1234...       │
│                                         │
│ ✅ Valida usuario                       │
│ ✅ Verifica permisos                    │
│ ✅ Aplica Rate Limiting                 │
│ ✅ Loguea actividades                   │
└─────────────────────────────────────────┘
```

### Roles & Permissions
```
Role: cliente
├─ POST /api/pedidos (crear pedido)
├─ POST /api/pedidos/{id}/pagar (pagar)
├─ GET /api/pedidos/{id} (ver pedido)
├─ GET /api/pedidos (listar mis pedidos)
├─ PATCH /api/pedidos/{id}/cancelar
└─ PATCH /api/pedidos/{id}/cambiar-direccion

Role: vendedor
├─ GET /api/mis-puesto/horarios
├─ PUT /api/mis-puesto/horarios
├─ POST /api/mis-puesto/pausas
├─ GET /api/mis-puesto/pausas
├─ PATCH /api/pedidos/{id}/cambiar-estado
└─ GET /api/mis-pedidos

Role: admin
└─ Todo (TBD)
```

### Security Layers
1. **SSL/TLS** - HTTPS en tránsito
2. **CORS** - Solo dominios autorizados
3. **CSRF** - Tokens para POST/PUT/PATCH/DELETE
4. **Rate Limiting** - 100 req/min por IP
5. **Input Validation** - Laravel Validator
6. **SQL Injection** - Prepared statements (Eloquent ORM)
7. **XSS Protection** - Output encoding

---

## <a name="endpoints"></a>5. API ENDPOINTS

### Clasificación por RF

**RF1-3: Authentication**
```
POST   /api/auth/google
POST   /api/auth/email-login
POST   /api/auth/phone
POST   /api/auth/verify-otp
POST   /api/auth/password-reset
```

**RF6: Puestos & QR**
```
GET    /api/puestos
GET    /api/puestos/{id}
GET    /api/puestos/{id}/qr
POST   /api/puestos (seller only)
```

**RF7: Menu Management**
```
GET    /api/mis-puesto/menu
POST   /api/mis-puesto/menu
PATCH  /api/menu/{id}
DELETE /api/menu/{id}
```

**RF8: Toppings/Accompaniments**
```
GET    /api/mis-puesto/cremas
POST   /api/mis-puesto/cremas
PATCH  /api/cremas/{id}
DELETE /api/cremas/{id}
```

**RF10: Hours & Pauses**
```
GET    /api/mis-puesto/horarios
PUT    /api/mis-puesto/horarios
POST   /api/mis-puesto/pausas
GET    /api/mis-puesto/pausas
PATCH  /api/mis-puesto/pausas/{id}
DELETE /api/mis-puesto/pausas/{id}
```

**RF11-12: Orders & Payments**
```
POST   /api/pedidos (create pending order)
POST   /api/pedidos/{id}/pagar (async payment)
GET    /api/pedidos (list my orders)
GET    /api/pedidos/{id} (detail + timeline)
```

**RF13: Time Estimation**
```
PUT    /api/mis-puesto/tiempos-preparacion
(GET estimado_delivery_at en pedidos)
```

**RF14: Order Status**
```
PATCH  /api/pedidos/{id}/cambiar-estado
(estados: pending→confirmed→preparing→ready→en_camino→delivered)
```

**RF15-16: Cancel & Modify**
```
PATCH  /api/pedidos/{id}/cancelar
PATCH  /api/pedidos/{id}/cambiar-direccion
```

**RF17: Vendor Active Orders**
```
GET    /api/mis-pedidos
(solo: confirmed, preparing, ready)
```

**Testing**
```
GET    /api/test-token?user_type=cliente|vendedor
```

---

## <a name="performance"></a>6. PERFORMANCE

### Request Lifecycle
```
1. CLIENTE REQUEST
   ├─ HTTP GET /api/pedidos/1
   └─ Header: Authorization: Bearer token...

2. NGINX (Load Balancer)
   ├─ SSL Termination
   ├─ Routing
   └─ Rate Limiting

3. LARAVEL (App)
   ├─ Middleware Chain
   │  ├─ Authentication (Sanctum)
   │  ├─ Authorization (Role Check)
   │  └─ Validation
   ├─ Controller
   │  ├─ Cache Check (Redis)
   │  │  ├─ HIT → Retornar respuesta (5ms) ⚡
   │  │  └─ MISS → Continuar
   │  ├─ Service Logic
   │  └─ Database Query (con índices)
   ├─ Response Formatting
   └─ JSON Response

4. NGINX (Compression)
   ├─ Gzip compression
   └─ Send response

5. CLIENTE RECEIVES RESPONSE (~5ms-200ms)
```

### Caching Strategy
```
┌─────────────────────────────────────┐
│  Cacheable Endpoints (READ-only)    │
├─────────────────────────────────────┤
│                                     │
│ GET /api/mis-puesto/horarios        │ TTL: 3600s
│ GET /api/mis-puesto/pausas          │ TTL: 600s
│ GET /api/puestos                    │ TTL: 1800s
│ GET /api/puestos/{id}               │ TTL: 1800s
│                                     │
├─────────────────────────────────────┤
│  Cache Invalidation Events          │
├─────────────────────────────────────┤
│                                     │
│ PUT /api/mis-puesto/horarios        │ Invalida: horarios_puesto_*
│ POST/PATCH/DELETE /pausas           │ Invalida: pausas_puesto_*
│ POST /menu                          │ Invalida: menu_puesto_*
│                                     │
└─────────────────────────────────────┘
```

### Queue System (Async Processing)
```
SYNC (Old - Bloquea):
POST /api/pedidos/{id}/pagar
    ↓ (2-3 segundos esperando)
    └─→ Procesa pago (DB writes, API calls)
    ↓
Response al cliente


ASYNC (Nuevo - No bloquea):
POST /api/pedidos/{id}/pagar
    ↓ (<100ms - encolamos job)
    └─→ ProcessPaymentJob::dispatch()
    ├─→ Job guardado en tabla 'jobs'
    ├─→ Response inmediata HTTP 202
    │
    └─ Background Worker
        ├─ Ejecuta job (async)
        ├─ Procesa pago
        ├─ Escribe BD
        ├─ Reintentos si falla
        └─ Completa sin bloquear
```

### Capacidad Mejorada
```
ANTES (Síncrono):
├─ CPU: 100% en procesamiento de pagos
├─ Conexiones DB: 20/50 usadas (40%)
├─ Pagos simultáneos: 10-20
├─ Pagos por día: ~200 (tx/8h = 25 tx/h)
└─ Latencia: 2-3 segundos

DESPUÉS (Async + Cache):
├─ CPU: 30% en procesamiento (resto en queue)
├─ Conexiones DB: 5/50 usadas (10%)
├─ Pagos simultáneos: 100+
├─ Pagos por día: 3-5K (tx/24h = 125-200 tx/h)
└─ Latencia: <100ms
```

---

## <a name="deployment"></a>7. DEPLOYMENT

### Staging Environment
```
URL: https://staging-api.tudominio.com
├─ Database: staging_db (MySQL)
├─ Redis: localhost:6379
├─ Queue Workers: 1
├─ Auto-deploy: Desde rama 'develop'
└─ Monitoring: Logs + email alerts
```

### Production Environment
```
URL: https://api.tudominio.com
├─ Database: prod_db (MySQL) + Read Replicas
├─ Redis: ElastiCache (AWS)
├─ Queue Workers: 2-4
├─ Load Balancer: Nginx (3+ servidores)
├─ SSL: Let's Encrypt (auto-renew)
├─ Monitoring: NewRelic/DataDog
├─ Backups: Nightly (incremental)
└─ CI/CD: GitHub Actions
```

### Deployment Checklist
```bash
1. Code review & testing ✓
2. Database migrations tested ✓
3. .env configurado ✓
4. Redis accessible ✓
5. Nginx config validated ✓
6. SSL certificate updated ✓
7. Supervisor restarted ✓
8. Health checks passing ✓
9. Monitoring alerts active ✓
10. Rollback procedure documented ✓
```

---

## 📊 RESUMEN ARQUITECTÓNICO

| Componente | Tecnología | Propósito | Escalabilidad |
|---|---|---|---|
| API Gateway | Nginx | Load balancing, SSL | ⭐⭐⭐⭐⭐ |
| App Server | Laravel 11 | Lógica negocio | ⭐⭐⭐⭐ |
| Database | MySQL 8 | Persistencia | ⭐⭐⭐⭐ |
| Cache Layer | Redis | Performance | ⭐⭐⭐⭐⭐ |
| Queue System | Laravel Queue | Async Jobs | ⭐⭐⭐⭐⭐ |
| Auth | Sanctum + OAuth | Security | ⭐⭐⭐⭐ |

---

**Última actualización:** 3 de Febrero de 2026  
**Arquitecto:** MVP Dev Team  
**Estado:** Production Ready
