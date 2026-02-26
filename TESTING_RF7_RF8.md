# 🧪 GUÍA DE TESTING - RF7 & RF8

## ✅ ESTADO ACTUAL

```
✅ RF1: Autenticación (Completado)
✅ RF2: Registro de usuarios (Completado)
✅ RF3: Recuperación de contraseña (Completado)
✅ RF6: Puestos y Menús básico (Completado)
✅ RF7: Configuración de menú CON IMÁGENES (✨ NUEVO)
✅ RF8: Gestión de cremas/acompañamientos (✨ NUEVO)
```

---

## 🚀 CÓMO PROBAR EN POSTMAN

### **Paso 1: Obtener TOKEN**

1. En Postman, importa el archivo: `ejemplos_requests/RF7_RF8_tests.json`
2. O crea un request manualmente:

```
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "email": "vendedor@example.com",
  "password": "password"
}
```

Copia el token recibido y reemplázalo en todos los requests (Authorization: Bearer TOKEN)

---

### **Paso 2: Crear Cremas**

```bash
POST http://localhost:8000/api/mis-cremas
Authorization: Bearer TU_TOKEN
Content-Type: application/json

{
  "nombre": "Queso Cheddar",
  "precio_adicional": 2.50,
  "descripcion": "Queso cheddar derretido",
  "activa": true
}
```

**Respuesta esperada (201):**
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

**Repite para crear más cremas:**
- Mayonesa (S/. 0.50)
- Huevo Frito (S/. 1.50)
- Guacamole (S/. 3.00)

---

### **Paso 3: Crear Producto CON IMAGEN**

```bash
POST http://localhost:8000/api/mis-productos
Authorization: Bearer TU_TOKEN
Content-Type: multipart/form-data

- nombre: "Salchipapas Premium"
- descripcion: "Papa crispy con salchichón premium"
- precio: 12.50
- categoria_id: 1
- activo: true
- destacado: true
- imagen: <selecciona una imagen JPG/PNG>
```

**Respuesta esperada (201):**
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

---

### **Paso 4: Asociar Cremas al Producto**

```bash
POST http://localhost:8000/api/mis-productos/1/cremas
Authorization: Bearer TU_TOKEN
Content-Type: application/json

{
  "crema_id": 1,
  "requerida": false
}
```

**Repite para todas las cremas que quieras asociar:**

```bash
POST /api/mis-productos/1/cremas
{ "crema_id": 2, "requerida": true }

POST /api/mis-productos/1/cremas
{ "crema_id": 3, "requerida": false }
```

---

### **Paso 5: Ver Producto CON CREMAS**

```bash
GET http://localhost:8000/api/mis-productos
Authorization: Bearer TU_TOKEN
```

**Respuesta esperada:**
```json
{
  "puesto_id": 1,
  "puesto_nombre": "Mi Puesto",
  "total_productos": 1,
  "productos": [
    {
      "id": 1,
      "nombre": "Salchipapas Premium",
      "precio": 12.50,
      "imagen": "http://127.0.0.1:8000/storage/productos/1_1705088400.jpg",
      "activo": true,
      "destacado": true,
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
  ]
}
```

---

### **Paso 6: Cliente Ve el Menú PÚBLICO**

```bash
GET http://localhost:8000/api/menu/1
(SIN TOKEN - acceso público)
```

**Respuesta esperada:**
```json
{
  "puesto": {
    "id": 1,
    "nombre": "Mi Puesto",
    "direccion": "Jr. Lima 123",
    "telefono": "+51987654321"
  },
  "productos": [
    {
      "id": 1,
      "nombre": "Salchipapas Premium",
      "precio": 12.50,
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
  ]
}
```

---

## 🧪 CASOS DE PRUEBA

### **Test 1: Crear crema válida**
✅ Debe aceptar: nombre, precio_adicional, descripcion, activa
❌ Debe rechazar si falta nombre o precio_adicional

### **Test 2: Crear producto con imagen**
✅ Debe aceptar: JPG, PNG, GIF (máx 2MB)
❌ Debe rechazar: archivos > 2MB o formatos no permitidos

### **Test 3: Asociar crema a producto**
✅ Debe permitir asociar crema del mismo puesto
❌ Debe rechazar crema de otro puesto

### **Test 4: Cliente ve menú con cremas**
✅ Solo muestra productos activos
✅ Incluye todas las cremas asociadas
✅ Funciona SIN token

### **Test 5: Vendedor ve cremas requeridas**
✅ `requerida: true` significa cliente DEBE seleccionar
✅ `requerida: false` significa es opcional

---

## 🔍 VERIFICACIÓN DE DATOS EN BD

Si necesitas verificar directamente en la BD:

```sql
-- Ver todas las cremas
SELECT * FROM toppings WHERE stall_id = 1;

-- Ver cremas asociadas a un producto
SELECT t.* FROM toppings t
JOIN menu_item_topping mit ON t.id = mit.topping_id
WHERE mit.menu_item_id = 1;

-- Ver si una crema es requerida
SELECT required FROM menu_item_topping 
WHERE menu_item_id = 1 AND topping_id = 2;
```

---

## 🐛 TROUBLESHOOTING

### **Error: "No tienes un puesto registrado"**
→ El usuario debe tener un puesto creado primero (RF6)

### **Error: "No tienes permiso para editar esta crema"**
→ Estás intentando editar crema de otro vendedor

### **La imagen no se sube**
→ Verifica que sea multipart/form-data y el archivo < 2MB

### **Las cremas no aparecen en el menú cliente**
→ Verifica que la crema esté activa (`active = true`)

### **Las rutas no existen**
→ Ejecuta: `php artisan route:list | grep mis-`

---

## 📝 NOTAS IMPORTANTES

1. **Caché:** El menú se cachea 60 segundos. Si cambias datos y no ves los cambios, espera o limpia caché con:
   ```bash
   php artisan cache:clear
   ```

2. **Imágenes:** Se guardan en `/storage/public/productos/`
   Asegúrate de crear el directorio:
   ```bash
   php artisan storage:link
   ```

3. **Permisos:** Todos los endpoints requieren `auth:sanctum` + `role:vendor` (excepto el menú público)

4. **Validaciones:** Las cremas se validan para que pertenezcan al mismo puesto

---

## ✅ CHECKLIST DE TESTING

- [ ] Crear 3 cremas diferentes
- [ ] Crear producto con imagen
- [ ] Asociar cremas al producto (incluyendo requeridas)
- [ ] Ver producto con cremas (VENDEDOR)
- [ ] Ver menú público con cremas (CLIENTE SIN LOGIN)
- [ ] Actualizar crema (cambiar precio)
- [ ] Desactivar crema
- [ ] Desasociar crema del producto
- [ ] Verificar que solo se muestran cremas activas
- [ ] Verificar que solo se muestran productos activos

---

## 📦 RECURSOS DISPONIBLES

- **Documentación completa:** `RF7_RF8_DOCUMENTATION.md`
- **Ejemplos Postman:** `ejemplos_requests/RF7_RF8_tests.json`
- **Resumen:** `RF7_RF8_SUMMARY.md`
- **Este archivo:** `TESTING_RF7_RF8.md`

---

## 🎉 ¡LISTO PARA PRODUCCIÓN!

RF7 y RF8 están 100% implementados y listos para testing.
Una vez validado, puedes proceder con RF11 (Pagos).

