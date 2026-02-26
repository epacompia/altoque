# ✅ CHECKLIST DE PRUEBAS - RF7 & RF8

## 📋 ANTES DE COMENZAR

- [ ] Laragon está corriendo
- [ ] Servidor PHP está activo: `php artisan serve --port=8000`
- [ ] Base de datos está actualizada: `php artisan migrate`
- [ ] Postman está instalado
- [ ] Tienes una cuenta vendedor con puesto creado

---

## 🧪 TESTING RF8: CREMAS

### Test 1: Crear primera crema

**Request:**
```
POST http://localhost:8000/api/mis-cremas
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "nombre": "Queso Cheddar",
  "precio_adicional": 2.50,
  "descripcion": "Queso cheddar derretido premium",
  "activa": true
}
```

**Validaciones:**
- [ ] Status code: **201**
- [ ] Response tiene `crema.id` (ej: 1)
- [ ] Response tiene `crema.nombre`: "Queso Cheddar"
- [ ] Response tiene `crema.precio_adicional`: 2.50

**Guarda:** `crema_id = 1` para usar luego

---

### Test 2: Crear más cremas

Repite el Test 1 pero con estos datos:

**Crema 2:**
```json
{
  "nombre": "Mayonesa Casera",
  "precio_adicional": 0.50,
  "activa": true
}
```
**Guarda:** `crema_id = 2`

---

**Crema 3:**
```json
{
  "nombre": "Huevo Frito",
  "precio_adicional": 1.50,
  "activa": true
}
```
**Guarda:** `crema_id = 3`

---

### Test 3: Verificar cremas creadas

**Request:**
```
GET http://localhost:8000/api/mis-cremas
Authorization: Bearer TOKEN
```

**Validaciones:**
- [ ] Status code: **200**
- [ ] `total_cremas`: **3**
- [ ] Array tiene 3 elementos
- [ ] Cada elemento tiene: `id`, `nombre`, `precio_adicional`, `activa`
- [ ] Orden alfabético por nombre

---

### Test 4: Actualizar crema

**Request:**
```
PATCH http://localhost:8000/api/mis-cremas/1
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "precio_adicional": 3.00
}
```

**Validaciones:**
- [ ] Status code: **200**
- [ ] `crema.precio_adicional` cambió a **3.00**
- [ ] Message: "Crema actualizada exitosamente"

---

### Test 5: Desactivar crema

**Request:**
```
PATCH http://localhost:8000/api/mis-cremas/2
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "activa": false
}
```

**Validaciones:**
- [ ] Status code: **200**
- [ ] `crema.activa`: **false**
- [ ] La crema sigue existiendo en BD (no se borra)

---

### Test 6: Verificar cambios en lista

**Request:**
```
GET http://localhost:8000/api/mis-cremas
Authorization: Bearer TOKEN
```

**Validaciones:**
- [ ] Crema 1 (Queso) tiene `precio_adicional`: **3.00**
- [ ] Crema 2 (Mayonesa) tiene `activa`: **false**
- [ ] Crema 3 (Huevo) sin cambios

---

## 🎁 TESTING RF7: PRODUCTOS CON IMÁGENES

### Test 7: Crear producto CON imagen

**Preparación:**
1. Descarga una imagen JPG o PNG pequeña
2. Toma nota de la ruta (ej: C:\Users\User\Pictures\salchipapas.jpg)

**Request (en Postman, usar Body → form-data):**
```
POST http://localhost:8000/api/mis-productos
Authorization: Bearer TOKEN
Content-Type: multipart/form-data

Campos:
- nombre: "Salchipapas Premium"
- descripcion: "Papa crispy con salchichón seleccionado"
- precio: "12.50"
- categoria_id: "1"
- activo: "true"
- destacado: "true"
- imagen: <archivo.jpg>
```

**Validaciones:**
- [ ] Status code: **201**
- [ ] `producto.id` es un número (ej: 1)
- [ ] `producto.nombre`: "Salchipapas Premium"
- [ ] `producto.precio`: 12.50
- [ ] `producto.imagen` comienza con "http://localhost:8000/storage"
- [ ] `producto.imagen` contiene "productos/"

