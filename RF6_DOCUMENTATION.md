# RF6 - GESTIÓN DE PUESTOS Y MENÚS

## ✅ IMPLEMENTACIÓN COMPLETADA

---

## 📋 RESUMEN

RF6 permite que:
1. **Vendedores** gestionen su puesto: crear QR, agregar productos, activar/desactivar por día, destacar ofertas
2. **Clientes** scaneen el QR y vean el menú disponible sin estar loguados, pero requieren login para hacer pedido

---

## 🛠️ CAMBIOS IMPLEMENTADOS

### 1. **Nueva Migración**
- `2026_01_04_add_featured_active_to_menu_items_table.php`
  - Agrega campos `active` y `featured` a tabla `menu_items`

### 2. **Modelos Actualizados**
- `MenuItem.php`: Agregados campos `active` y `featured` en `$fillable`

### 3. **Controllers Ampliados**

#### **StallController.php** (Nuevos métodos)
- `obtenerMiPuesto()`: Retorna datos del puesto del vendedor
- `generarQr()`: Genera QR con URL del menú

#### **MenuController.php** (Nuevos métodos)
- `obtenerMisProductos()`: Ve todos los productos del vendedor (activos e inactivos)
- `crearProducto()`: Crear nuevo producto
- `actualizarProducto()`: Editar/desactivar/destacar producto
- `verMenu()`: Cliente ve menú público (solo productos activos)

### 4. **Rutas Nuevas en `routes/api.php`**

```php
// VENDEDOR
GET    /api/mis-puesto
POST   /api/mis-puesto/generar-qr
GET    /api/mis-productos
POST   /api/mis-productos
PATCH  /api/mis-productos/{id}

// CLIENTE
GET    /api/menu/{stallId}
```

---

## 📊 ENDPOINTS DETALLADOS

### **1. GET /api/mis-puesto**
**Autenticación:** `auth:sanctum + role:vendor`

**Respuesta (200):**
```json
{
  "puesto": {
    "id": 1,
    "nombre": "Salchipapas Don Juan",
    "slug": "salchipapas-don-juan",
    "direccion": "Jr. Lima 123",
    "telefono": "+51987654321",
    "horario_apertura": "11:00",
    "horario_cierre": "23:00",
    "qr_path": null,
    "activo": true
  }
}
```

---

### **2. POST /api/mis-puesto/generar-qr**
**Autenticación:** `auth:sanctum + role:vendor`

**Body:** `{}` (vacío)

**Respuesta (200):**
```json
{
  "message": "QR generado exitosamente",
  "qr_url": "http://localhost/storage/qr_codes/stall_1_1704415200.png",
  "codigo_puesto": "STALL-1",
  "menu_url": "http://localhost:3000/menu/salchipapas-don-juan",
  "generado_en": "2026-01-04T16:30:00Z"
}
```

---

### **3. GET /api/mis-productos**
**Autenticación:** `auth:sanctum + role:vendor`

**Respuesta (200):**
```json
{
  "puesto_id": 1,
  "puesto_nombre": "Salchipapas Don Juan",
  "total_productos": 2,
  "productos": [
    {
      "id": 1,
      "nombre": "Salchipapas Clásica",
      "descripcion": "Papa con salchichón",
      "precio": 8.50,
      "categoria": "Platos",
      "categoria_id": 1,
      "activo": true,
      "destacado": false,
      "imagen": null,
      "creado_en": "2026-01-04T16:30:00Z"
    }
  ]
}
```

---

### **4. POST /api/mis-productos**
**Autenticación:** `auth:sanctum + role:vendor`

**Body:**
```json
{
  "nombre": "Salchipapas Especial",
  "descripcion": "Papa con queso derretido",
  "precio": 12.50,
  "categoria_id": 1,
  "activo": true,
  "destacado": false
}
```

**Validaciones:**
- `nombre`: Obligatorio, máx 255 caracteres
- `precio`: Obligatorio, numérico, mín 0.01
- `categoria_id`: Obligatorio, debe existir en tabla `categories`
- `descripcion`: Opcional, máx 1000 caracteres
- `activo`: Opcional (default: true)
- `destacado`: Opcional (default: false)

**Respuesta (201):**
```json
{
  "message": "Producto creado exitosamente",
  "producto": {
    "id": 3,
    "nombre": "Salchipapas Especial",
    "precio": 12.50,
    "activo": true,
    "destacado": false
  }
}
```

---

### **5. PATCH /api/mis-productos/{id}**
**Autenticación:** `auth:sanctum + role:vendor`

**Body (todos opcionales):**
```json
{
  "nombre": "Nuevo nombre",
  "descripcion": "Nueva descripción",
  "precio": 15.00,
  "categoria_id": 2,
  "activo": false,
  "destacado": true
}
```

**Respuesta (200):**
```json
{
  "message": "Producto actualizado exitosamente",
  "producto": {
    "id": 1,
    "nombre": "Nuevo nombre",
    "precio": 15.00,
    "activo": false,
    "destacado": true
  }
}
```

---

### **6. GET /api/menu/{stallId}**
**Autenticación:** NO requerida (pero detecta si está logueado)

**Parámetro:** `stallId` puede ser el ID o el slug

