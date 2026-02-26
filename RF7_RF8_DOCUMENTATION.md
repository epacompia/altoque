# RF7 & RF8 - CONFIGURACIÓN DE MENÚ Y CREMAS/ACOMPAÑAMIENTOS

## ✅ IMPLEMENTACIÓN COMPLETADA

---

## 📋 RESUMEN

**RF7**: Vendedor configura menú editando precios, fotos, descripción y disponibilidad  
**RF8**: Vendedor asigna cremas/acompañamientos opcionales o requeridos a cada plato

---

## 🛠️ CAMBIOS IMPLEMENTADOS

### 1. **Nuevas Migraciones**
- `2026_01_12_203607_create_toppings_table.php` - Tabla de cremas/acompañamientos
- `2026_01_12_203629_create_menu_item_topping_table.php` - Relación many-to-many
- `2026_01_12_203647_add_image_support_to_menu_items_table.php` - Soporte de imágenes

### 2. **Nuevos Modelos**
- `Topping.php` - Modelo para cremas/acompañamientos

### 3. **Nuevos Métodos en MenuController**
- `obtenerCremas()` - Listar todas las cremas del vendedor
- `crearCrema()` - Crear nueva crema
- `actualizarCrema()` - Actualizar crema
- `asociarCremaAProducto()` - Vincular crema a producto
- `desasociarCremaDeProducto()` - Desvincular crema
- `obtenerCremasDeProducto()` - Ver cremas de un producto

### 4. **Rutas Nuevas**
```php
// Cremas
GET    /api/mis-cremas
POST   /api/mis-cremas
PATCH  /api/mis-cremas/{id}

// Asociar cremas a productos
POST   /api/mis-productos/{productoId}/cremas
DELETE /api/mis-productos/{productoId}/cremas/{cremaId}
GET    /api/mis-productos/{productoId}/cremas
```

---

## 📊 NUEVOS ENDPOINTS

### **RF7: ACTUALIZAR PRODUCTO CON IMAGEN**

#### **PATCH /api/mis-productos/{id}**
**Autenticación:** `auth:sanctum + role:vendor`

**Body (todos opcionales, multipart/form-data):**
```json
{
  "nombre": "Salchipapas Premium",
  "descripcion": "Con queso derretido",
  "precio": 15.50,
  "categoria_id": 1,
  "activo": true,
  "destacado": true,
  "imagen": <archivo.jpg>
}
```

**Respuesta (200):**
```json
{
  "message": "Producto actualizado exitosamente",
  "producto": {
    "id": 1,
    "nombre": "Salchipapas Premium",
    "precio": 15.50,
    "activo": true,
    "destacado": true,
    "imagen": "http://127.0.0.1:8000/storage/productos/1_1705088400.jpg"
  }
}
```

---

## 📊 ENDPOINTS RF8: GESTIÓN DE CREMAS

### **1. GET /api/mis-cremas**
**Autenticación:** `auth:sanctum + role:vendor`

**Respuesta (200):**
```json
{
  "puesto_id": 1,
  "total_cremas": 3,
  "cremas": [
    {
      "id": 1,
      "nombre": "Queso Cheddar",
      "precio_adicional": 2.50,
      "descripcion": "Queso cheddar derretido",
      "activa": true,
      "productos_asociados": 5
    },
    {
      "id": 2,
      "nombre": "Mayonesa Casera",
      "precio_adicional": 0.50,
      "descripcion": null,
      "activa": true,
      "productos_asociados": 12
    }
  ]
}
```

---

### **2. POST /api/mis-cremas**
**Autenticación:** `auth:sanctum + role:vendor`

**Body:**
```json
{
  "nombre": "Guacamole",
  "precio_adicional": 3.00,
  "descripcion": "Guacamole fresco",
  "activa": true
}
```

**Validaciones:**
- `nombre`: Obligatorio, máx 255 caracteres
- `precio_adicional`: Obligatorio, numérico, mín 0
- `descripcion`: Opcional, máx 500 caracteres
- `activa`: Opcional (default: true)

**Respuesta (201):**
```json
{
  "message": "Crema creada exitosamente",
  "crema": {
    "id": 5,
    "nombre": "Guacamole",
    "precio_adicional": 3.00,
    "activa": true
  }
}
```

---

### **3. PATCH /api/mis-cremas/{id}**
**Autenticación:** `auth:sanctum + role:vendor`

**Body (todos opcionales):**
```json
{
  "nombre": "Guacamole Premium",
  "precio_adicional": 4.00,
  "descripcion": "Guacamole importado",
  "activa": false
}
```

**Respuesta (200):**
```json
{
  "message": "Crema actualizada exitosamente",
  "crema": {
    "id": 5,
    "nombre": "Guacamole Premium",
    "precio_adicional": 4.00,
    "activa": false
  }
}
```

---

### **4. POST /api/mis-productos/{productoId}/cremas**
**Autenticación:** `auth:sanctum + role:vendor`

Asociar una crema a un producto

**Body:**
```json
{
  "crema_id": 1,
  "requerida": false
}
```

**Parámetro:**
- `requerida`: Si true, el cliente DEBE seleccionar esta crema. Si false, es opcional.

**Respuesta (200):**
```json
{
  "message": "Crema asociada al producto",
  "producto_id": 1,
  "crema_id": 1,
  "requerida": false
}
```

---

### **5. GET /api/mis-productos/{productoId}/cremas**
**Autenticación:** `auth:sanctum + role:vendor`

Ver todas las cremas asociadas a un producto