**Guarda:** `producto_id = 1` y `imagen_url`

---

### Test 8: Verificar imagen guardada

**Acciones:**
1. Copia la URL de `producto.imagen` del test anterior
2. Abre en navegador: verifica que la imagen se ve correctamente
3. Verifica que el archivo existe en `/storage/public/productos/`

**Validaciones:**
- [ ] Imagen se ve correctamente en navegador
- [ ] Archivo existe en `/storage/public/productos/`

---

### Test 9: Actualizar producto CON imagen nueva

**Preparación:**
- Descarga una imagen diferente

**Request:**
```
PATCH http://localhost:8000/api/mis-productos/1
Authorization: Bearer TOKEN
Content-Type: multipart/form-data

Campos:
- nombre: "Salchipapas Premium Deluxe"
- precio: "14.50"
- imagen: <archivo_nuevo.jpg>
```

**Validaciones:**
- [ ] Status code: **200**
- [ ] `producto.nombre` cambió a "Salchipapas Premium Deluxe"
- [ ] `producto.precio` cambió a 14.50
- [ ] `producto.imagen` URL es diferente a la anterior (nueva imagen)

---

### Test 10: Verificar productos en lista

**Request:**
```
GET http://localhost:8000/api/mis-productos
Authorization: Bearer TOKEN
```

**Validaciones:**
- [ ] Status code: **200**
- [ ] Array `productos` tiene al menos 1 elemento
- [ ] Primer producto tiene:
  - [ ] `id`: 1
  - [ ] `nombre`: "Salchipapas Premium Deluxe"
  - [ ] `precio`: 14.50
  - [ ] `imagen` URL válida
  - [ ] `activo`: true
  - [ ] `destacado`: true
  - [ ] `cremas`: array (vacío por ahora)

---

## 🔗 TESTING ASOCIACIÓN: CREMAS → PRODUCTOS

### Test 11: Asociar primera crema (OPCIONAL)

**Request:**
```
POST http://localhost:8000/api/mis-productos/1/cremas
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "crema_id": 1,
  "requerida": false
}
```

**Validaciones:**
- [ ] Status code: **200**
- [ ] `producto_id`: 1
- [ ] `crema_id`: 1
- [ ] `requerida`: false

---

### Test 12: Asociar segunda crema (REQUERIDA)

**Request:**
```
POST http://localhost:8000/api/mis-productos/1/cremas
Authorization: Bearer TOKEN
Content-Type: application/json

{
  "crema_id": 3,
  "requerida": true
}
```

**Validaciones:**
- [ ] Status code: **200**
- [ ] `crema_id`: 3
- [ ] `requerida`: true

**Nota:** Crema 3 es Huevo Frito (porque Crema 2 la desactivamos)

---

### Test 13: Ver cremas del producto

**Request:**
```
GET http://localhost:8000/api/mis-productos/1/cremas
Authorization: Bearer TOKEN
```

**Validaciones:**
- [ ] Status code: **200**
- [ ] `producto_id`: 1
- [ ] `total_cremas`: 2
- [ ] Array tiene 2 elementos:
  - [ ] Elemento 1: Queso (precio_adicional: 3.00, requerida: false)
  - [ ] Elemento 2: Huevo (precio_adicional: 1.50, requerida: true)

---

### Test 14: Desasociar crema

**Request:**
```
DELETE http://localhost:8000/api/mis-productos/1/cremas/1
Authorization: Bearer TOKEN
```

**Validaciones:**
- [ ] Status code: **200**
- [ ] Message: "Crema desasociada del producto"

---

### Test 15: Verificar desasociación

**Request:**
```
GET http://localhost:8000/api/mis-productos/1/cremas
Authorization: Bearer TOKEN
```

**Validaciones:**
- [ ] `total_cremas`: 1 (antes eran 2)
- [ ] Array solo tiene:
  - [ ] Huevo Frito (no Queso)

---

## 👥 TESTING CLIENTE: MENÚ PÚBLICO

### Test 16: Ver menú PÚBLICO (SIN TOKEN)

**Request:**
```
GET http://localhost:8000/api/menu/1
```

**IMPORTANTE:** Sin header Authorization

