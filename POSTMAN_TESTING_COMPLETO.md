# 🧪 GUÍA COMPLETA DE TESTING EN POSTMAN - RF7 & RF8

## ⚠️ PASO 0: OBTENER TOKEN (OBLIGATORIO)

Todos los endpoints (excepto menú público) requieren token de autenticación.

### **Opción A: Si tienes un usuario vendedor registrado**

```
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "email": "vendedor@example.com",
  "password": "password"
}
```

**Respuesta (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 2,
    "name": "Juan Vendedor",
    "email": "vendedor@example.com",
    "role": "vendor"
  }
}
```

**👉 COPIA EL TOKEN y úsalo en todos los requests siguientes**

---

### **Opción B: Si NO tienes usuario registrado**

1. Primero, crea una cuenta de cliente:
```
POST http://localhost:8000/api/register
Content-Type: application/json

{
  "nombre": "Carlos",
  "apellidos": "Perez",
  "email": "carlos@example.com",
  "celular": "987654321",
  "password": "password123",
  "password_confirmation": "password123"
}
```

2. Luego, conviértete a vendedor:
```
POST http://localhost:8000/api/convertirse-vendedor
Authorization: Bearer TOKEN_DEL_CLIENTE
Content-Type: application/json

{
  "razon_social": "Salchipapas Don Juan",
  "numero_puesto": "001"
}
```

3. Vuelve a hacer login como vendedor para obtener el nuevo token

---

## 📝 ENDPOINTS RF8: GESTIÓN DE CREMAS

### **1. POST - CREAR CREMA**

**URL:**
```
POST http://localhost:8000/api/mis-cremas
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "nombre": "Queso Cheddar",
  "precio_adicional": 2.50,
  "descripcion": "Queso cheddar derretido premium",
  "activa": true
}
```

**✅ Respuesta esperada (201):**
```json
{
  "message": "Crema creada exitosamente",
  "crema": {
    "id": 1,
    "nombre": "Queso Cheddar",
    "precio_adicional": 2.50,
    "activa": true
  }
}
```

**📌 NOTAS:**
- `nombre`: Obligatorio
- `precio_adicional`: Obligatorio, mínimo 0
- `descripcion`: Opcional
- `activa`: Opcional (default: true)

**❌ Errores comunes:**

| Error | Causa | Solución |
|-------|-------|----------|
| 401 Unauthorized | Falta token o token inválido | Incluir header `Authorization: Bearer TOKEN` |
| 403 Forbidden | No eres vendedor | Primero conviértete a vendedor |
| 422 Validation Error | Falta campo obligatorio | Incluir `nombre` y `precio_adicional` |
| 404 | No tienes puesto registrado | Crear un puesto primero (RF6) |

---

### **2. GET - VER TODAS LAS CREMAS**

**URL:**
```
GET http://localhost:8000/api/mis-cremas
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
```

**Body:** (vacío)

**✅ Respuesta esperada (200):**
```json
{
  "puesto_id": 1,
  "total_cremas": 3,
  "cremas": [
    {
      "id": 1,
      "nombre": "Queso Cheddar",
      "precio_adicional": 2.50,
      "descripcion": "Queso cheddar derretido premium",
      "activa": true,
      "productos_asociados": 2
    },
    {
      "id": 2,
      "nombre": "Mayonesa Casera",
      "precio_adicional": 0.50,
      "descripcion": null,
      "activa": true,
      "productos_asociados": 5
    }
  ]
}
```

**📌 NOTAS:**
- Muestra todas tus cremas (activas e inactivas)
- `productos_asociados`: cuántos productos tienen esta crema

---

### **3. PATCH - ACTUALIZAR CREMA**

**URL:**
```
PATCH http://localhost:8000/api/mis-cremas/1
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
```

**Body (ejemplo 1: cambiar precio):**
```json
{
  "precio_adicional": 3.00
}
```

**✅ Respuesta (200):**
```json
{
  "message": "Crema actualizada exitosamente",
  "crema": {
    "id": 1,
    "nombre": "Queso Cheddar",
    "precio_adicional": 3.00,
    "activa": true
  }
}
```

---

**Body (ejemplo 2: cambiar nombre):**
```json
{
  "nombre": "Queso Cheddar Premium"
}
```

---

**Body (ejemplo 3: desactivar crema):**
```json
{
  "activa": false
}
```

**✅ Respuesta:**
```json
{
  "message": "Crema actualizada exitosamente",
  "crema": {
    "id": 1,
    "nombre": "Queso Cheddar",
    "precio_adicional": 3.00,
    "activa": false
  }
}
```

**📌 NOTAS:**
- Todos los campos son opcionales
- Puedes actualizar solo lo que necesites
- Si desactivas una crema, NO se verá en menú cliente

---

## 📦 ENDPOINTS RF8: ASOCIAR CREMAS A PRODUCTOS

### **4. POST - ASOCIAR CREMA A PRODUCTO (OPCIONAL)**

**URL:**
```
POST http://localhost:8000/api/mis-productos/1/cremas
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
```

**Body:**
```json
{
  "crema_id": 1,
  "requerida": false
}
```

**✅ Respuesta (200):**
```json
{
  "message": "Crema asociada al producto",
  "producto_id": 1,
  "crema_id": 1,
  "requerida": false
}
```

**📌 NOTAS:**
- `requerida: false` = Cliente PUEDE elegir esta crema (opcional)
- `requerida: true` = Cliente DEBE elegir esta crema (obligatoria)

---

### **5. POST - ASOCIAR CREMA A PRODUCTO (REQUERIDA)**

**URL:**
```
POST http://localhost:8000/api/mis-productos/1/cremas
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: application/json
```

**Body:**
```json
{
  "crema_id": 2,
  "requerida": true
}
```

**✅ Respuesta (200):**
```json
{
  "message": "Crema asociada al producto",
  "producto_id": 1,
  "crema_id": 2,
  "requerida": true
}
```

**📌 NOTAS:**
- Ahora el cliente DEBE seleccionar "Mayonesa" al pedir este producto

---

### **6. GET - VER CREMAS DE UN PRODUCTO**

**URL:**
```
GET http://localhost:8000/api/mis-productos/1/cremas
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
```

**Body:** (vacío)

**✅ Respuesta (200):**
```json
{
  "producto_id": 1,
  "producto_nombre": "Salchipapas Classic",
  "total_cremas": 3,
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
      "nombre": "Mayonesa",
      "precio_adicional": 0.50,
      "descripcion": null,
      "requerida": true
    },
    {
      "id": 3,
      "nombre": "Huevo Frito",
      "precio_adicional": 1.50,
      "descripcion": "Recién hecho",
      "requerida": false
    }
  ]
}
```

---

### **7. DELETE - DESASOCIAR CREMA DE PRODUCTO**

**URL:**
```
DELETE http://localhost:8000/api/mis-productos/1/cremas/2
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
```

**Body:** (vacío)

**✅ Respuesta (200):**
```json
{
  "message": "Crema desasociada del producto"
}
```

**📌 NOTAS:**
- Quita la crema del producto
- La crema sigue existiendo (no se borra)
- Otros productos pueden seguir usándola

---

## 🎁 ENDPOINTS RF7: PRODUCTOS CON IMÁGENES (MEJORADOS)

### **8. POST - CREAR PRODUCTO CON IMAGEN** ✨

**URL:**
```
POST http://localhost:8000/api/mis-productos
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: multipart/form-data
```

**Body (Form-data, NO JSON):**

| Campo | Tipo | Valor |
|-------|------|-------|
| nombre | text | Salchipapas Premium |
| descripcion | text | Papa crispy con salchichón seleccionado |
| precio | text | 12.50 |
| categoria_id | text | 1 |
| activo | text | true |
| destacado | text | true |
| imagen | file | <selecciona un .jpg o .png> |

**Pasos en Postman:**
1. Selecciona el tab "Body"
2. Selecciona "form-data" (NOT raw JSON)
3. Agrega cada campo como muestra la tabla arriba
4. Para "imagen", selecciona "File" en el dropdown y carga una imagen

**✅ Respuesta (201):**
```json
{
  "message": "Producto creado exitosamente",
  "producto": {
    "id": 1,
    "nombre": "Salchipapas Premium",
    "precio": 12.50,
    "activo": true,
    "destacado": true,
    "imagen": "http://127.0.0.1:8000/storage/productos/1_1705088400.jpg"
  }
}
```

**📌 NOTAS IMPORTANTES:**
- Usa `form-data`, NO JSON raw
- La imagen es OPCIONAL
- Formatos válidos: JPEG, PNG, GIF
- Tamaño máximo: 2MB
- Se guarda en `/storage/public/productos/`

**❌ Errores comunes:**

| Error | Causa | Solución |
|-------|-------|----------|
| 422 imagen must be an image | Archivo no es imagen | Carga JPG, PNG o GIF válido |
| 422 imagen may not be greater than 2048 kilobytes | Imagen muy grande | Comprimir la imagen a < 2MB |
| 422 categoria_id exists | Categoría no existe | Usar una categoría válida |

---

### **9. PATCH - ACTUALIZAR PRODUCTO CON IMAGEN** ✨

**URL:**
```
PATCH http://localhost:8000/api/mis-productos/1
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: multipart/form-data
```

**Body (Form-data - todos opcionales):**

| Campo | Tipo | Valor |
|-------|------|-------|
| nombre | text | Salchipapas Premium Deluxe |
| precio | text | 14.50 |
| imagen | file | <nueva_imagen.jpg> |

**✅ Respuesta (200):**
```json
{
  "message": "Producto actualizado exitosamente",
  "producto": {
    "id": 1,
    "nombre": "Salchipapas Premium Deluxe",
    "precio": 14.50,
    "activo": true,
    "destacado": true,
    "imagen": "http://127.0.0.1:8000/storage/productos/1_1705088401.jpg"
  }
}
```

**📌 NOTAS:**
- Todos los campos son opcionales
- Si no incluyes imagen, se mantiene la anterior
- Si incluyes imagen nueva, reemplaza la anterior
- Usa `form-data`, NO JSON

---

### **10. GET - VER TODOS MIS PRODUCTOS (CON CREMAS)** ✨

**URL:**
```
GET http://localhost:8000/api/mis-productos
```

**Headers:**
```
Authorization: Bearer YOUR_TOKEN_HERE
```

**Body:** (vacío)

**✅ Respuesta (200):**
```json
{
  "puesto_id": 1,
  "puesto_nombre": "Mi Puesto",
  "total_productos": 2,
  "productos": [
    {
      "id": 1,
      "nombre": "Salchipapas Premium",
      "descripcion": "Papa crispy con salchichón",
      "precio": 12.50,
      "categoria": "Platos",
      "categoria_id": 1,
      "activo": true,
      "destacado": true,
      "imagen": "http://127.0.0.1:8000/storage/productos/1_1705088400.jpg",
      "creado_en": "2026-01-12T10:30:00Z",
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
    },
    {
      "id": 2,
      "nombre": "Salchipapas Classica",
      "descripcion": "La tradicional",
      "precio": 8.50,
      "categoria": "Platos",
      "categoria_id": 1,
      "activo": true,
      "destacado": false,
      "imagen": "http://127.0.0.1:8000/storage/productos/1_1705088401.jpg",
      "creado_en": "2026-01-12T11:00:00Z",
      "cremas": []
    }
  ]
}
```

**✨ LO NUEVO:**
- Ahora incluye `imagen` con URL completa
- Ahora incluye array `cremas` con todas asociadas
- Muestra `requerida` para cada crema

---

## 👥 ENDPOINT CLIENTE: MENÚ PÚBLICO (MEJORADO)

### **11. GET - VER MENÚ PÚBLICO CON CREMAS E IMÁGENES** ✨

**URL:**
```
GET http://localhost:8000/api/menu/1
```

**O por slug:**
```
GET http://localhost:8000/api/menu/mi-puesto-abc123
```

**Headers:** (OPCIONAL - sin token, acceso público)
```
(vacío o sin Authorization)
```

**Body:** (vacío)

**✅ Respuesta (200):**
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
      "nombre": "Salchipapas Premium",
      "precio": 12.50,
      "descripcion": "Papa crispy con salchichón",
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
        },
        {
          "id": 3,
          "nombre": "Huevo Frito",
          "precio_adicional": 1.50,
          "requerida": false
        }
      ]
    },
    {
      "id": 2,
      "nombre": "Broaster Premium",
      "precio": 18.00,
      "descripcion": "Pollo frito crujiente",
      "categoria": "Platos",
      "destacado": false,
      "imagen": "http://127.0.0.1:8000/storage/productos/1_1705088401.jpg",
      "cremas": [
        {
          "id": 1,
          "nombre": "Queso Cheddar",
          "precio_adicional": 2.50,
          "requerida": true
        }
      ]
    }
  ],
  "total_productos": 2,
  "usuario_autenticado": false
}
```