**Respuesta (200):**
```json
{
  "producto_id": 1,
  "producto_nombre": "Salchipapas Classic",
  "total_cremas": 2,
  "cremas": [
    {
      "id": 1,
      "nombre": "Queso Cheddar",
      "precio_adicional": 2.50,
      "descripcion": "Queso cheddar derretido",
      "requerida": false
    },
    {
      "id": 2,
      "nombre": "Huevo frito",
      "precio_adicional": 1.50,
      "descripcion": "Huevo frito fresco",
      "requerida": true
    }
  ]
}
```

---

### **6. DELETE /api/mis-productos/{productoId}/cremas/{cremaId}**
**Autenticación:** `auth:sanctum + role:vendor`

Desasociar una crema de un producto

**Respuesta (200):**
```json
{
  "message": "Crema desasociada del producto"
}
```

---

## 📊 CLIENTE: VER MENÚ CON CREMAS

### **GET /api/menu/{stallId}**
**Autenticación:** NO requerida

**Respuesta (200) - ACTUALIZADO CON CREMAS:**
```json
{
  "puesto": {
    "id": 1,
    "nombre": "Salchipapas Don Juan",
    "slug": "salchipapas-don-juan-abc123",
    "direccion": "Jr. Lima 123",
    "telefono": "+51987654321",
    "horario_apertura": "11:00",
    "horario_cierre": "23:00",
    "activo": true,
    "descripcion": "Especialistas en salchipapas"
  },
  "productos": [
    {
      "id": 1,
      "nombre": "Salchipapas Classic",
      "precio": 8.50,
      "descripcion": "Papa con salchichón",
      "categoria": "Platos",
      "destacado": true,
      "imagen": "http://127.0.0.1:8000/storage/productos/1_1705088400.jpg",
      "cremas": [
        {
          "id": 1,
          "nombre": "Queso Cheddar",
          "precio_adicional": 2.50,
          "requerida": false
        },
        {
          "id": 2,
          "nombre": "Mayonesa",
          "precio_adicional": 0.50,
          "requerida": true
        }
      ]
    }
  ],
  "total_productos": 1,
  "usuario_autenticado": false
}
```

---

## GET /api/mis-productos (VENDEDOR)
**Autenticación:** `auth:sanctum + role:vendor`

**Respuesta (200) - AHORA INCLUYE CREMAS:**
```json
{
  "puesto_id": 1,
  "puesto_nombre": "Salchipapas Don Juan",
  "total_productos": 2,
  "productos": [
    {
      "id": 1,
      "nombre": "Salchipapas Classic",
      "descripcion": "Papa con salchichón",
      "precio": 8.50,
      "categoria": "Platos",
      "categoria_id": 1,
      "activo": true,
      "destacado": true,
      "imagen": "http://127.0.0.1:8000/storage/productos/1_1705088400.jpg",
      "creado_en": "2026-01-04T16:30:00Z",
      "cremas": [
        {
          "id": 1,
          "nombre": "Queso Cheddar",
          "precio_adicional": 2.50,
          "requerida": false
        }
      ]
    }
  ]
}
```

---

## 🧪 CASO DE USO COMPLETO

### **Escenario: Vendedor crea producto con cremas**

#### 1️⃣ Crear cremas disponibles
```bash
POST /api/mis-cremas
Authorization: Bearer TOKEN

{
  "nombre": "Queso Cheddar",
  "precio_adicional": 2.50,
  "descripcion": "Queso cheddar derretido",
  "activa": true
}

Respuesta: { "crema": { "id": 1, ... } }
```

#### 2️⃣ Crear producto
```bash
POST /api/mis-productos
Authorization: Bearer TOKEN
Content-Type: multipart/form-data

- nombre: "Salchipapas Premium"
- precio: 12.50
- categoria_id: 1
- imagen: <archivo.jpg>

Respuesta: { "producto": { "id": 5, ... } }
```

#### 3️⃣ Asociar crema al producto
```bash
POST /api/mis-productos/5/cremas
Authorization: Bearer TOKEN

{
  "crema_id": 1,
  "requerida": false
}

Respuesta: { "message": "Crema asociada al producto" }
```

#### 4️⃣ Cliente ve el menú completo
```bash
GET /api/menu/1

Respuesta incluye:
{
  "productos": [
    {
      "id": 5,
      "nombre": "Salchipapas Premium",
      "precio": 12.50,
      "imagen": "...",
      "cremas": [
        {
          "id": 1,
          "nombre": "Queso Cheddar",
          "precio_adicional": 2.50,
          "requerida": false
        }
      ]
    }
  ]
}
```

---

## 🔍 VALIDACIONES IMPLEMENTADAS

✅ Vendedor solo puede editar sus propias cremas y productos  
✅ Crema debe pertenecer al mismo puesto que el producto  
✅ Evita cremas duplicadas (unique constraint en tabla pivot)  
✅ Soporte de imágenes: JPEG, PNG, GIF (máx 2MB)  
✅ Caché se limpia automáticamente al cambiar datos  

---

## 📌 NOTAS IMPORTANTES

- Las cremas con `requerida: true` deben ser seleccionadas obligatoriamente en el pedido
- Las imágenes se almacenan en `/storage/public/productos/`
- El precio adicional de cremas se suma al total del pedido en RF11
- Las cremas inactivas no se mostrarán a clientes en el menú

---

## 🔗 RELACIÓN CON OTROS RFs

- **RF6**: Base de menú (ya implementado)
- **RF7**: Actualización de productos y upload de imágenes
- **RF8**: Gestión de cremas/acompañamientos
- **RF11**: Los precios de cremas se incluyen en el total del pedido
- **RF12**: Boleta incluye detalle de cremas seleccionadas