**Validaciones:**
- [ ] Status code: **200** (acceso público)
- [ ] `puesto.nombre` existe
- [ ] `total_productos`: > 0
- [ ] `usuario_autenticado`: false

---

### Test 17: Verificar datos del menú público

**Request:** (misma que Test 16)

**Validaciones en `productos[0]`:**
- [ ] Tiene `id`, `nombre`, `precio`
- [ ] Tiene `imagen` URL válida
- [ ] Tiene `descripcion`
- [ ] Tiene `categoria`
- [ ] Tiene `destacado`: true
- [ ] Tiene `cremas`: array

---

### Test 18: Verificar cremas en menú público

**Validaciones en `productos[0].cremas`:**
- [ ] Array tiene 1 elemento (Huevo - lo desasociamos)
- [ ] Elemento tiene: `id`, `nombre`, `precio_adicional`, `requerida`
- [ ] `requerida`: true (porque así la asociamos)

---

### Test 19: Ver por SLUG (alternativo)

**Request:**
```
GET http://localhost:8000/api/menu/mi-puesto-abc123
```

(Reemplaza "abc123" con el slug real de tu puesto)

**Validaciones:**
- [ ] Status code: **200**
- [ ] Respuesta es idéntica al Test 16 (mismo menú)

---

### Test 20: Verificar solo productos ACTIVOS

**Preparación:**
1. En Postman, desactiva el primer producto: `PATCH /api/mis-productos/1` con `{"activo": false}`

**Request:**
```
GET http://localhost:8000/api/menu/1
```

**Validaciones:**
- [ ] Status code: **200**
- [ ] `total_productos` disminuyó
- [ ] El producto desactivado NO aparece en el menú

---

## 🔄 TESTING COMPLETO: FLUJO END-TO-END

Ejecuta estos tests en orden:

```
✅ Test 1: Crear crema 1
✅ Test 2: Crear crema 2 y 3
✅ Test 3: Ver cremas (3 totales)
✅ Test 4: Actualizar crema 1
✅ Test 5: Desactivar crema 2
✅ Test 6: Verificar cambios
✅ Test 7: Crear producto con imagen
✅ Test 8: Verificar imagen
✅ Test 9: Actualizar producto
✅ Test 10: Ver productos
✅ Test 11: Asociar crema 1 (opcional)
✅ Test 12: Asociar crema 3 (requerida)
✅ Test 13: Ver cremas del producto
✅ Test 14: Desasociar crema 1
✅ Test 15: Verificar desasociación
✅ Test 16: Ver menú público
✅ Test 17: Verificar datos menú
✅ Test 18: Verificar cremas menú
✅ Test 19: Ver por slug
✅ Test 20: Verificar inactividad
```

---

## 📊 RESUMEN ESPERADO FINAL

Después de todos los tests:

**Base de datos:**
- 3 cremas creadas (1 activa, 1 inactiva, 1 activa)
- 1 producto con imagen
- 1 asociación crema-producto

**Menú cliente:**
- 1 producto visible (el que está activo)
- 1 crema en ese producto (Huevo)
- Imagen visible en navegador

**Validaciones:**
- [ ] Todos los tests pasaron
- [ ] Imágenes se ven en navegador
- [ ] API responde correctamente
- [ ] Permisos funcionan (404 si no es tu producto)

---

## 🐛 SI ALGO FALLA

| Problema | Revisión |
|----------|----------|
| 401 Unauthorized | ¿Token válido? ¿Incluiste Bearer? |
| 403 Forbidden | ¿Eres vendedor? ¿Es tu puesto/producto? |
| 404 No encontrado | ¿El ID existe? ¿Es el correcto? |
| 422 Validation Error | ¿Faltan campos? ¿Tipos de datos correctos? |
| Imagen no se ve | Ejecuta `php artisan storage:link` |
| Cremas vacías | ¿La crema está `activa: true`? |

---

## ✨ AL TERMINAR

- [ ] Todos los tests pasaron ✅
- [ ] Imágenes visibles en navegador ✅
- [ ] Menú público muestra todo correcto ✅
- [ ] Documentación revisada ✅

¡**RF7 y RF8 están listos para producción!** 🚀

