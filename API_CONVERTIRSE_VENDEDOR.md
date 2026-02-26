# API: Convertirse en Vendedor

## Endpoint
```
POST /api/convertirse-vendedor
```

## Autenticación
- **Requerida**: Sí (Bearer Token Sanctum)
- **Rol permitido**: `client`

## Descripción
Permite a un cliente convertirse en vendedor. Este endpoint:
1. Valida que el usuario sea cliente (`role = 'client'`)
2. Valida que no tenga ya un puesto de comida registrado
3. Recibe datos del puesto (nombre, dirección, horario, etc.)
4. Crea un registro en `food_stalls`
5. Cambia el rol del usuario a `vendor`
6. Retorna el usuario actualizado y el puesto creado

---

## Request Headers
```
Authorization: Bearer {token}
Content-Type: application/json
```

## Request Body
```json
{
  "nombre_puesto": "Salchipapas Don Juan",
  "direccion": "Jr. Lima 123, Breña",
  "telefono": "987654321",
  "horario_apertura": "11:00",
  "horario_cierre": "23:00",
  "latitud": -12.0693,
  "longitud": -77.0703,
  "descripcion": "Las mejores salchipapas del distrito"
}
```

## Parámetros

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `nombre_puesto` | string | ✓ | Nombre del puesto (máx 100 caracteres, debe ser único) |
| `direccion` | string | ✓ | Dirección del puesto (máx 255 caracteres) |
| `telefono` | string | ✓ | Teléfono del puesto (máx 20 caracteres) |
| `horario_apertura` | time | ✓ | Hora de apertura (formato HH:mm) |
| `horario_cierre` | time | ✓ | Hora de cierre (formato HH:mm, debe ser posterior a apertura) |
| `latitud` | float | ✗ | Latitud GPS (-90 a 90) |
| `longitud` | float | ✗ | Longitud GPS (-180 a 180) |
| `descripcion` | string | ✗ | Descripción del puesto (máx 500 caracteres) |

---

## Response: Success (201 Created)
```json
{
  "message": "¡Felicitaciones! Ahora eres vendedor",
  "usuario": {
    "id": 1,
    "nombre": "Juan Pérez García",
    "email": "juan@example.com",
    "rol": "vendor",
    "nombre_puesto": "Juan",
    "apellido": "Pérez García"
  },
  "puesto": {
    "id": 1,
    "nombre": "Salchipapas Don Juan",
    "slug": "salchipapas-don-juan",
    "seller_id": 1,
    "direccion": "Jr. Lima 123, Breña",
    "telefono": "987654321",
    "horario_apertura": "11:00:00",
    "horario_cierre": "23:00:00",
    "latitud": "-12.06930000",
    "longitud": "-77.07030000",
    "descripcion": "Las mejores salchipapas del distrito",
    "activo": true,
    "qr_path": null,
    "creado_en": "2026-01-04T12:30:00Z"
  }
}
```

---

## Response: Error Cases

### 409 - Usuario ya es vendedor
```json
{
  "error": "Solo los clientes pueden convertirse en vendedores",
  "rol_actual": "vendor"
}
```

### 409 - Usuario ya tiene un puesto
```json
{
  "error": "Ya eres vendedor. No puedes crear otro puesto.",
  "puesto_existente": "Salchipapas Don Juan"
}
```

### 422 - Errores de validación
```json
{
  "error": "Errores de validación",
  "errores": {
    "nombre_puesto": [
      "El campo nombre del puesto es obligatorio",
      "El campo nombre del puesto ya ha sido usado"
    ],
    "direccion": [
      "El campo dirección es obligatorio"
    ],
    "horario_cierre": [
      "El horario de cierre debe ser posterior al de apertura"
    ]
  }
}
```

### 401 - No autenticado
```json
{
  "error": "No autenticado"
}
```

### 403 - Rol no permitido
```json
{
  "error": "No tienes permiso para acceder a este recurso",
  "rol_requerido": ["client"],
  "rol_actual": "vendor"
}
```

### 500 - Error del servidor
```json
{
  "error": "Error al convertir a vendedor",
  "detalles": "Mensaje de error específico"
}
```

---

## Validaciones

### Reglas aplicadas:
- ✓ `nombre_puesto`: Obligatorio, máx 100 caracteres, único en BD
- ✓ `direccion`: Obligatorio, máx 255 caracteres
- ✓ `telefono`: Obligatorio, máx 20 caracteres
- ✓ `horario_apertura`: Obligatorio, formato HH:mm válido
- ✓ `horario_cierre`: Obligatorio, formato HH:mm válido, posterior a apertura
- ✓ `latitud`: Opcional, debe estar entre -90 y 90
- ✓ `longitud`: Opcional, debe estar entre -180 y 180
- ✓ `descripcion`: Opcional, máx 500 caracteres

---

## Flujo de uso (desde el Mobile)

1. **Usuario logueado como cliente** inicia sesión con Google
   ```
   Token: eyJ0eXAiOiJKV1QiLCJhbGc... (Sanctum Token)
   Rol: client
   ```

2. **Usuario selecciona botón "Convertirse en vendedor"**
   - Se abre formulario para ingresar datos del puesto

3. **Usuario completa el formulario** con los datos del puesto
   ```json
   {
     "nombre_puesto": "Salchipapas Don Juan",
     "direccion": "Jr. Lima 123",
     "telefono": "987654321",
     "horario_apertura": "11:00",
     "horario_cierre": "23:00"
   }
   ```

4. **App envía POST a `/api/convertirse-vendedor`**
   ```bash
   curl -X POST http://localhost/api/convertirse-vendedor \
     -H "Authorization: Bearer {token}" \
     -H "Content-Type: application/json" \
     -d '{...datos...}'
   ```

5. **Backend procesa y retorna 201**
   - Usuario ahora tiene `role = vendor`
   - Se creó el puesto con `seller_id = user_id`
   - Se generó slug automático

6. **App actualiza su UI**
   - Muestra menú de vendedor
   - Redirige al panel de vendedor
   - Usuario puede ahora gestionar su menú (RF6)

---

## Cambios en Base de Datos

### Nueva Migración: `add_fields_to_food_stalls_table`
Agrega campos a la tabla `food_stalls`:
- `address` (string, nullable)
- `phone` (string, nullable)
- `opening_time` (time, nullable)
- `closing_time` (time, nullable)
- `latitude` (decimal, nullable)
- `longitude` (decimal, nullable)
- `description` (text, nullable)

### Cambios en Modelo `FoodStall`
- Actualizado `$fillable` con nuevos campos
- Relación existente `owner()` se mantiene

### Cambios en Modelo `User`
- Agregada relación `foodStall()` → hasOne(FoodStall)

---

## Auditoría
Cada conversión exitosa se registra en logs:
```
Usuario convertido a vendedor
user_id: 1
email: juan@example.com
stall_id: 1
stall_name: Salchipapas Don Juan
timestamp: 2026-01-04T12:30:00Z
```

---

## Próximos pasos (RF6)
Una vez el usuario es vendedor, puede:
- Generar QR único para el puesto
- Agregar productos al menú
- Activar/desactivar productos por día
- Destacar ofertas del día