**✨ LO NUEVO:**
- Incluye `imagen` con URL completa
- Incluye array `cremas` con detalles
- Muestra `requerida` para cada crema
- **SIN autenticación necesaria**

**📌 NOTAS:**
- Solo muestra productos `activo: true`
- Solo muestra cremas `activa: true`
- `destacado: true` = aparece primero (Oferta del Día)
- Los productos inactivos del vendedor NO se ven aquí

---

## 🔄 FLUJO COMPLETO DE TESTING

### **Paso 1: Crear cremas (5 minutos)**

```
POST /api/mis-cremas → Queso Cheddar (S/. 2.50)
POST /api/mis-cremas → Mayonesa (S/. 0.50)
POST /api/mis-cremas → Huevo Frito (S/. 1.50)
POST /api/mis-cremas → Guacamole (S/. 3.00)
```

Verificar: `GET /api/mis-cremas` (debe mostrar 4 cremas)

---

### **Paso 2: Crear producto con imagen (5 minutos)**

```
POST /api/mis-productos 
- Nombre: "Salchipapas Premium"
- Precio: S/. 12.50
- Imagen: <carga una imagen JPG/PNG>
- Destacado: true (para que aparezca como Oferta del Día)
```

**Guarda el ID del producto (ej: id=1)**

---