**Respuesta (200) - SIN autenticación:**
```json
{
  "puesto": {
    "id": 1,
    "nombre": "Salchipapas Don Juan",
    "slug": "salchipapas-don-juan",
    "direccion": "Jr. Lima 123",
    "telefono": "+51987654321",
    "horario_apertura": "11:00",
    "horario_cierre": "23:00",
    "activo": true,
    "descripcion": "Las mejores salchipapas"
  },
  "productos": [
    {
      "id": 1,
      "nombre": "Salchipapas Clásica",
      "precio": 8.50,
      "descripcion": "Papa con salchichón",
      "categoria": "Platos",
      "destacado": false,
      "imagen": null
    }
  ],
  "total_productos": 1,
  "usuario_autenticado": false
}
```

**Nota:** El flag `usuario_autenticado` indica al frontend si mostrar/habilitar botones de pedido.

---

## 🧪 CÓMO PROBAR

### **Paso 1: Crear usuario vendedor**

```bash
php artisan tinker
```

```php
$user = App\Models\User::create([
    'name' => 'Juan Vendedor',
    'first_name' => 'Juan',
    'last_name' => 'Vendedor',
    'email' => 'juan.vendedor@test.com',
    'role' => 'vendor',
    'password' => null
]);

$puesto = App\Models\FoodStall::create([
    'name' => 'Salchipapas Don Juan',
    'seller_id' => $user->id,
    'slug' => 'salchipapas-don-juan',
    'address' => 'Jr. Lima 123, Breña, Lima',
    'phone' => '+51987654321',
    'opening_time' => '11:00',
    'closing_time' => '23:00',
    'latitude' => -12.0693,
    'longitude' => -77.0703,
    'description' => 'Las mejores salchipapas del barrio',
    'active' => true
]);

# Crear categorías
App\Models\Category::firstOrCreate(['name' => 'Platos']);
App\Models\Category::firstOrCreate(['name' => 'Bebidas']);

$token = $user->createToken('Test')->plainTextToken;
echo "TOKEN: " . $token;

# COPIAR EL TOKEN
```

Escribe `exit` para salir.

---

### **Paso 2: Probar en Postman**

Usa el token copiado en los headers `Authorization: Bearer {TOKEN}`

Ver archivo [SETUP_RF6_TESTS.md](SETUP_RF6_TESTS.md) para todos los tests.

---

## 🎯 CARACTERÍSTICAS IMPLEMENTADAS

✅ Vendedor puede obtener datos de su puesto  
✅ Vendedor puede generar QR único (con URL del menú)  
✅ Vendedor puede agregar productos al menú  
✅ Vendedor puede ver todos sus productos (activos e inactivos)  
✅ Vendedor puede desactivar productos del día  
✅ Vendedor puede destacar ofertas del día  
✅ Vendedor puede actualizar precios, nombres, descripciones  
✅ Cliente puede ver menú sin estar loguado  
✅ Sistema detecta si cliente está loguado (flag `usuario_autenticado`)  
✅ Menú muestra solo productos activos para cliente  
✅ Menú destaca productos en orden: destacados primero  
✅ Caché implementado para optimizar (60 segundos)  
✅ Auditoría en logs para todas las acciones  

---

## ⚙️ DETALLES TÉCNICOS

### **Caché**
- Clave: `menu_stall_{stallId}`
- Duración: 60 segundos
- Se limpia cuando: vendedor crea/actualiza productos

### **Validaciones**
- Vendedor solo puede editar sus propios productos
- Productos solo se retornan si puesto está activo
- Menú del cliente solo muestra productos activos

### **Ordenamiento**
- Productos destacados primero
- Luego por nombre (alfabético)
- En menú cliente: destacados siempre visible

---

## 🔄 FLUJO COMPLETO

```
1. Vendedor: GET /api/mis-puesto → Ve sus datos
2. Vendedor: POST /api/mis-puesto/generar-qr → Obtiene URL del QR
3. Vendedor: POST /api/mis-productos → Crea producto
4. Vendedor: PATCH /api/mis-productos/1 → Desactiva producto del día
5. Vendedor: PATCH /api/mis-productos/2 → Destaca oferta
6. Cliente: Escanea QR → Lee código
7. Cliente: GET /api/menu/salchipapas-don-juan (sin token) → Ve menú
8. Cliente: Ve botón "Hacer Pedido" pero está deshabilitado
9. Cliente: Click en "Hacer Pedido" → Redirige a login
10. Cliente: Login exitoso → Token obtenido
11. Cliente: GET /api/menu/salchipapas-don-juan (con token) → usuario_autenticado=true
12. Cliente: Click en "Hacer Pedido" → Botón habilitado
13. Cliente: POST /api/pedidos (con token) → Pedido creado ✅
```

---

## 📁 ARCHIVOS MODIFICADOS

- ✅ `app/Models/MenuItem.php`
- ✅ `app/Http/Controllers/StallController.php`
- ✅ `app/Http/Controllers/MenuController.php`
- ✅ `routes/api.php`
- ✅ `database/migrations/2026_01_04_add_featured_active_to_menu_items_table.php`

---

## 🚀 PRÓXIMOS PASOS

RF6 está completo. El siguiente es **RF11-12**: Implementar creación de pedidos, pagos y seguimiento.

Consulta [API_CONVERTIRSE_VENDEDOR.md](API_CONVERTIRSE_VENDEDOR.md) para flujo de conversión cliente → vendedor.

