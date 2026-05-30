# DOCUMENTACIÓN BACKEND - PROYECTO ALTOQUE

**Versión:** 1.0  
**Última actualización:** Mayo 2026  
**Framework:** Laravel 11  
**Base de Datos:** MySQL  
**API Base URL:** `http://localhost:8000/api`

---

## TABLA DE CONTENIDOS

1. [Descripción General](#descripción-general)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Autenticación y Autorización](#autenticación-y-autorización)
4. [Estructura de Base de Datos](#estructura-de-base-de-datos)
5. [Modelos y Relaciones](#modelos-y-relaciones)
6. [Endpoints por Funcionalidad](#endpoints-por-funcionalidad)
7. [Servicios y Jobs](#servicios-y-jobs)
8. [Flujos Principales](#flujos-principales)
9. [Respuestas y Códigos HTTP](#respuestas-y-códigos-http)
10. [Integración con Flutter](#integración-con-flutter)

---

## DESCRIPCIÓN GENERAL

**Altoque** es una plataforma digital para la gestión de pedidos, entregas y comisiones de puestos de comida (food stalls). El backend está construido con **Laravel 11** y proporciona una API REST completa para:

- **Clientes**: Buscar puestos, ver menús, crear pedidos, pagar en línea, rastrear entregas
- **Vendedores**: Gestionar menú, horarios, pausas, ver pedidos, recibir comisiones
- **Administradores**: Gestionar reglas de comisión, reportes, auditoría

**Funcionalidades principales implementadas:**
- RF4: Dashboard
- RF6-RF9: Gestión de menú, categorías y cremas (acompañamientos), menú offline
- RF10: Horarios y pausas de puestos
- RF11-RF12: Crear y listar pedidos con detalles
- RF13: Tiempo estimado de entrega
- RF14-RF16: Estados de pedidos, cancelación, cambio de dirección
- RF17: Pedidos del vendedor
- RF18-RF20: Sistema de comisiones con auditoría y transferencias
- RF21: Comprobantes electrónicos (facturas/boletas)
- RF28-RF32: Geolocalización, búsqueda por mapa, rutas, estado del puesto

---

## STACK TECNOLÓGICO

| Componente | Tecnología | Versión |
|---|---|---|
| **Framework** | Laravel | 11 |
| **Lenguaje** | PHP | 8.2+ |
| **Base de Datos** | MySQL | 5.7+ |
| **Autenticación** | Laravel Sanctum | - |
| **API Tokens** | JWT (Sanctum) | - |
| **Almacenamiento** | Local (S3 ready) | - |
| **Cola Asíncrona** | Database/Redis | - |
| **SMS** | Twilio | - |
| **Email** | SMTP/Mailtrap | - |
| **ORM** | Eloquent | - |
| **Testing** | PHPUnit | 9+ |

**Dependencias principales:**
```json
{
  "laravel/framework": "^11.0",
  "laravel/sanctum": "^4.0",
  "laravel/tinker": "^2.0",
  "twilio/sdk": "^9.0",
  "simpleSoftwareIO/simple-qrcode": "^4.0"
}
```

---

## AUTENTICACIÓN Y AUTORIZACIÓN

### 1. Registro de Usuario

**POST** `/api/auth/register`

Registra un nuevo usuario como cliente (customer).

**Body:**
```json
{
  "name": "Juan García",
  "email": "juan@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

**Respuesta (201 Created):**
```json
{
  "user": {
    "id": 1,
    "name": "Juan García",
    "email": "juan@example.com",
    "role": "customer",
    "created_at": "2026-05-21T10:00:00Z"
  },
  "message": "Usuario registrado exitosamente"
}
```

**Errores:**
- `422`: Email ya existe o validación falla
- `500`: Error interno

---

### 2. Login

**POST** `/api/auth/login`

Autentica al usuario y devuelve token API.

**Body:**
```json
{
  "email": "juan@example.com",
  "password": "SecurePass123!"
}
```

**Respuesta (200 OK):**
```json
{
  "user": {
    "id": 1,
    "name": "Juan García",
    "email": "juan@example.com",
    "role": "customer",
    "phone": "+51987654321",
    "dni": "12345678",
    "address": "Av. Principal 123",
    "district": "San Isidro",
    "department": "Lima"
  },
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Headers requeridos en peticiones posteriores:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Accept: application/json
Content-Type: application/json
```

**Errores:**
- `401`: Credenciales inválidas
- `422`: Validación falla

---

### 3. Actualizar Datos de Usuario

**PUT** `/api/user/update`

*Requiere autenticación*

**Body:**
```json
{
  "phone": "+51987654321",
  "dni": "12345678",
  "address": "Av. Principal 123",
  "district": "San Isidro",
  "department": "Lima"
}
```

**Respuesta (200 OK):**
```json
{
  "message": "Datos actualizados correctamente",
  "user": {
    "id": 1,
    "name": "Juan García",
    "email": "juan@example.com",
    "phone": "+51987654321",
    "dni": "12345678",
    "address": "Av. Principal 123",
    "district": "San Isidro",
    "department": "Lima"
  }
}
```

---

### 4. Recuperar Contraseña (OTP)

**POST** `/api/send-otp`

Envía código OTP al teléfono del usuario.

**Body:**
```json
{
  "phone": "+51987654321"
}
```

**Respuesta (200 OK):**
```json
{
  "message": "OTP enviado correctamente"
}
```

---

**POST** `/api/verify-otp`

Verifica el OTP enviado.

**Body:**
```json
{
  "phone": "+51987654321",
  "otp": "123456"
}
```

**Respuesta (200 OK):**
```json
{
  "message": "OTP verificado. Proceda a cambiar la contraseña"
}
```

---

**POST** `/api/reset-password`

Establece nueva contraseña.

**Body:**
```json
{
  "phone": "+51987654321",
  "otp": "123456",
  "password": "NewSecurePass123!",
  "password_confirmation": "NewSecurePass123!"
}
```

**Respuesta (200 OK):**
```json
{
  "message": "Contraseña restablecida correctamente"
}
```

---

### 5. Roles y Permisos

**Roles disponibles:**
- `customer`: Cliente que compra comida
- `vendor`: Vendedor que gestiona un puesto
- `admin`: Administrador del sistema

**Middleware de autorización:**

```php
// Solo autenticado
Route::middleware('auth:sanctum')->get('/user', ...);

// Solo cliente
Route::middleware(['auth:sanctum', 'role:customer'])->post('/orders', ...);

// Solo vendedor
Route::middleware(['auth:sanctum', 'role:vendor'])->get('/mis-puesto', ...);

// Solo admin
Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin/reports', ...);
```

---

## ESTRUCTURA DE BASE DE DATOS

### Tablas Principales

```sql
users (Usuarios del sistema)
├── id (PK)
├── name, email, password
├── phone, dni
├── first_name, last_name
├── address, district, department
├── role (customer|vendor|admin)
├── profile_picture
├── created_at, updated_at

food_stalls (Puestos de comida)
├── id (PK)
├── seller_id (FK → users.id)
├── name, slug
├── address, phone
├── latitude, longitude
├── opening_time, closing_time (HH:mm)
├── description
├── qr_path (Ubicación del QR)
├── active (boolean)
├── delivery_rate_per_km (Tarifa delivery por km)
├── delivery_min_cost (Costo mínimo delivery)
├── created_at, updated_at

menu_items (Productos del menú)
├── id (PK)
├── stall_id (FK → food_stalls.id)
├── category_id (FK → categories.id)
├── name, description
├── price (Decimal)
├── active, featured (booleans)
├── image_path
├── created_at, updated_at

categories (Categorías de productos)
├── id (PK)
├── name

toppings (Acompañamientos/Cremas)
├── id (PK)
├── stall_id (FK → food_stalls.id)
├── name, description
├── price (Decimal)
├── active (boolean)
├── created_at, updated_at

menu_item_topping (Relación muchos a muchos)
├── id (PK)
├── menu_item_id (FK)
├── topping_id (FK)
├── required (boolean)

stall_pauses (Pausas temporales del puesto)
├── id (PK)
├── stall_id (FK → food_stalls.id)
├── reason, start_at, end_at
├── is_active (boolean)
├── created_at, updated_at

orders (Pedidos)
├── id (PK)
├── user_id (FK → users.id)
├── stall_id (FK → food_stalls.id)
├── status (pending|confirmed|preparing|ready|en_camino|delivered|cancelled)
├── subtotal, igv, delivery_cost, total (Decimals)
├── payment_method (mock|yape|plin)
├── payment_id (FK → payments.id)
├── delivery_address
├── delivery_latitude, delivery_longitude
├── delivery_distance_km
├── client_notes
├── estimated_delivery_at, delivered_at
├── confirmed_at, preparing_at, ready_at, en_camino_at (Timestamps)
├── invoice_requested, invoice_type, customer_document_type
├── customer_document_number, customer_name
├── created_at, updated_at

order_items (Items de un pedido)
├── id (PK)
├── order_id (FK → orders.id)
├── product_id (FK → menu_items.id)
├── quantity, price_per_unit, subtotal
├── toppings (JSON con datos de cremas solicitadas)
├── created_at, updated_at

payments (Registros de pago)
├── id (PK)
├── order_id (FK → orders.id)
├── method (mock|yape|plin)
├── amount, status (completed|failed|pending)
├── transaction_id
├── response (JSON)
├── error_message
├── created_at, updated_at

invoices (Comprobantes electrónicos)
├── id (PK)
├── order_id (FK → orders.id)
├── seller_id (FK → users.id)
├── type (boleta|factura)
├── customer_name, customer_document_type, customer_document_number
├── subtotal, igv, total
├── pdf_path, xml_path (Ubicación en storage)
├── ose_ticket, ose_hash (De OSE)
├── status (pending|generated|failed)
├── attempts, error_message
├── created_at, updated_at

commission_rules (Reglas de comisión)
├── id (PK)
├── name, type (base|category|price_range|vendor_type)
├── commission_percentage (10-25%)
├── category_id, min_amount, max_amount
├── vendor_type, valid_from, valid_until
├── is_active, created_by, notes
├── created_at, updated_at

order_commissions (Comisión por pedido)
├── id (PK)
├── order_id (FK → orders.id)
├── commission_rule_id (FK → commission_rules.id)
├── order_total, commission_percentage, commission_amount
├── net_amount, status (pending|completed|refunded)
├── rule_applied, calculated_at, transferred_at
├── transfer_method, transfer_transaction_id
├── created_at, updated_at

commission_audit_logs (Auditoría)
├── id (PK)
├── commission_rule_id (FK)
├── action, old_values (JSON), new_values (JSON)
├── changed_by, ip_address, notes
├── created_at

company_account_entries (Ledger de empresa)
├── id (PK)
├── type (credit|debit)
├── amount, balance_after
├── description, order_id, reference
├── created_at

transfer_attempts (Intentos de transferencia)
├── id (PK)
├── order_commission_id (FK)
├── method, transaction_id, status
├── response (JSON), attempt
├── created_at
```

---

## MODELOS Y RELACIONES

### User

```php
// Relaciones
User::where('id', 1)->foodStall();  // El puesto del vendedor
User::where('id', 1)->orders();     // Los pedidos del cliente
```

### FoodStall

```php
// Relaciones
FoodStall::find(1)->owner();           // User (vendedor)
FoodStall::find(1)->menuItems();       // MenuItem[]
FoodStall::find(1)->toppings();        // Topping[]
FoodStall::find(1)->pauses();          // StallPause[]
FoodStall::find(1)->orders();          // Order[]

// Métodos útiles
$stall->isOpenNow();       // true si está abierto ahora
$stall->isInPauseNow();    // true si hay pausa activa
```

### Order

```php
// Relaciones
Order::find(1)->client();              // User (cliente)
Order::find(1)->stall();               // FoodStall
Order::find(1)->items();               // OrderItem[]
Order::find(1)->payment();             // Payment
Order::find(1)->invoice();             // Invoice
Order::find(1)->commission();          // OrderCommission
```

### MenuItem

```php
// Relaciones
MenuItem::find(1)->stall();            // FoodStall
MenuItem::find(1)->category();         // Category
MenuItem::find(1)->toppings();         // Topping[] (many-to-many)
```

---

## ENDPOINTS POR FUNCIONALIDAD

### RF4: DASHBOARD

**GET** `/api/dashboard`

*Requiere autenticación*

Obtiene estadísticas personalizadas según rol del usuario.

**Parámetros:**
- Ninguno (el rol se detecta del usuario autenticado)

**Respuesta (200 OK):**

*Para Cliente:*
```json
{
  "role": "customer",
  "total_orders": 15,
  "pending_orders": 2,
  "total_spent": 450.50,
  "favorite_stalls": [
    {
      "id": 1,
      "name": "Cevichería Mar Azul",
      "orders_count": 5
    }
  ]
}
```

*Para Vendedor:*
```json
{
  "role": "vendor",
  "stall": {
    "id": 1,
    "name": "Mi Puesto",
    "status": "open"
  },
  "today_orders": 12,
  "today_revenue": 500.00,
  "pending_preparation": 3,
  "commissions_pending": 200.00,
  "commissions_transferred": 1500.00
}
```

---

### RF6-RF9: GESTIÓN DE MENÚ Y ACOMPAÑAMIENTOS

#### RF6: Crear Puesto y Gestionar Categorías

**POST** `/api/mis-categorias`

*Requiere autenticación como vendedor*

Crea una nueva categoría.

**Body:**
```json
{
  "nombre": "Ceviches"
}
```

**Respuesta (201 Created):**
```json
{
  "id": 5,
  "nombre": "Ceviches",
  "puesto_id": 1,
  "creada_en": "2026-05-21T10:30:00Z"
}
```

---

**GET** `/api/mis-categorias`

Obtiene todas las categorías del vendedor.

**Respuesta (200 OK):**
```json
{
  "datos": [
    {
      "id": 1,
      "nombre": "Ceviches",
      "cantidad_productos": 5
    },
    {
      "id": 2,
      "nombre": "Bebidas",
      "cantidad_productos": 3
    }
  ]
}
```

---

#### RF7: Crear y Gestionar Productos

**POST** `/api/mis-productos`

*Requiere autenticación como vendedor*

Crea un nuevo producto.

**Body:**
```json
{
  "nombre": "Ceviche de Pescado",
  "descripcion": "Fresco ceviche de pescado blanco",
  "precio": 35.50,
  "categoria_id": 1,
  "destacado": true,
  "imagen": "base64_encoded_image_or_url"
}
```

**Respuesta (201 Created):**
```json
{
  "id": 10,
  "nombre": "Ceviche de Pescado",
  "descripcion": "Fresco ceviche de pescado blanco",
  "precio": 35.50,
  "categoria_id": 1,
  "activo": true,
  "destacado": true,
  "imagen_ruta": "/storage/images/producto_10.jpg",
  "cremas": [],
  "creado_en": "2026-05-21T10:30:00Z"
}
```

---

**PATCH** `/api/mis-productos/{id}`

*Requiere autenticación como vendedor*

Actualiza un producto.

**Body:**
```json
{
  "nombre": "Ceviche Premium",
  "precio": 39.99,
  "activo": true
}
```

**Respuesta (200 OK):**
```json
{
  "id": 10,
  "nombre": "Ceviche Premium",
  "precio": 39.99,
  "activo": true,
  "actualizado_en": "2026-05-21T10:45:00Z"
}
```

---

**GET** `/api/mis-productos`

Obtiene todos los productos del vendedor.

**Parámetros:**
- `categoria_id` (optional): Filtrar por categoría

**Respuesta (200 OK):**
```json
{
  "puesto_id": 1,
  "puesto_nombre": "Mi Puesto",
  "productos": [
    {
      "id": 10,
      "nombre": "Ceviche de Pescado",
      "precio": 35.50,
      "categoria_id": 1,
      "categoria": "Ceviches",
      "activo": true,
      "destacado": true,
      "imagen": "/storage/images/producto_10.jpg",
      "cremas_disponibles": 2
    }
  ]
}
```

---

#### RF8: Crear y Gestionar Cremas/Acompañamientos

**POST** `/api/mis-cremas`

*Requiere autenticación como vendedor*

Crea una nueva crema/acompañamiento.

**Body:**
```json
{
  "nombre": "Leche de Tigre",
  "descripcion": "Marinada especial",
  "precio": 5.00
}
```

**Respuesta (201 Created):**
```json
{
  "id": 15,
  "nombre": "Leche de Tigre",
  "precio": 5.00,
  "activa": true,
  "creada_en": "2026-05-21T10:30:00Z"
}
```

---

**GET** `/api/mis-cremas`

Obtiene todas las cremas del vendedor.

**Respuesta (200 OK):**
```json
{
  "cremas": [
    {
      "id": 15,
      "nombre": "Leche de Tigre",
      "precio": 5.00,
      "activa": true
    }
  ]
}
```

---

**POST** `/api/mis-productos/{productoId}/cremas`

Asocia una crema a un producto.

**Body:**
```json
{
  "crema_id": 15,
  "requerida": false
}
```

**Respuesta (201 Created):**
```json
{
  "producto_id": 10,
  "crema_id": 15,
  "crema_nombre": "Leche de Tigre",
  "requerida": false,
  "precio": 5.00
}
```

---

**GET** `/api/mis-productos/{productoId}/cremas`

Obtiene las cremas asociadas a un producto.

**Respuesta (200 OK):**
```json
{
  "producto_id": 10,
  "cremas": [
    {
      "id": 15,
      "nombre": "Leche de Tigre",
      "precio": 5.00,
      "requerida": false
    },
    {
      "id": 16,
      "nombre": "Salsa Picante",
      "precio": 0,
      "requerida": false
    }
  ]
}
```

---

**DELETE** `/api/mis-productos/{productoId}/cremas/{cremaId}`

Desasocia una crema de un producto.

**Respuesta (200 OK):**
```json
{
  "mensaje": "Crema desasociada correctamente"
}
```

---

#### RF9: Descargar Menú Offline

**GET** `/api/mis-productos/offline`

*Requiere autenticación como vendedor*

Descarga el menú completo (solo productos activos) para disponibilidad offline.

**Respuesta (200 OK):**
```json
{
  "puesto_id": 1,
  "puesto_nombre": "Mi Puesto",
  "productos": [
    {
      "id": 10,
      "nombre": "Ceviche de Pescado",
      "descripcion": "Fresco ceviche...",
      "precio": 35.50,
      "categoria": "Ceviches",
      "categoria_id": 1,
      "destacado": true,
      "imagen": "/storage/images/producto_10.jpg",
      "cremas": [
        {
          "id": 15,
          "nombre": "Leche de Tigre",
          "precio_adicional": 5.00,
          "requerida": false
        }
      ]
    }
  ]
}
```

---

**GET** `/api/menu/{stallId}`

*Sin autenticación*

Obtiene el menú público de un puesto.

**Parámetros:**
- `stallId`: ID del puesto

**Respuesta (200 OK):**
```json
{
  "puesto": {
    "id": 1,
    "nombre": "Mi Puesto",
    "descripcion": "Puesto de cevichería",
    "horario_apertura": "10:00",
    "horario_cierre": "22:00",
    "abierto_ahora": true
  },
  "categorias": [
    {
      "id": 1,
      "nombre": "Ceviches",
      "productos": [
        {
          "id": 10,
          "nombre": "Ceviche de Pescado",
          "precio": 35.50,
          "imagen": "/storage/images/producto_10.jpg",
          "cremas": [
            {
              "id": 15,
              "nombre": "Leche de Tigre",
              "precio_adicional": 5.00
            }
          ]
        }
      ]
    }
  ]
}
```

---

### RF10: HORARIOS Y PAUSAS

**GET** `/api/mis-puesto/horarios`

*Requiere autenticación como vendedor*

Obtiene los horarios actuales del puesto.

**Respuesta (200 OK):**
```json
{
  "puesto_id": 1,
  "puesto_nombre": "Mi Puesto",
  "horario": {
    "apertura": "10:00",
    "cierre": "22:00",
    "activo": true
  },
  "estado_actual": {
    "abierto_ahora": true,
    "en_pausa_ahora": false,
    "hora_actual": "15:30:45"
  }
}
```

---

**PUT** `/api/mis-puesto/horarios`

Actualiza los horarios del puesto.

**Body:**
```json
{
  "horario_apertura": "09:00",
  "horario_cierre": "23:00",
  "activo": true
}
```

**Respuesta (200 OK):**
```json
{
  "mensaje": "Horarios actualizados exitosamente",
  "horario": {
    "apertura": "09:00",
    "cierre": "23:00",
    "activo": true
  }
}
```

---

**POST** `/api/mis-puesto/pausas`

Crea una pausa temporal en el puesto.

**Body:**
```json
{
  "razon": "Mantenimiento de equipos",
  "inicio": "2026-05-21 14:00",
  "fin": "2026-05-21 15:00"
}
```

**Respuesta (201 Created):**
```json
{
  "id": 5,
  "razon": "Mantenimiento de equipos",
  "inicio": "2026-05-21T14:00:00Z",
  "fin": "2026-05-21T15:00:00Z",
  "activa": true,
  "creada_en": "2026-05-21T13:50:00Z"
}
```

---

**GET** `/api/mis-puesto/pausas`

Obtiene todas las pausas del puesto.

**Respuesta (200 OK):**
```json
{
  "pausas": [
    {
      "id": 5,
      "razon": "Mantenimiento de equipos",
      "inicio": "2026-05-21T14:00:00Z",
      "fin": "2026-05-21T15:00:00Z",
      "activa": true
    }
  ]
}
```

---

**PATCH** `/api/mis-puesto/pausas/{id}`

Actualiza una pausa.

**Body:**
```json
{
  "fin": "2026-05-21 14:30"
}
```

**Respuesta (200 OK):**
```json
{
  "id": 5,
  "fin": "2026-05-21T14:30:00Z",
  "actualizada_en": "2026-05-21T13:55:00Z"
}
```

---

**DELETE** `/api/mis-puesto/pausas/{id}`

Elimina una pausa.

**Respuesta (204 No Content):** (Sin body)

---

**PUT** `/api/mis-puesto/tiempos-preparacion`

*RF13: Configura tiempos de preparación*

Establece tiempos estimados de preparación por número de items.

**Body:**
```json
{
  "tiempo_base_minutos": 5,
  "tiempo_por_item_minutos": 2,
  "tiempo_delivery_minutos": 10
}
```

**Respuesta (200 OK):**
```json
{
  "mensaje": "Tiempos actualizados exitosamente",
  "configuracion": {
    "tiempo_base_minutos": 5,
    "tiempo_por_item_minutos": 2,
    "tiempo_delivery_minutos": 10
  }
}
```

---

### RF11-RF12: PEDIDOS Y PAGOS

#### Crear Pedido

**POST** `/api/pedidos`

*Requiere autenticación como cliente*

Crea un nuevo pedido sin pagar (estado: pending).

**Body:**
```json
{
  "stall_id": 1,
  "productos": [
    {
      "producto_id": 10,
      "cantidad": 2,
      "cremas": [15, 16]
    },
    {
      "producto_id": 11,
      "cantidad": 1,
      "cremas": []
    }
  ],
  "direccion": "Av. Principal 123, San Isidro",
  "with_delivery": true,
  "delivery_latitude": -12.1234,
  "delivery_longitude": -77.0123,
  "notas": "Sin picante",
  "invoice_requested": true,
  "invoice_type": "boleta",
  "customer_document_type": "DNI",
  "customer_document_number": "12345678",
  "customer_name": "Juan García"
}
```

**Respuesta (201 Created):**
```json
{
  "mensaje": "Pedido creado. Procede al pago.",
  "orden": {
    "id": 42,
    "estado": "pending",
    "puesto_nombre": "Cevichería Mar Azul",
    "subtotal": 100.00,
    "igv": 18.00,
    "delivery": 10.00,
    "total": 128.00,
    "direccion_entrega": "Av. Principal 123, San Isidro"
  },
  "items_pedido": [
    {
      "producto_id": 10,
      "cantidad": 2,
      "precio_unitario": 35.50,
      "subtotal": 71.00,
      "cremas": [
        {
          "id": 15,
          "nombre": "Leche de Tigre",
          "precio": 5.00
        }
      ]
    }
  ],
  "siguiente_paso": "POST /api/pedidos/42/pagar"
}
```

---

#### Pagar Pedido

**POST** `/api/pedidos/{id}/pagar`

*Requiere autenticación como cliente*

Procesa el pago de un pedido pendiente.

**Body:**
```json
{
  "metodo_pago": "mock"
}
```

**Opciones de metodo_pago:** `mock`, `yape`, `plin`

**Respuesta (202 Accepted):**
```json
{
  "mensaje": "Pago en procesamiento",
  "orden": {
    "id": 42,
    "estado": "pending",
    "estado_pago": "procesando",
    "puesto_nombre": "Cevichería Mar Azul",
    "total": 128.00,
    "metodo_pago": "mock",
    "nota": "El pago se está procesando. Verifica el estado en GET /api/pedidos/42"
  }
}
```

**Nota:** El pago se procesa en background. Después de unos segundos, el estado del pedido cambiará a `confirmed`.

---

#### Obtener Detalle de Pedido

**GET** `/api/pedidos/{id}`

*Requiere autenticación*

Obtiene el detalle completo de un pedido.

**Respuesta (200 OK):**
```json
{
  "pedido": {
    "id": 42,
    "estado": "confirmed",
    "puesto": {
      "id": 1,
      "nombre": "Cevichería Mar Azul",
      "telefono": "+51987654321",
      "latitud": -12.1000,
      "longitud": -77.0500
    },
    "cliente": {
      "nombre": "Juan García",
      "telefono": "+51987654321"
    },
    "items": [
      {
        "producto": "Ceviche de Pescado",
        "cantidad": 2,
        "precio_unitario": 35.50,
        "subtotal": 71.00,
        "cremas": [
          {
            "id": 15,
            "nombre": "Leche de Tigre",
            "precio": 5.00
          }
        ]
      }
    ],
    "subtotal": 100.00,
    "igv": 18.00,
    "delivery": 10.00,
    "total": 128.00,
    "metodo_pago": "mock",
    "direccion_entrega": "Av. Principal 123, San Isidro",
    "notas": "Sin picante",
    "tiempo_estimado_entrega": "2026-05-21T16:20:00Z",
    "estado_timeline": {
      "pending": {
        "estado": "pending",
        "descripcion": "Pedido creado",
        "fecha": "2026-05-21T15:00:00Z"
      },
      "confirmed": {
        "estado": "confirmed",
        "descripcion": "Pago confirmado",
        "fecha": "2026-05-21T15:02:00Z"
      },
      "preparing": {
        "estado": "preparing",
        "descripcion": "En preparación",
        "fecha": "2026-05-21T15:05:00Z"
      }
    }
  }
}
```

---

#### Listar Mis Pedidos (Cliente)

**GET** `/api/pedidos`

*Requiere autenticación como cliente*

Obtiene todos los pedidos del cliente.

**Parámetros:**
- `estado` (optional): Filtrar por estado (pending, confirmed, preparing, ready, delivered, cancelled)
- `page` (optional): Número de página
- `per_page` (optional): Registros por página

**Respuesta (200 OK):**
```json
{
  "pedidos": [
    {
      "id": 42,
      "puesto_nombre": "Cevichería Mar Azul",
      "total": 128.00,
      "estado": "confirmed",
      "fecha": "2026-05-21T15:00:00Z",
      "items_count": 2
    },
    {
      "id": 41,
      "puesto_nombre": "Chifa Don Juan",
      "total": 95.50,
      "estado": "delivered",
      "fecha": "2026-05-20T19:30:00Z",
      "items_count": 1
    }
  ],
  "paginacion": {
    "total": 15,
    "per_page": 10,
    "current_page": 1,
    "last_page": 2
  }
}
```

---

#### Listar Mis Pedidos (Vendedor)

**GET** `/api/mis-pedidos`

*Requiere autenticación como vendedor*

Obtiene todos los pedidos del puesto del vendedor.

**Parámetros:**
- `estado` (optional)
- `page` (optional)
- `per_page` (optional)

**Respuesta (200 OK):**
```json
{
  "puesto_id": 1,
  "pedidos": [
    {
      "id": 42,
      "cliente_nombre": "Juan García",
      "cliente_telefono": "+51987654321",
      "total": 128.00,
      "estado": "preparing",
      "fecha": "2026-05-21T15:00:00Z",
      "items": [
        {
          "producto": "Ceviche de Pescado",
          "cantidad": 2
        }
      ],
      "notas": "Sin picante"
    }
  ]
}
```

---

### RF13: TIEMPO ESTIMADO DE ENTREGA

El tiempo se calcula automáticamente al crear un pedido basándose en:
- Tiempo base configurado
- Número de items × tiempo por item
- Si es delivery, se suma tiempo de entrega

**Ejemplo:**
```
Base: 5 min
Por item: 2 min
Items: 3
Delivery: Sí (+10 min)
= 5 + (3 × 2) + 10 = 21 minutos
```

---

### RF14: CAMBIAR ESTADO DEL PEDIDO

**PATCH** `/api/pedidos/{id}/cambiar-estado`

*Requiere autenticación como vendedor*

Cambia el estado del pedido en el flujo: pending → confirmed → preparing → ready → en_camino → delivered

**Body:**
```json
{
  "estado": "preparing",
  "notas": "Iniciando preparación"
}
```

**Estados válidos:**
- `pending`: Pedido creado (no pagado)
- `confirmed`: Pagado, listo para preparación
- `preparing`: En preparación
- `ready`: Listo para entregar/recoger
- `en_camino`: En camino de entrega
- `delivered`: Entregado

**Respuesta (200 OK):**
```json
{
  "mensaje": "Estado actualizado correctamente",
  "orden": {
    "id": 42,
    "estado_anterior": "confirmed",
    "estado_nuevo": "preparing",
    "actualizado_en": "2026-05-21T15:05:00Z"
  }
}
```

---

### RF15: CANCELAR PEDIDO

**PATCH** `/api/pedidos/{id}/cancelar`

*Requiere autenticación como cliente*

Cancela un pedido pendiente o confirmado.

**Body:**
```json
{
  "razon": "Cambié de opinión"
}
```

**Respuesta (200 OK):**
```json
{
  "mensaje": "Pedido cancelado correctamente",
  "orden": {
    "id": 42,
    "estado": "cancelled",
    "razon_cancelacion": "Cambié de opinión",
    "reembolso": 128.00,
    "cancelado_en": "2026-05-21T15:10:00Z"
  }
}
```

**Restricciones:**
- Solo se puede cancelar en estados: pending, confirmed
- Una vez en preparing o superior, requiere aprobación del vendedor

---

### RF16: CAMBIAR DIRECCIÓN DE ENTREGA

**PATCH** `/api/pedidos/{id}/cambiar-direccion`

*Requiere autenticación como cliente*

Cambia la dirección de entrega de un pedido (antes de que inicie la entrega).

**Body:**
```json
{
  "nueva_direccion": "Av. Secundaria 456, San Isidro",
  "nueva_latitud": -12.1350,
  "nueva_longitud": -77.0200
}
```

**Respuesta (200 OK):**
```json
{
  "mensaje": "Dirección actualizada correctamente",
  "orden": {
    "id": 42,
    "direccion_anterior": "Av. Principal 123, San Isidro",
    "direccion_nueva": "Av. Secundaria 456, San Isidro",
    "costo_delivery_anterior": 10.00,
    "costo_delivery_nuevo": 12.00,
    "total_anterior": 128.00,
    "total_nuevo": 130.00,
    "actualizado_en": "2026-05-21T15:15:00Z"
  }
}
```

---

### RF18-RF20: SISTEMA DE COMISIONES

#### Calcular Comisión

**POST** `/api/comisiones/calcular`

*Requiere autenticación*

Calcula la comisión para un pedido confirmado.

**Body:**
```json
{
  "order_id": 42
}
```

**Respuesta (201 Created):**
```json
{
  "exito": true,
  "mensaje": "Comisión calculada exitosamente",
  "datos": {
    "comision_id": 10,
    "pedido_id": 42,
    "monto_pedido": 128.00,
    "porcentaje_comision": "15%",
    "monto_comision": 19.20,
    "monto_neto_vendedor": 108.80,
    "regla_aplicada": "Comisión Base 15%",
    "estado": "pending",
    "calculada_en": "2026-05-21T15:20:00Z"
  }
}
```

---

#### Obtener Comisión de un Pedido

**GET** `/api/comisiones/pedido/{order_id}`

*Requiere autenticación*

Obtiene la comisión de un pedido específico.

**Respuesta (200 OK):**
```json
{
  "exito": true,
  "datos": {
    "comision_id": 10,
    "pedido_id": 42,
    "monto_pedido": 128.00,
    "porcentaje_comision": "15%",
    "monto_comision": 19.20,
    "monto_neto_vendedor": 108.80,
    "regla_aplicada": "Comisión Base 15%",
    "estado": "completed",
    "calculada_en": "2026-05-21T15:20:00Z",
    "transferida_en": "2026-05-21T16:00:00Z"
  }
}
```

---

#### Listar Reglas de Comisión (Admin)

**GET** `/api/comisiones/reglas`

*Requiere autenticación como admin*

Obtiene todas las reglas de comisión del sistema.

**Parámetros:**
- `activas` (optional): true|false
- `tipo` (optional): base|category|price_range|vendor_type

**Respuesta (200 OK):**
```json
{
  "exito": true,
  "total": 4,
  "datos": [
    {
      "id": 1,
      "nombre": "Comisión Base 15%",
      "tipo": "base",
      "porcentaje": "15%",
      "activa": "Sí",
      "vigencia_desde": "2026-01-01",
      "vigencia_hasta": "2026-12-31",
      "creada_por": "admin@example.com",
      "notas": "Comisión general para todos los vendedores",
      "creada_en": "2026-01-01T10:00:00Z",
      "actualizada_en": "2026-01-01T10:00:00Z"
    },
    {
      "id": 2,
      "nombre": "Comisión Ceviches 12%",
      "tipo": "category",
      "porcentaje": "12%",
      "activa": "Sí",
      "vigencia_desde": "2026-02-01",
      "vigencia_hasta": null,
      "creada_por": "admin@example.com",
      "notas": "Reducida para categoría de ceviches",
      "creada_en": "2026-02-01T10:00:00Z",
      "actualizada_en": "2026-02-01T10:00:00Z"
    }
  ]
}
```

---

#### Crear Regla de Comisión (Admin)

**POST** `/api/comisiones/reglas`

*Requiere autenticación como admin*

Crea una nueva regla de comisión.

**Body:**
```json
{
  "nombre": "Comisión Pedidos Premium",
  "tipo": "price_range",
  "porcentaje_comision": 10,
  "min_amount": 100,
  "max_amount": 500,
  "valid_from": "2026-05-21",
  "valid_until": "2026-12-31",
  "is_active": true,
  "notas": "Para pedidos entre 100 y 500 soles"
}
```

**Tipos soportados:**
- `base`: Aplica a todos los pedidos
- `category`: Aplica por categoría de producto
- `price_range`: Aplica por rango de monto
- `vendor_type`: Aplica por tipo de vendedor

**Respuesta (201 Created):**
```json
{
  "exito": true,
  "id": 5,
  "mensaje": "Regla creada exitosamente",
  "datos": {
    "id": 5,
    "nombre": "Comisión Pedidos Premium",
    "tipo": "price_range",
    "porcentaje": "10%"
  }
}
```

---

#### Transferir Comisiones (Admin)

**POST** `/api/comisiones/transferir`

*Requiere autenticación como admin*

Transfiere todas las comisiones pendientes a los vendedores.

**Body:**
```json
{
  "metodo_transferencia": "mock",
  "fecha_desde": "2026-05-01",
  "fecha_hasta": "2026-05-31"
}
```

**Respuesta (200 OK):**
```json
{
  "exito": true,
  "mensaje": "Proceso de transferencia iniciado",
  "datos": {
    "comisiones_procesadas": 45,
    "monto_total_transferido": 5230.50,
    "transferencias_exitosas": 42,
    "transferencias_fallidas": 3,
    "fecha_inicio": "2026-05-21T10:00:00Z",
    "detalles_fallos": [
      {
        "commission_id": 10,
        "vendedor": "Juan García",
        "monto": 120.00,
        "error": "Cuenta bancaria no verificada"
      }
    ]
  }
}
```

---

### RF21: COMPROBANTES ELECTRÓNICOS

#### Generar Comprobante

**POST** `/api/comprobantes/generar/{order_id}`

*Requiere autenticación*

Genera un comprobante electrónico (factura/boleta) para un pedido.

**Respuesta (201 Created):**
```json
{
  "exito": true,
  "invoice_id": 15,
  "mensaje": "Comprobante en generación",
  "datos": {
    "id": 15,
    "pedido_id": 42,
    "tipo": "boleta",
    "cliente": "Juan García",
    "documento_cliente": "DNI 12345678",
    "subtotal": 100.00,
    "igv": 18.00,
    "total": 128.00,
    "estado": "pending",
    "nota": "Se está generando. Verifica el estado en GET /api/comprobantes/15"
  }
}
```

---

#### Listar Comprobantes

**GET** `/api/comprobantes`

*Requiere autenticación*

Obtiene los comprobantes generados.

**Parámetros:**
- `estado` (optional): pending|generated|failed
- `page` (optional)
- `per_page` (optional)

**Respuesta (200 OK):**
```json
{
  "exito": true,
  "comprobantes": [
    {
      "id": 15,
      "pedido_id": 42,
      "tipo": "boleta",
      "cliente": "Juan García",
      "total": 128.00,
      "estado": "generated",
      "ose_ticket": "OSE-2026-5-123456",
      "pdf_disponible": true,
      "xml_disponible": true,
      "generado_en": "2026-05-21T15:25:00Z"
    }
  ]
}
```

---

#### Descargar Comprobante (PDF)

**GET** `/api/comprobantes/{invoice_id}/pdf`

*Requiere autenticación*

Descarga el PDF del comprobante.

**Respuesta:** Archivo PDF (application/pdf)

---

#### Descargar Comprobante (XML)

**GET** `/api/comprobantes/{invoice_id}/xml`

*Requiere autenticación*

Descarga el XML del comprobante (para integración con contabilidad).

**Respuesta:** Archivo XML (application/xml)

---

### RF28-RF32: GEOLOCALIZACIÓN Y MAPAS

#### Obtener Puestos Cercanos

**GET** `/api/puestos/nearby`

*Sin autenticación*

Obtiene puestos cercanos usando geolocalización.

**Parámetros:**
- `lat` (required, numeric): Latitud
- `lng` (required, numeric): Longitud
- `radius` (optional, numeric, default 1): Radio en km
- `open_now` (optional, boolean): Solo puestos abiertos
- `sort` (optional): distance|popularity
- `page` (optional)
- `per_page` (optional)

**Ejemplo:**
```
GET /api/puestos/nearby?lat=-12.1234&lng=-77.0123&radius=2&open_now=true&sort=distance&per_page=10
```

**Respuesta (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "nombre": "Cevichería Mar Azul",
      "direccion": "Av. Principal 123, San Isidro",
      "latitude": -12.1000,
      "longitude": -77.0500,
      "distance_km": 0.45,
      "orders_count": 150,
      "open_now": true,
      "active": true
    },
    {
      "id": 2,
      "nombre": "Chifa Don Juan",
      "direccion": "Av. Secundaria 456, San Isidro",
      "latitude": -12.1100,
      "longitude": -77.0400,
      "distance_km": 0.82,
      "orders_count": 89,
      "open_now": true,
      "active": true
    }
  ]
}
```

---

#### Listado Público de Puestos

**GET** `/api/puestos`

*Sin autenticación*

Obtiene un listado público de todos los puestos activos con filtros avanzados.

**Parámetros:**
- `lat` (optional): Latitud
- `lng` (optional): Longitud
- `radius` (optional, default 5): Radio en km
- `open_now` (optional): Solo abiertos
- `sort` (optional): distance|popularity
- `page` (optional)
- `per_page` (optional)

**Respuesta (200 OK):**
```json
{
  "meta": {
    "total": 42,
    "per_page": 10,
    "current_page": 1,
    "last_page": 5
  },
  "data": [
    {
      "id": 1,
      "nombre": "Cevichería Mar Azul",
      "descripcion": "Cevichería tradicional con 20 años de experiencia",
      "direccion": "Av. Principal 123, San Isidro",
      "latitude": -12.1000,
      "longitude": -77.0500,
      "distance_km": 0.45,
      "orders_count": 150,
      "open_now": true,
      "active": true,
      "rating": 4.8,
      "horario_apertura": "10:00",
      "horario_cierre": "22:00"
    }
  ]
}
```

---

#### Marcadores para Mapa

**GET** `/api/puestos/map-markers`

*Sin autenticación*

Obtiene marcadores ligeros para mostrar en mapa (menor carga de datos).

**Parámetros:**
- `lat` (required)
- `lng` (required)
- `radius` (optional)

**Respuesta (200 OK):**
```json
{
  "markers": [
    {
      "id": 1,
      "name": "Cevichería Mar Azul",
      "lat": -12.1000,
      "lng": -77.0500,
      "icon": "restaurant",
      "distance_km": 0.45,
      "open_now": true
    }
  ]
}
```

---

#### Obtener Puesto para Mapa

**GET** `/api/puestos/{stallId}/map`

*Sin autenticación*

Obtiene el puesto con su menú para mostrar desde un mapa.

**Respuesta (200 OK):**
```json
{
  "stall": {
    "id": 1,
    "nombre": "Cevichería Mar Azul",
    "descripcion": "Cevichería tradicional",
    "direccion": "Av. Principal 123, San Isidro",
    "telefono": "+51987654321",
    "latitude": -12.1000,
    "longitude": -77.0500,
    "horario_apertura": "10:00",
    "horario_cierre": "22:00",
    "abierto_ahora": true,
    "menuItems": [
      {
        "id": 10,
        "nombre": "Ceviche de Pescado",
        "precio": 35.50,
        "imagen": "/storage/images/producto_10.jpg"
      }
    ]
  }
}
```

---

#### Obtener Menú del Puesto

**GET** `/api/puestos/{stallId}/menu`

*Sin autenticación*

Alias a `/map` para obtener el menú.

---

#### Estado del Puesto

**GET** `/api/puestos/{stallId}/status`

*Sin autenticación*

Obtiene el estado actual del puesto (abierto, cerrado, en pausa, próxima apertura).

**Respuesta (200 OK):**
```json
{
  "stall_id": 1,
  "nombre": "Cevichería Mar Azul",
  "open_now": true,
  "in_pause": false,
  "next_opening": null,
  "horario_apertura": "10:00",
  "horario_cierre": "22:00",
  "pauses_active": []
}
```

---

#### Obtener Direcciones en Google Maps

**GET** `/api/puestos/{stallId}/directions`

*Sin autenticación*

Devuelve una URL para abrir direcciones en Google Maps.

**Parámetros:**
- `from_lat` (required): Latitud de origen
- `from_lng` (required): Longitud de origen
- `mode` (optional): driving|walking|bicycling (default: driving)

**Respuesta (200 OK):**
```json
{
  "url": "https://www.google.com/maps/dir/?api=1&origin=-12.1234,-77.0123&destination=-12.1000,-77.0500&travelmode=driving"
}
```

---

#### Estimar Ruta y ETA

**GET** `/api/puestos/{stallId}/route`

*Sin autenticación*

Estima la distancia y tiempo aproximado (sin API externa).

**Parámetros:**
- `from_lat` (required)
- `from_lng` (required)
- `mode` (optional): driving|walking

**Respuesta (200 OK):**
```json
{
  "stall_id": 1,
  "distance_km": 2.45,
  "estimated_time_min": 8,
  "mode": "driving",
  "nota": "Estimación local. Para máxima precisión, usa Google Maps Directions API"
}
```

---

### RF17: CONVERTIRSE EN VENDEDOR

**POST** `/api/convertirse-vendedor`

*Requiere autenticación como cliente*

Convierte un cliente en vendedor y crea su primer puesto.

**Body:**
```json
{
  "nombre_puesto": "Cevichería Mar Azul",
  "direccion": "Av. Principal 123, San Isidro",
  "telefono": "+51987654321",
  "horario_apertura": "10:00",
  "horario_cierre": "22:00",
  "latitud": -12.1000,
  "longitud": -77.0500,
  "descripcion": "Cevichería tradicional con 20 años de experiencia"
}
```

**Respuesta (201 Created):**
```json
{
  "mensaje": "Has sido convertido a vendedor exitosamente",
  "usuario": {
    "id": 1,
    "nombre": "Juan García",
    "email": "juan@example.com",
    "rol": "vendor",
    "puesto": {
      "id": 1,
      "nombre": "Cevichería Mar Azul",
      "slug": "cevicheria-mar-azul",
      "activo": true
    }
  }
}
```

---

### RF6: GENERAR QR DEL PUESTO

**POST** `/api/mis-puesto/generar-qr`

*Requiere autenticación como vendedor*

Genera un código QR para el puesto.

**Body:**
```json
{
  "tamaño": 300
}
```

**Respuesta (200 OK):**
```json
{
  "mensaje": "QR generado exitosamente",
  "puesto": {
    "id": 1,
    "nombre": "Cevichería Mar Azul",
    "qr_url": "/storage/qr/stall_1_qr.png",
    "qr_contenido": "https://altoque.app/menu/cevicheria-mar-azul"
  }
}
```

---

## SERVICIOS Y JOBS

### CommissionService

Calcula automáticamente la comisión de un pedido confirmado.

**Prioridad de reglas:**
1. `price_range`: Si el monto está en un rango específico
2. `category`: Si es de una categoría específica
3. `vendor_type`: Si el vendedor es de un tipo específico
4. `base`: Comisión general

**Ejemplo de cálculo:**
```
Order total: 128.00
Applicable rule: Base 15%
Commission amount: 19.20
Net for vendor: 108.80
```

---

### PaymentService

Procesa pagos de forma asíncrona mediante jobs.

**Métodos soportados:**
- `mock`: Simula un pago exitoso (para testing)
- `yape`: Integración con app Yape (pendiente)
- `plin`: Integración con app Plin (pendiente)

**Flujo:**
1. Cliente solicita pago → `ProcessPaymentJob` encolado
2. Job ejecuta `PaymentService::processPayment()`
3. Se crea registro Payment
4. Se actualiza Order status a `confirmed`
5. Se calcula comisión vía CommissionService
6. Si invoice_requested → `GenerateElectronicInvoiceJob` encolado

---

### DeliveryService

Calcula costos y distancias de delivery.

**Fórmula Haversine:**
```
Distancia = 6371 × acos(cos(lat1°) × cos(lat2°) × cos(lng2° - lng1°) + sin(lat1°) × sin(lat2°))
```

**Costo delivery:**
```
Si distance_km ≤ stall.delivery_min_cost_distance:
  costo = stall.delivery_min_cost
Sino:
  costo = stall.delivery_min_cost + (distance_km × stall.delivery_rate_per_km)
```

---

### InvoiceService

Genera comprobantes electrónicos (facturas/boletas).

**Flujo:**
1. Se crea registro Invoice
2. Se despacha `GenerateElectronicInvoiceJob`
3. Job genera XML vía OseAdapterMock
4. Job genera PDF vía InvoicePdfGenerator
5. Se guarda en storage
6. Se actualiza Invoice status a `generated`
7. Se envía email al cliente

---

### TransferService

Transfiere las comisiones netas a vendedores.

**Métodos:**
- `mock`: Simula transferencia (para testing)
- Integrable con Yape/Plin APIs

---

## FLUJOS PRINCIPALES

### Flujo 1: Crear y Pagar Pedido

```
1. Cliente obtiene token auth (POST /api/auth/login)
2. Cliente ve puestos cercanos (GET /api/puestos/nearby?lat=...&lng=...)
3. Cliente ve menú de puesto (GET /api/menu/{stallId})
4. Cliente crea pedido (POST /api/pedidos)
   - Status: pending
5. Sistema calcula tiempos (RF13)
6. Cliente paga (POST /api/pedidos/{id}/pagar)
   - ProcessPaymentJob encolado
   - Status aún: pending
7. Job procesa pago:
   - Crea Payment
   - Status → confirmed
   - CommissionService calcula comisión
   - GenerateElectronicInvoiceJob encolado (si solicitó)
8. Cliente puede ver estado (GET /api/pedidos/{id})
9. Vendedor ve pedido (GET /api/mis-pedidos)
10. Vendedor cambia estado (PATCH /api/pedidos/{id}/cambiar-estado)
    - pending → confirmed → preparing → ready → en_camino → delivered
```

---

### Flujo 2: Sistema de Comisiones

```
1. Pedido es confirmado (pagado)
   - CommissionService::calculateCommissionForOrder() automáticamente
   - Selecciona mejor regla aplicable
   - Crea OrderCommission (status: pending)
   - Registra en CompanyAccountEntry (crédito)
   - Registra en CommissionAuditLog
2. Admin transfiere comisiones (POST /api/comisiones/transferir)
   - ExecuteTransferJob encolado para cada comisión
3. Job ejecuta transferencia:
   - Llama TransferService
   - Registra TransferAttempt
   - Actualiza OrderCommission status → completed
   - Registra débito en CompanyAccountEntry
4. Vendedor puede ver estado:
   - GET /api/comisiones/pedido/{order_id}
   - Ver monto neto ganado, porcentaje, regla aplicada
```

---

### Flujo 3: Generar Comprobante Electrónico

```
1. Cliente solicita comprobante al crear pedido (invoice_requested: true)
   - Especifica tipo: boleta o factura
   - Proporciona datos: document_type (DNI|RUC), document_number, customer_name
2. Pedido es pagado
   - GenerateElectronicInvoiceJob automáticamente encolado
3. Job ejecuta:
   - Lee datos del pedido e invoice
   - Valida que esté completo
   - Genera XML vía OseAdapterMock
   - Genera PDF vía InvoicePdfGenerator
   - Guarda en storage (`invoices/`)
   - Actualiza Invoice status → generated
   - Envía email al cliente con enlace de descarga
4. Cliente descarga:
   - GET /api/comprobantes/{invoice_id}/pdf
   - GET /api/comprobantes/{invoice_id}/xml
5. Si falla:
   - Reintentos automáticos (max 3)
   - Notificación admin si falla definitivamente
```

---

## RESPUESTAS Y CÓDIGOS HTTP

### Códigos HTTP Utilizados

| Código | Significado | Ejemplo |
|---|---|---|
| `200` | OK | Operación exitosa, datos devueltos |
| `201` | Created | Recurso creado exitosamente |
| `202` | Accepted | Solicitud aceptada, procesamiento en background |
| `204` | No Content | Solicitud exitosa, sin contenido (DELETE) |
| `400` | Bad Request | Datos inválidos en solicitud |
| `401` | Unauthorized | Token faltante o expirado |
| `403` | Forbidden | Usuario sin permiso para la acción |
| `404` | Not Found | Recurso no existe |
| `409` | Conflict | Conflicto (ej: puesto cerrado, estado inválido) |
| `422` | Unprocessable Entity | Errores de validación |
| `429` | Too Many Requests | Límite de rate limiting excedido |
| `500` | Internal Server Error | Error del servidor |

---

### Formato Estándar de Respuesta (Error)

```json
{
  "error": "Descripción del error",
  "errores": {
    "campo_1": ["Mensaje de error 1", "Mensaje de error 2"],
    "campo_2": ["Mensaje de error"]
  },
  "status": 422,
  "timestamp": "2026-05-21T15:30:00Z"
}
```

---

### Ejemplo: No Autenticado

```json
{
  "error": "Unauthenticated",
  "message": "Se requiere autenticación"
}
```

---

### Ejemplo: Sin Permiso

```json
{
  "error": "No tienes permiso para esta acción",
  "message": "Solo vendedores pueden acceder a este endpoint"
}
```

---

## INTEGRACIÓN CON FLUTTER

### 1. Configuración Base

```dart
class ApiClient {
  static const String BASE_URL = 'http://localhost:8000/api';
  static const String TOKEN_KEY = 'api_token';
  
  late SharedPreferences _prefs;
  late Dio _dio;
  
  Future<void> initialize() async {
    _prefs = await SharedPreferences.getInstance();
    _dio = Dio(BaseOptions(
      baseUrl: BASE_URL,
      connectTimeout: Duration(seconds: 30),
      receiveTimeout: Duration(seconds: 30),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));
    
    // Interceptor para agregar token
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) {
        final token = _prefs.getString(TOKEN_KEY);
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          // Token expirado, limpiar y redirigir a login
          _prefs.remove(TOKEN_KEY);
        }
        return handler.next(error);
      },
    ));
  }
}
```

---

### 2. Modelos Principales

```dart
// User
class User {
  final int id;
  final String name;
  final String email;
  final String role; // customer, vendor, admin
  final String? phone;
  final String? dni;
  final String? address;
  
  User.fromJson(Map<String, dynamic> json)
    : id = json['id'],
      name = json['name'],
      email = json['email'],
      role = json['role'],
      phone = json['phone'],
      dni = json['dni'],
      address = json['address'];
}

// Order
class Order {
  final int id;
  final int stallId;
  final String status; // pending, confirmed, preparing, ready, delivered
  final double subtotal;
  final double igv;
  final double deliveryCost;
  final double total;
  final List<OrderItem> items;
  final String? notes;
  final DateTime? estimatedDelivery;
  
  Order.fromJson(Map<String, dynamic> json)
    : id = json['id'],
      stallId = json['stall_id'],
      status = json['status'],
      subtotal = json['subtotal'],
      igv = json['igv'],
      deliveryCost = json['delivery_cost'],
      total = json['total'],
      items = (json['items'] as List)
        .map((i) => OrderItem.fromJson(i))
        .toList(),
      notes = json['client_notes'],
      estimatedDelivery = json['estimated_delivery_at'] != null
        ? DateTime.parse(json['estimated_delivery_at'])
        : null;
}

// FoodStall
class FoodStall {
  final int id;
  final String name;
  final String address;
  final double latitude;
  final double longitude;
  final String openingTime;
  final String closingTime;
  final bool openNow;
  final List<MenuItem> menuItems;
  
  FoodStall.fromJson(Map<String, dynamic> json)
    : id = json['id'],
      name = json['name'],
      address = json['address'],
      latitude = json['latitude'],
      longitude = json['longitude'],
      openingTime = json['horario_apertura'],
      closingTime = json['horario_cierre'],
      openNow = json['open_now'] ?? false,
      menuItems = (json['menuItems'] as List?)
        ?.map((m) => MenuItem.fromJson(m))
        .toList() ?? [];
}

// MenuItem
class MenuItem {
  final int id;
  final String name;
  final String? description;
  final double price;
  final String? image;
  final List<Topping> toppings;
  
  MenuItem.fromJson(Map<String, dynamic> json)
    : id = json['id'],
      name = json['nombre'] ?? json['name'],
      description = json['descripcion'] ?? json['description'],
      price = json['precio'] ?? json['price'],
      image = json['imagen'] ?? json['image'],
      toppings = (json['cremas'] as List?)
        ?.map((t) => Topping.fromJson(t))
        .toList() ?? [];
}

// Topping
class Topping {
  final int id;
  final String name;
  final double price;
  final bool required;
  
  Topping.fromJson(Map<String, dynamic> json)
    : id = json['id'],
      name = json['nombre'] ?? json['name'],
      price = json['precio'] ?? json['precio_adicional'] ?? 0,
      required = json['requerida'] ?? json['required'] ?? false;
}
```

---

### 3. Servicios principales

```dart
// AuthService
class AuthService {
  final ApiClient apiClient;
  
  Future<Map<String, dynamic>> register(String name, String email, String password) async {
    final response = await apiClient.post('/auth/register', data: {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': password,
    });
    return response;
  }
  
  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await apiClient.post('/auth/login', data: {
      'email': email,
      'password': password,
    });
    // Guardar token
    await _saveToken(response['token']);
    return response;
  }
  
  Future<void> logout() async {
    // Limpiar token localmente
  }
}

// StallService
class StallService {
  final ApiClient apiClient;
  
  Future<List<FoodStall>> getNearby(double lat, double lng, {int radius = 1}) async {
    final response = await apiClient.get('/puestos/nearby', queryParameters: {
      'lat': lat,
      'lng': lng,
      'radius': radius,
    });
    return (response['data'] as List)
      .map((s) => FoodStall.fromJson(s))
      .toList();
  }
  
  Future<FoodStall> getStall(int stallId) async {
    final response = await apiClient.get('/puestos/$stallId/map');
    return FoodStall.fromJson(response['stall']);
  }
}

// OrderService
class OrderService {
  final ApiClient apiClient;
  
  Future<Order> createOrder(CreateOrderRequest request) async {
    final response = await apiClient.post('/pedidos', data: request.toJson());
    return Order.fromJson(response['orden']);
  }
  
  Future<Order> payOrder(int orderId, String paymentMethod) async {
    final response = await apiClient.post('/pedidos/$orderId/pagar', data: {
      'metodo_pago': paymentMethod,
    });
    return Order.fromJson(response['orden']);
  }
  
  Future<Order> getOrder(int orderId) async {
    final response = await apiClient.get('/pedidos/$orderId');
    return Order.fromJson(response['pedido']);
  }
  
  Future<List<Order>> listMyOrders() async {
    final response = await apiClient.get('/pedidos');
    return (response['pedidos'] as List)
      .map((o) => Order.fromJson(o))
      .toList();
  }
}
```

---

### 4. Manejo de Ubicación

```dart
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

class LocationService {
  Future<Position> getCurrentLocation() async {
    final permission = await Geolocator.requestPermission();
    if (permission == LocationPermission.denied) {
      throw Exception('Permisos de ubicación denegados');
    }
    return await Geolocator.getCurrentPosition();
  }
  
  Stream<Position> getLocationUpdates() {
    return Geolocator.getPositionStream(
      locationSettings: LocationSettings(
        accuracy: LocationAccuracy.high,
        distanceFilter: 10, // Actualizar cada 10m
      ),
    );
  }
}
```

---

### 5. Autenticación con Tokens

```dart
// En main.dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await apiClient.initialize();
  
  // Verificar si hay token guardado
  final token = await _prefs.getString('api_token');
  final isLoggedIn = token != null;
  
  runApp(MyApp(isLoggedIn: isLoggedIn));
}

// Navigation condicionada por autenticación
class MyApp extends StatelessWidget {
  final bool isLoggedIn;
  
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      home: isLoggedIn ? HomeScreen() : AuthScreen(),
    );
  }
}
```

---

### 6. Pantalla de Mapa

```dart
class MapScreen extends StatefulWidget {
  @override
  _MapScreenState createState() => _MapScreenState();
}

class _MapScreenState extends State<MapScreen> {
  late GoogleMapController mapController;
  Set<Marker> markers = {};
  final stallService = StallService(apiClient);
  final locationService = LocationService();
  
  @override
  void initState() {
    super.initState();
    _loadNearbyStalls();
  }
  
  Future<void> _loadNearbyStalls() async {
    final position = await locationService.getCurrentLocation();
    final stalls = await stallService.getNearby(
      position.latitude,
      position.longitude,
      radius: 2,
    );
    
    setState(() {
      markers = stalls.map((stall) {
        return Marker(
          markerId: MarkerId(stall.id.toString()),
          position: LatLng(stall.latitude, stall.longitude),
          infoWindow: InfoWindow(title: stall.name),
          onTap: () => _showStallDetails(stall),
        );
      }).toSet();
    });
  }
  
  void _showStallDetails(FoodStall stall) {
    // Mostrar menú del puesto
    Navigator.push(context, MaterialPageRoute(
      builder: (context) => StallMenuScreen(stall: stall),
    ));
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Puestos Cercanos')),
      body: GoogleMap(
        onMapCreated: (controller) => mapController = controller,
        initialCameraPosition: CameraPosition(
          target: LatLng(-12.1234, -77.0123),
          zoom: 15,
        ),
        markers: markers,
      ),
    );
  }
}
```

---

### 7. Pantalla de Carrito

```dart
class CartScreen extends StatefulWidget {
  final FoodStall stall;
  
  @override
  _CartScreenState createState() => _CartScreenState();
}

class _CartScreenState extends State<CartScreen> {
  Map<int, int> cartItems = {}; // productId -> quantity
  
  void addToCart(MenuItem item) {
    setState(() {
      cartItems[item.id] = (cartItems[item.id] ?? 0) + 1;
    });
  }
  
  Future<void> submitOrder() async {
    final request = CreateOrderRequest(
      stallId: widget.stall.id,
      productos: cartItems.entries.map((e) => ProductInOrder(
        productoId: e.key,
        cantidad: e.value,
      )).toList(),
      direccion: 'Mi Dirección',
      withDelivery: true,
    );
    
    final orderService = OrderService(apiClient);
    final order = await orderService.createOrder(request);
    
    // Redirigir a pantalla de pago
    Navigator.push(context, MaterialPageRoute(
      builder: (context) => PaymentScreen(order: order),
    ));
  }
}
```

---

### 8. Pantalla de Rastreo de Pedido

```dart
class OrderTrackingScreen extends StatefulWidget {
  final int orderId;
  
  @override
  _OrderTrackingScreenState createState() => _OrderTrackingScreenState();
}

class _OrderTrackingScreenState extends State<OrderTrackingScreen> {
  late Timer _refreshTimer;
  Order? order;
  
  @override
  void initState() {
    super.initState();
    _refreshOrder();
    // Actualizar cada 10 segundos
    _refreshTimer = Timer.periodic(Duration(seconds: 10), (_) => _refreshOrder());
  }
  
  Future<void> _refreshOrder() async {
    final orderService = OrderService(apiClient);
    final updatedOrder = await orderService.getOrder(widget.orderId);
    setState(() {
      order = updatedOrder;
    });
  }
  
  @override
  Widget build(BuildContext context) {
    if (order == null) return Scaffold(body: Center(child: CircularProgressIndicator()));
    
    final stateTimeline = [
      ('pending', 'Creado', Icons.receipt),
      ('confirmed', 'Pagado', Icons.check_circle),
      ('preparing', 'Preparando', Icons.restaurant),
      ('ready', 'Listo', Icons.done_all),
      ('en_camino', 'En camino', Icons.local_shipping),
      ('delivered', 'Entregado', Icons.home),
    ];
    
    return Scaffold(
      appBar: AppBar(title: Text('Pedido #${order!.id}')),
      body: Column(
        children: [
          Expanded(
            child: ListView(
              children: stateTimeline.map((state) {
                final isCompleted = order!.status == state.$1 || 
                  (stateTimeline.indexWhere((s) => s.$1 == order!.status) > 
                   stateTimeline.indexWhere((s) => s.$1 == state.$1));
                
                return ListTile(
                  leading: Icon(
                    state.$3,
                    color: isCompleted ? Colors.green : Colors.grey,
                  ),
                  title: Text(state.$2),
                  trailing: order!.status == state.$1 
                    ? CircularProgressIndicator()
                    : null,
                );
              }).toList(),
            ),
          ),
          if (order!.status == 'ready' || order!.status == 'en_camino')
            Padding(
              padding: EdgeInsets.all(16),
              child: ElevatedButton(
                onPressed: () {
                  // Abrir ubicación en tiempo real o Google Maps
                },
                child: Text('Ver ubicación en mapa'),
              ),
            ),
        ],
      ),
    );
  }
  
  @override
  void dispose() {
    _refreshTimer.cancel();
    super.dispose();
  }
}
```

---

### 9. Pantalla de Vendedor

```dart
class VendorDashboard extends StatefulWidget {
  @override
  _VendorDashboardState createState() => _VendorDashboardState();
}

class _VendorDashboardState extends State<VendorDashboard> {
  late Timer _refreshTimer;
  List<Order> activeOrders = [];
  
  @override
  void initState() {
    super.initState();
    _loadActiveOrders();
    _refreshTimer = Timer.periodic(Duration(seconds: 15), (_) => _loadActiveOrders());
  }
  
  Future<void> _loadActiveOrders() async {
    final orderService = OrderService(apiClient);
    final orders = await orderService.listMyOrders();
    
    setState(() {
      activeOrders = orders.where((o) => 
        o.status != 'delivered' && o.status != 'cancelled'
      ).toList();
    });
  }
  
  Future<void> _updateOrderStatus(int orderId, String newStatus) async {
    // PATCH /api/pedidos/{id}/cambiar-estado
    final response = await apiClient.patch(
      '/pedidos/$orderId/cambiar-estado',
      data: {'estado': newStatus},
    );
    await _loadActiveOrders();
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Mi Puesto - Panel de Vendedor')),
      body: RefreshIndicator(
        onRefresh: _loadActiveOrders,
        child: ListView.builder(
          itemCount: activeOrders.length,
          itemBuilder: (context, index) {
            final order = activeOrders[index];
            return OrderCard(
              order: order,
              onStatusChanged: (newStatus) => _updateOrderStatus(order.id, newStatus),
            );
          },
        ),
      ),
    );
  }
}
```

---

Este documento proporciona toda la información que un desarrollador de Flutter necesita para construir una app completa que integre con este backend de Altoque. La documentación incluye todos los endpoints, modelos, flujos, y ejemplos de código Flutter para las principales funcionalidades.