### **Paso 3: Asociar cremas al producto (5 minutos)**

```
POST /api/mis-productos/1/cremas
{ "crema_id": 1, "requerida": false }  → Queso: opcional

POST /api/mis-productos/1/cremas
{ "crema_id": 2, "requerida": true }   → Mayonesa: requerida

POST /api/mis-productos/1/cremas
{ "crema_id": 3, "requerida": false }  → Huevo: opcional
```

Verificar: `GET /api/mis-productos/1/cremas` (debe mostrar 3 cremas)

---

### **Paso 4: Verificar producto con cremas (2 minutos)**

```
GET /api/mis-productos
```

Respuesta debe incluir:
- ✅ Imagen URL completa
- ✅ Array de cremas con precios y requerida
- ✅ Producto marcado como destacado

---

### **Paso 5: Cliente ve menú público (2 minutos)**

```
GET /api/menu/1
```

SIN TOKEN - acceso público

Respuesta debe incluir:
- ✅ Imagen URL completa
- ✅ Array de cremas
- ✅ Solo productos activos
- ✅ Orden: destacados primero

---

## ⚠️ CHECKLIST DE VALIDACIÓN

Después de cada test, verifica:

### **Test 1: Crear crema**
- [ ] Status code 201
- [ ] Response incluye `crema.id`
- [ ] `GET /api/mis-cremas` muestra la nueva crema

### **Test 2: Crear producto con imagen**
- [ ] Status code 201
- [ ] Response incluye `imagen` URL
- [ ] Imagen existe en `/storage/productos/`
- [ ] `GET /api/mis-productos` muestra la imagen

### **Test 3: Asociar crema**
- [ ] Status code 200
- [ ] `GET /api/mis-productos/1/cremas` muestra la crema

### **Test 4: Ver producto con cremas**
- [ ] Incluye array `cremas`
- [ ] Muestra `precio_adicional` correcto
- [ ] Muestra `requerida` correctamente

### **Test 5: Cliente ve menú**
- [ ] Status code 200 SIN TOKEN
- [ ] Incluye `imagen` URL
- [ ] Incluye `cremas` array
- [ ] Solo productos `activo: true`

---

## 🐛 TROUBLESHOOTING

| Problema | Solución |
|----------|----------|
| 401 Unauthorized | Verificar que incluiste header `Authorization: Bearer TOKEN` |
| 403 Forbidden | Verificar que tienes rol `vendor` (conviértete a vendedor) |
| 404 No tienes puesto registrado | Crear un puesto primero en RF6 |
| 422 imagen must be an image | Carga JPG, PNG o GIF válido |
| Imagen no aparece | Ejecutar `php artisan storage:link` |
| Cremas no aparecen | Verificar que `activa: true` |
| Caché antigua | Ejecutar `php artisan cache:clear` |

---

## 📞 URLS RESUMEN

```
Cremas:
POST   http://localhost:8000/api/mis-cremas
GET    http://localhost:8000/api/mis-cremas
PATCH  http://localhost:8000/api/mis-cremas/1

Productos:
POST   http://localhost:8000/api/mis-productos
GET    http://localhost:8000/api/mis-productos
PATCH  http://localhost:8000/api/mis-productos/1

Asociar cremas:
POST   http://localhost:8000/api/mis-productos/1/cremas
GET    http://localhost:8000/api/mis-productos/1/cremas
DELETE http://localhost:8000/api/mis-productos/1/cremas/1

Menú público (SIN TOKEN):
GET    http://localhost:8000/api/menu/1
GET    http://localhost:8000/api/menu/salchipapas-don-juan
```

---

¡Listo! Sigue los pasos y valida cada endpoint. 🎉

