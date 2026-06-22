# ANÁLISIS FUNCIONAL DEL PROYECTO — ALTOQUE

---

## ÍNDICE DE MÓDULOS

| # | Módulo | RF | Endpoints |
|---|--------|----|-----------|
| 1 | Autenticación | RF1-RF3 | `/api/register`, `/api/login`, `/api/logout` |
| 2 | Google OAuth | RF1 | `/auth/google`, `/auth/google/callback` |
| 3 | Perfil de Usuario | RF2 | `PUT /api/user/update` |
| 4 | Recuperación de Contraseña | RF3 | `/api/send-otp`, `/api/verify-otp`, `/api/reset-password` |
| 5 | Dashboard | RF4 | `GET /api/dashboard` |
| 6 | Conversión a Vendedor | RF5 | `POST /api/convertirse-vendedor` |
| 7 | Gestión del Puesto | RF6 | `GET /api/mis-puesto`, `POST /api/mis-puesto/generar-qr` |
| 8 | Categorías | RF6 | `GET /api/mis-categorias`, `POST /api/mis-categorias` |
| 9 | Productos / Menú | RF7 | CRUD `/api/mis-productos` |
| 10 | Menú Público | RF7 | `GET /api/menu/{stallId}` |
| 11 | Cremas / Toppings | RF8 | CRUD `/api/mis-cremas`, asociación a productos |
| 12 | Menú Offline | RF9 | `GET /api/mis-productos/offline` |
| 13 | Horarios y Pausas | RF10 | CRUD `/api/mis-puesto/horarios`, `/api/mis-puesto/pausas` |
| 14 | Tiempo de Preparación | RF13 | `PUT /api/mis-puesto/tiempos-preparacion` |
| 15 | Pedidos (Cliente) | RF11-RF12 | CRUD `/api/pedidos` |
| 16 | Estados del Pedido | RF14 | `PATCH /api/pedidos/{id}/cambiar-estado` |
| 17 | Cancelar Pedido | RF15 | `PATCH /api/pedidos/{id}/cancelar` |
| 18 | Cambiar Dirección | RF16 | `PATCH /api/pedidos/{id}/cambiar-direccion` |
| 19 | Pedidos del Vendedor | RF17 | `GET /api/mis-pedidos` |
| 20 | Geo / Mapa | RF28-RF32 | `/api/puestos/nearby`, `/api/puestos/{id}/map`, etc. |
| 21 | Comisiones | RF18-RF19 | `/api/comisiones/*` |
| 22 | Transferencias | RF20 | `POST /api/comisiones/transferir` |
| 23 | Comprobantes | RF21 | `/api/comprobantes/*` |

---

## MÓDULO 1: AUTENTICACIÓN (RF1-RF3)

### 1.1 Registro de Usuario

**Endpoint:** `POST /api/register`

**Autenticación:** No requiere

**Request:**
```json
{
    "name": "Juan Perez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201 - Creado):**
```json
{
    "success": true,
    "message": "Registro exitoso",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Perez",
            "email": "juan@example.com",
            "role": "client"
        },
        "token": "1|abc123def456...",
        "token_type": "Bearer"
    }
}
```

**Response (422 - Validación):**
```json
{
    "success": false,
    "message": "Error de validación",
    "errors": {
        "email": ["El email ya está registrado"]
    }
}
```

**Reglas de validación:**
- `name`: Requerido, string, max 255
- `email`: Requerido, email, único en tabla users, max 255, lowercase
- `password`: Requerido, min 8 caracteres, confirmado (debe enviar `password_confirmation`)

**Comportamiento:**
- El usuario se registra con rol `client` por defecto
- Se genera automáticamente un token Sanctum
- Se retorna el token en la respuesta para uso inmediato

---

### 1.2 Inicio de Sesión

**Endpoint:** `POST /api/login`

**Autenticación:** No requiere

**Request:**
```json
{
    "email": "juan@example.com",
    "password": "password123"
}
```

**Response (200 - Éxito):**
```json
{
    "success": true,
    "message": "Inicio de sesión exitoso",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Perez",
            "email": "juan@example.com",
            "role": "client"
        },
        "token": "1|abc123def456...",
        "token_type": "Bearer"
    }
}
```

**Response (401 - Credenciales inválidas):**
```json
{
    "success": false,
    "message": "Credenciales inválidas",
    "errors": {
        "email": ["Las credenciales no coinciden con nuestros registros."]
    }
}
```

**Comportamiento:**
- Verifica email y password contra la BD
- Si las credenciales son correctas, genera un nuevo token Sanctum
- Si son incorrectas, retorna 401 con mensaje de error

---

### 1.3 Cierre de Sesión

**Endpoint:** `POST /api/logout`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Headers:**
```
Authorization: Bearer 1|abc123def456...
Accept: application/json
```

**Request body:** No requiere

**Response (200 - Éxito):**
```json
{
    "success": true,
    "message": "Sesión cerrada exitosamente"
}
```

**Comportamiento:**
- Revoca el token actual del usuario (currentAccessToken)
- El token deja de ser válido para futuras requests

---

### 1.4 Test Token (Desarrollo)

**Endpoint:** `GET /api/test-token`

**Autenticación:** No requiere

**Query params:** `?user_type=cliente|vendedor|admin`

**Response (200):** Retorna un token de prueba con el rol especificado para desarrollo/testing.

---

## MÓDULO 2: GOOGLE OAUTH (RF1)

### 2.1 Redirección a Google

**Endpoint:** `GET /auth/google`

**Autenticación:** No requiere

**Comportamiento:** Redirige al navegador a la página de login de Google.

### 2.2 Callback de Google

**Endpoint:** `GET /auth/google/callback?code=CODIGO_DE_GOOGLE`

**Autenticación:** No requiere

**Query params:** `code` — Código de autorización devuelto por Google

**Comportamiento:** Google redirige con el código de autorización. El backend intercambia el código por un token de acceso y crea/autentica al usuario. Retorna un token Sanctum.

---

## MÓDULO 3: PERFIL DE USUARIO (RF2)

### 3.1 Actualizar Datos del Usuario

**Endpoint:** `PUT /api/user/update`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Request:**
```json
{
    "phone": "987546758",
    "dni": "43908196",
    "address": "Av. Lima 123",
    "district": "Villa el Salvador",
    "department": "Lima"
}
```

**Response (200 - Éxito):**
```json
{
    "message": "Datos actualizados correctamente",
    "user": {
        "id": 1,
        "name": "Juan Perez",
        "email": "juan@example.com",
        "phone": "987546758",
        "dni": "43908196",
        "address": "Av. Lima 123",
        "district": "Villa el Salvador",
        "department": "Lima"
    }
}
```

**Reglas de validación:**
- `phone`: Opcional, string, max 15
- `dni`: Opcional, regex 8 dígitos exactos
- `address`: Opcional, string, max 255
- `district`: Opcional, string, max 255
- `department`: Opcional, string, max 255

---

## MÓDULO 4: RECUPERACIÓN DE CONTRASEÑA (RF3)

### 4.1 Enviar OTP por SMS

**Endpoint:** `POST /api/send-otp`

**Autenticación:** No requiere

**Request:**
```json
{
    "phone": "987546758"
}
```

**Response (200 - Éxito):**
```json
{
    "message": "OTP enviado correctamente"
}
```

**Response (404 - No registrado):**
```json
{
    "message": "Número de celular no está registrado"
}
```

**Comportamiento:**
- Normaliza el número (quita +51 o 51 del inicio)
- Busca al usuario por teléfono
- Genera OTP de 6 dígitos aleatorio
- Guarda en tabla `password_resets` con expiración de 5 minutos
- Envía SMS vía Twilio con el código OTP

### 4.2 Verificar OTP

**Endpoint:** `POST /api/verify-otp`

**Autenticación:** No requiere

**Request:**
```json
{
    "phone": "987546758",
    "otp": "123456"
}
```

**Response (200 - Válido):**
```json
{
    "message": "OTP verificado. Proceda a cambiar la contraseña"
}
```

**Response (400 - Inválido):**
```json
{
    "message": "Código OTP inválido o ha expirado"
}
```

**Comportamiento:** Verifica que el OTP exista, coincida y no haya expirado (5 min).

### 4.3 Restablecer Contraseña

**Endpoint:** `POST /api/reset-password`

**Autenticación:** No requiere

**Request:**
```json
{
    "phone": "987546758",
    "otp": "123456",
    "password": "nuevaPassword123",
    "password_confirmation": "nuevaPassword123"
}
```

**Response (200 - Éxito):**
```json
{
    "message": "Contraseña actualizada correctamente"
}
```

**Comportamiento:**
- Verifica OTP nuevamente
- Actualiza la contraseña del usuario con Hash::make
- Elimina el registro OTP de la tabla

---

## MÓDULO 5: DASHBOARD (RF4)

### 5.1 Obtener Dashboard

**Endpoint:** `GET /api/dashboard`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Response (200):**
```json
{
    "role": "client",
    "quick_access": [
        { "icon": "shopping_cart", "label": "Nuevo Pedido", "route": "/pedidos" },
        { "icon": "history", "label": "Mis Pedidos", "route": "/mis-pedidos" },
        { "icon": "map", "label": "Mapa de Puestos", "route": "/mapa" },
        { "icon": "receipt", "label": "Comprobantes", "route": "/comprobantes" }
    ],
    "suggestions": [
        {
            "title": "Salchipapas Clásicas",
            "image": "/images/salchipapas.jpg",
            "route": "/menu/1",
            "description": "Deliciosas salchipapas",
            "total_consumo": 5
        }
    ]
}
```

**Comportamiento:**
- Retorna accesos rápidos según el rol del usuario (client / vendor)
- Sugerencias personalizadas basadas en el historial de consumo del usuario
- Si no hay historial, muestra sugerencias por defecto
- Para rol `vendor` muestra: Mi Puesto, Menú, Horarios, Pedidos Activos, Comisiones, etc.

---

## MÓDULO 6: CONVERSIÓN A VENDEDOR (RF5)

### 6.1 Convertirse en Vendedor

**Endpoint:** `POST /api/convertirse-vendedor`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:client`

**Request:**
```json
{
    "nombre_puesto": "Anticuchos de la Tía Rosa",
    "direccion": "Av. Grau 456, Lima",
    "telefono": "987654321",
    "horario_apertura": "10:00",
    "horario_cierre": "22:00",
    "latitud": -12.0464,
    "longitud": -77.0428,
    "descripcion": "Los mejores anticuchos de Lima"
}
```

**Response (201 - Creado):**
```json
{
    "message": "¡Felicitaciones! Ahora eres vendedor",
    "usuario": {
        "id": 1,
        "nombre": "Juan Perez",
        "email": "juan@example.com",
        "rol": "vendor"
    },
    "puesto": {
        "id": 1,
        "nombre": "Anticuchos de la Tía Rosa",
        "slug": "anticuchos-de-la-tia-rosa",
        "seller_id": 1,
        "direccion": "Av. Grau 456, Lima",
        "telefono": "987654321",
        "horario_apertura": "10:00",
        "horario_cierre": "22:00",
        "latitud": -12.0464,
        "longitud": -77.0428,
        "descripcion": "Los mejores anticuchos de Lima",
        "activo": true,
        "creado_en": "2026-03-03T12:00:00.000000Z"
    }
}
```

**Reglas de validación:**
- `nombre_puesto`: Requerido, string, max 100, único en food_stalls
- `direccion`: Requerido, string, max 255
- `telefono`: Requerido, string, max 20
- `horario_apertura`: Requerido, formato H:i
- `horario_cierre`: Requerido, formato H:i, después de apertura
- `latitud`: Opcional, numeric, entre -90 y 90
- `longitud`: Opcional, numeric, entre -180 y 180
- `descripcion`: Opcional, string, max 500

**Comportamiento:**
- Verifica que el usuario tenga rol `client`
- Verifica que no tenga ya un puesto creado
- Crea el FoodStall con slug único automático
- Cambia el rol del usuario de `client` a `vendor`
- El slug se genera con Str::slug y se asegura que sea único

---

## MÓDULO 7: GESTIÓN DEL PUESTO (RF6)

### 7.1 Obtener Mi Puesto

**Endpoint:** `GET /api/mis-puesto`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "puesto": {
        "id": 1,
        "nombre": "Anticuchos de la Tía Rosa",
        "slug": "anticuchos-de-la-tia-rosa",
        "direccion": "Av. Grau 456, Lima",
        "telefono": "987654321",
        "horario_apertura": "10:00",
        "horario_cierre": "22:00",
        "latitud": -12.0464,
        "longitud": -77.0428,
        "descripcion": "Los mejores anticuchos de Lima",
        "qr_path": "qr_codes/stall_1_1234567890.svg",
        "activo": true,
        "creado_en": "2026-03-03T12:00:00.000000Z"
    }
}
```

### 7.2 Generar QR del Menú

**Endpoint:** `POST /api/mis-puesto/generar-qr`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "message": "QR generado exitosamente",
    "qr_url": "http://127.0.0.1:8000/storage/qr_codes/stall_1_1234567890.svg",
    "codigo_puesto": "STALL-1",
    "menu_url": "http://127.0.0.1:3000/menu/anticuchos-de-la-tia-rosa",
    "generado_en": "2026-03-03T12:00:00.000000Z"
}
```

**Comportamiento:**
- Genera QR en formato SVG (no requiere imagick)
- El QR apunta al menú público del puesto usando el slug
- Guarda la ruta del QR en la BD
- Usa config `app.frontend_url` para la URL base del menú

---

## MÓDULO 8: CATEGORÍAS (RF6)

### 8.1 Listar Categorías

**Endpoint:** `GET /api/mis-categorias`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "total_categorias": 3,
    "categorias": [
        { "id": 1, "nombre": "Anticuchos" },
        { "id": 2, "nombre": "Bebidas" },
        { "id": 3, "nombre": "Postres" }
    ]
}
```

### 8.2 Crear Categoría

**Endpoint:** `POST /api/mis-categorias`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request:**
```json
{
    "nombre": "Anticuchos"
}
```

**Response (201):**
```json
{
    "message": "Categoría creada exitosamente",
    "categoria": {
        "id": 1,
        "nombre": "Anticuchos"
    }
}
```

**Reglas de validación:**
- `nombre`: Requerido, string, max 255, único en categories

---

## MÓDULO 9: PRODUCTOS / MENÚ (RF7)

### 9.1 Listar Mis Productos

**Endpoint:** `GET /api/mis-productos`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "puesto_id": 1,
    "puesto_nombre": "Anticuchos de la Tía Rosa",
    "total_productos": 2,
    "productos": [
        {
            "id": 1,
            "nombre": "Anticucho de Corazón",
            "descripcion": "Anticucho de corazón de res con papas doradas",
            "precio": 12.50,
            "categoria": "Anticuchos",
            "categoria_id": 1,
            "activo": true,
            "destacado": false,
            "imagen": null,
            "creado_en": "2026-03-03T12:00:00.000000Z",
            "cremas": [
                {
                    "id": 1,
                    "nombre": "Crema de Ají",
                    "precio_adicional": 1.50,
                    "requerida": false
                }
            ]
        },
        {
            "id": 2,
            "nombre": "Combo Anticucho + Chicha",
            "descripcion": "Oferta especial: Anticucho completo con chicha morada",
            "precio": 18.00,
            "categoria": "Anticuchos",
            "categoria_id": 1,
            "activo": true,
            "destacado": true,
            "imagen": "productos/1_1234567890.jpg",
            "creado_en": "2026-03-03T12:00:00.000000Z",
            "cremas": []
        }
    ]
}
```

**Comportamiento:** Lista todos los productos (activos e inactivos) con sus cremas asociadas.

### 9.2 Crear Producto

**Endpoint:** `POST /api/mis-productos`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request (multipart/form-data):**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `nombre` | string | Sí | Nombre del producto |
| `descripcion` | string | No | Descripción |
| `precio` | numeric | Sí | Precio (mínimo 0.01) |
| `categoria_id` | integer | Sí | ID de categoría existente |
| `activo` | boolean | No | Por defecto true |
| `destacado` | boolean | No | Por defecto false |
| `imagen` | file | No | jpeg, png, jpg, gif — max 2MB |

**Response (201):**
```json
{
    "message": "Producto creado exitosamente",
    "producto": {
        "id": 1,
        "nombre": "Anticucho de Corazón",
        "precio": 12.50,
        "activo": true,
        "destacado": false,
        "imagen": "http://127.0.0.1:8000/storage/productos/1_1234567890.jpg"
    }
}
```

### 9.3 Actualizar Producto

**Endpoint:** `PATCH /api/mis-productos/{id}`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request (algunos campos):**
```json
{
    "nombre": "Anticucho de Corazón Premium",
    "precio": 15.00,
    "activo": false
}
```

**Response (200):**
```json
{
    "message": "Producto actualizado exitosamente",
    "producto": {
        "id": 1,
        "nombre": "Anticucho de Corazón Premium",
        "precio": 15.00,
        "activo": false,
        "destacado": false
    }
}
```

**Reglas de validación (todos opcionales):**
- `nombre`: string, max 255
- `descripcion`: string, max 1000
- `precio`: numeric, min 0.01
- `categoria_id`: debe existir en categories
- `activo`: boolean
- `destacado`: boolean

**Comportamiento:** Verifica que el producto pertenezca al puesto del vendedor autenticado.

---

## MÓDULO 10: MENÚ PÚBLICO (RF7)

### 10.1 Ver Menú (Sin Autenticación)

**Endpoint:** `GET /api/menu/{stallId}`

**Autenticación:** No requiere (público)

**Response (200):**
```json
{
    "puesto": {
        "id": 1,
        "nombre": "Anticuchos de la Tía Rosa",
        "slug": "anticuchos-de-la-tia-rosa",
        "direccion": "Av. Grau 456, Lima",
        "telefono": "987654321",
        "horario_apertura": "10:00",
        "horario_cierre": "22:00",
        "activo": true,
        "descripcion": "Los mejores anticuchos de Lima"
    },
    "productos": [
        {
            "id": 1,
            "nombre": "Anticucho de Corazón",
            "precio": 12.50,
            "descripcion": "Anticucho de corazón de res con papas doradas",
            "categoria": "Anticuchos",
            "destacado": false,
            "imagen": null,
            "cremas": [
                {
                    "id": 1,
                    "nombre": "Crema de Ají",
                    "precio_adicional": 1.50,
                    "requerida": false
                }
            ]
        }
    ],
    "total_productos": 2,
    "usuario_autenticado": false
}
```

**Comportamiento:**
- `stallId` puede ser el ID numérico o el slug del puesto
- Solo muestra productos activos y cremas activas
- Usa caché (60 segundos) para optimizar
- Si se envía un Bearer Token válido, `usuario_autenticado` será `true`

---

## MÓDULO 11: CREMAS / TOPPINGS (RF8)

### 11.1 Listar Cremas

**Endpoint:** `GET /api/mis-cremas`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "puesto_id": 1,
    "total_cremas": 2,
    "cremas": [
        {
            "id": 1,
            "nombre": "Crema de Ají",
            "precio_adicional": 1.50,
            "descripcion": "Crema de ají amarillo casera",
            "activa": true,
            "productos_asociados": 1
        },
        {
            "id": 2,
            "nombre": "Salsa de Tamarindo",
            "precio_adicional": 2.00,
            "descripcion": "Salsa agridulce de tamarindo",
            "activa": true,
            "productos_asociados": 0
        }
    ]
}
```

### 11.2 Crear Crema

**Endpoint:** `POST /api/mis-cremas`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request:**
```json
{
    "nombre": "Crema de Ají",
    "precio_adicional": 1.50,
    "descripcion": "Crema de ají amarillo casera",
    "activa": true
}
```

**Response (201):**
```json
{
    "message": "Crema creada exitosamente",
    "crema": {
        "id": 1,
        "nombre": "Crema de Ají",
        "precio_adicional": 1.50,
        "activa": true
    }
}
```

### 11.3 Actualizar Crema

**Endpoint:** `PATCH /api/mis-cremas/{id}`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request:**
```json
{
    "nombre": "Crema de Ají Especial",
    "precio_adicional": 2.00,
    "activa": true
}
```

**Response (200):**
```json
{
    "message": "Crema actualizada exitosamente",
    "crema": {
        "id": 1,
        "nombre": "Crema de Ají Especial",
        "precio_adicional": 2.00,
        "activa": true
    }
}
```

### 11.4 Eliminar Crema

**Endpoint:** `DELETE /api/mis-cremas/{id}`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "message": "Crema eliminada exitosamente"
}
```

**Comportamiento:** Desasocia la crema de todos los productos antes de eliminarla.

### 11.5 Asociar Crema a Producto

**Endpoint:** `POST /api/mis-productos/{productoId}/cremas`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request:**
```json
{
    "crema_id": 1,
    "requerida": false
}
```

**Response (200):**
```json
{
    "message": "Crema asociada al producto",
    "producto_id": 1,
    "crema_id": 1,
    "requerida": false
}
```

### 11.6 Ver Cremas de un Producto

**Endpoint:** `GET /api/mis-productos/{productoId}/cremas`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "producto_id": 1,
    "producto_nombre": "Anticucho de Corazón",
    "total_cremas": 1,
    "cremas": [
        {
            "id": 1,
            "nombre": "Crema de Ají",
            "precio_adicional": 1.50,
            "descripcion": "Crema de ají amarillo casera",
            "requerida": false
        }
    ]
}
```

### 11.7 Desasociar Crema de Producto

**Endpoint:** `DELETE /api/mis-productos/{productoId}/cremas/{cremaId}`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "message": "Crema desasociada del producto"
}
```

---

## MÓDULO 12: MENÚ OFFLINE (RF9)

### 12.1 Descargar Menú Offline

**Endpoint:** `GET /api/mis-productos/offline`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "puesto_id": 1,
    "puesto_nombre": "Anticuchos de la Tía Rosa",
    "productos": [
        {
            "id": 1,
            "nombre": "Anticucho de Corazón",
            "descripcion": "Anticucho de corazón de res con papas doradas",
            "precio": 12.50,
            "categoria": "Anticuchos",
            "categoria_id": 1,
            "destacado": false,
            "imagen": null,
            "cremas": [
                {
                    "id": 1,
                    "nombre": "Crema de Ají",
                    "precio_adicional": 1.50,
                    "requerida": false
                }
            ]
        }
    ]
}
```

**Comportamiento:**
- Solo retorna productos activos
- Incluye categorías y cremas activas
- Diseñado para que el vendedor descargue y use sin conexión

---

## MÓDULO 13: HORARIOS Y PAUSAS (RF10)

### 13.1 Obtener Horarios del Puesto

**Endpoint:** `GET /api/mis-puesto/horarios`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "puesto_id": 1,
    "puesto_nombre": "Anticuchos de la Tía Rosa",
    "horario": {
        "apertura": "10:00",
        "cierre": "22:00",
        "activo": true
    },
    "estado_actual": {
        "abierto_ahora": true,
        "en_pausa_ahora": false,
        "hora_actual": "14:30:00"
    }
}
```

**Comportamiento:** Los horarios se cachean por 3600 segundos. Se invalida al actualizar.

### 13.2 Actualizar Horarios

**Endpoint:** `PUT /api/mis-puesto/horarios`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request:**
```json
{
    "horario_apertura": "10:00",
    "horario_cierre": "23:30",
    "activo": true
}
```

**Response (200):**
```json
{
    "message": "Horarios actualizados exitosamente",
    "horario": {
        "apertura": "10:00",
        "cierre": "23:30",
        "activo": true
    }
}
```

**Reglas:**
- `horario_apertura`: Requerido, formato H:i
- `horario_cierre`: Requerido, formato H:i, debe ser posterior a apertura
- `activo`: Opcional, boolean

### 13.3 Crear Pausa Temporal

**Endpoint:** `POST /api/mis-puesto/pausas`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request:**
```json
{
    "razon": "Descanso para almuerzo",
    "inicio": "2026-03-03 13:00",
    "fin": "2026-03-03 14:00"
}
```

**Response (201):**
```json
{
    "message": "Pausa temporal creada exitosamente",
    "pausa": {
        "id": 1,
        "razon": "Descanso para almuerzo",
        "inicio": "2026-03-03 13:00:00",
        "fin": "2026-03-03 14:00:00",
        "activa": true
    }
}
```

**Reglas:**
- `razon`: Opcional, string, max 255
- `inicio`: Requerido, formato `Y-m-d H:i`
- `fin`: Requerido, formato `Y-m-d H:i`, después de inicio

### 13.4 Listar Pausas

**Endpoint:** `GET /api/mis-puesto/pausas`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "puesto_id": 1,
    "total_pausas": 3,
    "pausas": [
        {
            "id": 1,
            "razon": "Descanso para almuerzo",
            "inicio": "2026-03-03 13:00:00",
            "fin": "2026-03-03 14:00:00",
            "activa": true,
            "es_vigente": false
        }
    ]
}
```

**Comportamiento:** Las pausas se cachean por 600 segundos. Se invalidan al crear, actualizar o eliminar.

### 13.5 Actualizar Pausa

**Endpoint:** `PATCH /api/mis-puesto/pausas/{id}`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request:**
```json
{
    "razon": "Descanso extendido",
    "fin": "2026-03-03 14:30",
    "activa": true
}
```

**Response (200):**
```json
{
    "message": "Pausa actualizada exitosamente",
    "pausa": {
        "id": 1,
        "razon": "Descanso extendido",
        "inicio": "2026-03-03 13:00:00",
        "fin": "2026-03-03 14:30:00",
        "activa": true
    }
}
```

### 13.6 Eliminar Pausa

**Endpoint:** `DELETE /api/mis-puesto/pausas/{id}`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "message": "Pausa eliminada exitosamente"
}
```

---

## MÓDULO 14: TIEMPO DE PREPARACIÓN (RF13)

### 14.1 Configurar Tiempos de Preparación

**Endpoint:** `PUT /api/mis-puesto/tiempos-preparacion`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request:**
```json
{
    "base_preparation_time": 15,
    "time_per_product": 5,
    "time_per_active_order": 5,
    "time_for_delivery": 10
}
```

**Response (200):**
```json
{
    "message": "Tiempos de preparación actualizados",
    "tiempos_configurados": {
        "base_preparation_time": "15 min",
        "time_per_product": "5 min",
        "time_per_active_order": "5 min",
        "time_for_delivery": "10 min"
    },
    "calculo_actual": {
        "productos_activos": 5,
        "ordenes_activas": 2,
        "formula": "Base(15) + productos(5*5) + ordenes_activas(5*2) + delivery(10) = 60 min",
        "tiempo_estimado_actual": "60 min"
    }
}
```

**Fórmula de cálculo:**
```
tiempo_total = base_preparation_time
             + (time_per_product * cantidad_productos_pedido)
             + (time_per_active_order * ordenes_activas_en_puesto)
             + (time_for_delivery si aplica)
```

**Reglas de validación:**
- `base_preparation_time`: Opcional, integer, min 5, max 60
- `time_per_product`: Opcional, integer, min 1, max 30
- `time_per_active_order`: Opcional, integer, min 1, max 30
- `time_for_delivery`: Opcional, integer, min 1, max 60

---

## MÓDULO 15: PEDIDOS — CLIENTE (RF11-RF12)

### 15.1 Crear Pedido (Sin Pago)

**Endpoint:** `POST /api/pedidos`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:client`

**Request:**
```json
{
    "stall_id": 1,
    "productos": [
        {
            "producto_id": 1,
            "cantidad": 2,
            "cremas": [1, 2]
        },
        {
            "producto_id": 2,
            "cantidad": 1
        }
    ],
    "direccion": "Jr. Manco Cápac 456, La Breña, Lima",
    "with_delivery": true,
    "delivery_latitude": -12.0600,
    "delivery_longitude": -77.0400,
    "notas": "Sin cebolla por favor",
    "invoice_requested": false,
    "invoice_type": null,
    "customer_document_type": null,
    "customer_document_number": null,
    "customer_name": null
}
```

**Response (201):**
```json
{
    "message": "Pedido creado. Procede al pago.",
    "orden": {
        "id": 1,
        "estado": "pending",
        "puesto_nombre": "Anticuchos de la Tía Rosa",
        "subtotal": 30.50,
        "igv": 5.49,
        "delivery": 3.00,
        "total": 38.99,
        "direccion_entrega": "Jr. Manco Cápac 456, La Breña, Lima"
    },
    "items_pedido": [
        {
            "producto_id": 1,
            "cantidad": 2,
            "precio_unitario": 12.50,
            "subtotal": 28.00,
            "cremas": [
                { "id": 1, "nombre": "Crema de Ají", "precio": 1.50 },
                { "id": 2, "nombre": "Salsa de Tamarindo", "precio": 2.00 }
            ]
        }
    ],
    "siguiente_paso": "POST /api/pedidos/1/pagar"
}
```

**Reglas de validación:**
- `stall_id`: Requerido, debe existir en food_stalls
- `productos`: Requerido, array, mínimo 1
- `productos.*.producto_id`: Requerido, debe existir en menu_items
- `productos.*.cantidad`: Requerido, integer, mínimo 1
- `productos.*.cremas`: Opcional, array de IDs de toppings
- `direccion`: Requerido, string, max 255
- `with_delivery`: Opcional, boolean
- `delivery_latitude`: Opcional, numeric
- `delivery_longitude`: Opcional, numeric
- `notas`: Opcional, string, max 500
- `invoice_requested`: Opcional, boolean
- `invoice_type`: Opcional, "boleta" o "factura"
- `customer_document_type`: Opcional, "DNI" o "RUC"
- `customer_document_number`: Opcional, string, max 20
- `customer_name`: Opcional, string, max 255

**Comportamiento del flujo:**
1. Verifica que el puesto esté abierto (isOpenNow)
2. Verifica que el puesto no esté en pausa (isInPauseNow)
3. Verifica que cada producto esté activo
4. Calcula subtotal sumando precio de productos y cremas
5. Calcula IGV (18%)
6. Calcula delivery usando DeliveryService (fórmula Haversine)
7. Calcula tiempo estimado de entrega (RF13)
8. Crea la orden en estado `pending`
9. Crea los OrderItem con sus cremas en JSON
10. Valida coherencia: si `invoice_type` es "factura", requiere `RUC` como documento

### 15.2 Pagar Pedido (Async)

**Endpoint:** `POST /api/pedidos/{id}/pagar`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:client`

**Request:**
```json
{
    "metodo_pago": "mock"
}
```

**Response (202 - Accepted):**
```json
{
    "message": "Pago en procesamiento",
    "orden": {
        "id": 1,
        "estado": "pending",
        "estado_pago": "procesando",
        "puesto_nombre": "Anticuchos de la Tía Rosa",
        "total": 38.99,
        "metodo_pago": "mock",
        "nota": "El pago se está procesando. Verifica el estado en GET /api/pedidos/1"
    }
}
```

**Reglas de validación:**
- `metodo_pago`: Requerido, valores permitidos: `mock`, `yape`, `plin`

**Comportamiento:**
- Verifica que el pedido pertenezca al usuario autenticado
- Verifica que el pedido esté en estado `pending`
- Guarda el método de pago en la orden
- Dispara `ProcessPaymentJob` a la cola para procesar en background
- Responde inmediatamente con 202 Accepted
- El job `ProcessPaymentJob` ejecuta `PaymentService::processPayment()` que:
  1. Simula el gateway de pago
  2. Crea el registro `Payment`
  3. Actualiza el pedido a `confirmed`
  4. Registra entrada en `CompanyAccountEntry` (crédito)
  5. Calcula la comisión vía `CommissionService`
  6. Si `transfer_mode` es `real_time`, dispara `ExecuteTransferJob`
  7. Si se solicitó comprobante (`invoice_requested`), genera la factura

### 15.3 Obtener Detalle del Pedido

**Endpoint:** `GET /api/pedidos/{id}`

**Autenticación:** `Bearer Token` (auth:sanctum) — Dueño del pedido o vendedor del puesto

**Response (200):**
```json
{
    "pedido": {
        "id": 1,
        "estado": "delivered",
        "puesto_nombre": "Anticuchos de la Tía Rosa",
        "cliente_nombre": "Juan Perez",
        "direccion_entrega": "Jr. Manco Cápac 456, La Breña, Lima",
        "subtotal": 30.50,
        "igv": 5.49,
        "delivery": 3.00,
        "total": 38.99,
        "metodo_pago": "mock",
        "notas": "Sin cebolla por favor",
        "created_at": "2026-03-03T12:00:00.000000Z",
        "estimated_delivery_at": "2026-03-03T12:45:00.000000Z"
    },
    "items": [
        {
            "producto": "Anticucho de Corazón",
            "cantidad": 2,
            "precio_unitario": 12.50,
            "subtotal": 28.00,
            "cremas": [
                { "id": 1, "nombre": "Crema de Ají", "precio": 1.50 }
            ]
        }
    ],
    "pago": {
        "metodo": "mock",
        "estado": "completed",
        "transaction_id": "MOCK-ABCD-123456789"
    },
    "timeline": [
        {
            "estado": "pending",
            "descripcion": "Pedido creado",
            "fecha": "2026-03-03T12:00:00.000000Z"
        },
        {
            "estado": "confirmed",
            "descripcion": "Pago confirmado",
            "fecha": "2026-03-03T12:01:00.000000Z"
        },
        {
            "estado": "preparing",
            "descripcion": "En preparación",
            "fecha": "2026-03-03T12:05:00.000000Z"
        },
        {
            "estado": "ready",
            "descripcion": "Listo para entrega",
            "fecha": "2026-03-03T12:30:00.000000Z"
        },
        {
            "estado": "en_camino",
            "descripcion": "En camino",
            "fecha": "2026-03-03T12:35:00.000000Z"
        },
        {
            "estado": "delivered",
            "descripcion": "Entregado",
            "fecha": "2026-03-03T12:45:00.000000Z"
        }
    ]
}
```

**Comportamiento:** Verifica que el usuario autenticado sea el cliente que hizo el pedido o el vendedor dueño del puesto. Incluye timeline completo de estados.

### 15.4 Listar Mis Pedidos (Cliente)

**Endpoint:** `GET /api/pedidos?page=1`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:client`

**Response (200):**
```json
{
    "total": 10,
    "pagina": 1,
    "por_pagina": 10,
    "pedidos": [
        {
            "id": 1,
            "puesto_nombre": "Anticuchos de la Tía Rosa",
            "estado": "delivered",
            "total": 38.99,
            "cantidad_items": 2,
            "created_at": "2026-03-03T12:00:00.000000Z"
        }
    ]
}
```

**Comportamiento:** Lista los pedidos del cliente autenticado con paginación (10 por página), ordenados por fecha descendente.

---

## MÓDULO 16: ESTADOS DEL PEDIDO — VENDEDOR (RF14)

### 16.1 Cambiar Estado del Pedido

**Endpoint:** `PATCH /api/pedidos/{id}/cambiar-estado`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Request:**
```json
{
    "nuevo_estado": "preparing"
}
```

**Máquina de estados (transiciones permitidas):**

```
pending → confirmed, cancelled
confirmed → preparing, cancelled
preparing → ready, cancelled
ready → en_camino, delivered, cancelled
en_camino → delivered, cancelled
delivered → (ninguna)
cancelled → (ninguna)
```

**Response (200):**
```json
{
    "message": "Estado actualizado correctamente",
    "orden_id": 1,
    "estado_anterior": "confirmed",
    "estado_nuevo": "preparing",
    "actualizado_en": "2026-03-03T12:05:00.000000Z",
    "orden": {
        "id": 1,
        "user_id": 2,
        "stall_id": 1,
        "status": "preparing",
        "subtotal": 30.50,
        "igv": 5.49,
        "delivery_cost": 3.00,
        "total": 38.99,
        "direccion": "Jr. Manco Cápac 456, La Breña, Lima",
        "notas": "Sin cebolla por favor",
        "timeline": {
            "pending": "2026-03-03T12:00:00.000000Z",
            "confirmed": "2026-03-03T12:01:00.000000Z",
            "preparing": "2026-03-03T12:05:00.000000Z",
            "ready": null,
            "en_camino": null,
            "delivered": null
        }
    }
}
```

**Response (409 - Transición inválida):**
```json
{
    "error": "No se puede pasar de 'delivered' a 'pending'",
    "estado_actual": "delivered",
    "transiciones_permitidas": []
}
```

**Reglas de validación:**
- `nuevo_estado`: Requerido, valores permitidos: `confirmed`, `preparing`, `ready`, `en_camino`, `delivered`, `cancelled`

**Comportamiento:**
- Verifica que el vendedor sea dueño del puesto asociado al pedido
- Valida la transición según la máquina de estados
- Registra timestamp automático para el nuevo estado (confirmed_at, preparing_at, etc.)

---

## MÓDULO 17: CANCELAR PEDIDO (RF15)

### 17.1 Cancelar Pedido (Cliente)

**Endpoint:** `PATCH /api/pedidos/{id}/cancelar`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:client`

**Request:**
```json
{
    "motivo": "Ya no quiero el pedido"
}
```

**Response (200):**
```json
{
    "message": "Pedido cancelado exitosamente",
    "orden_id": 1,
    "estado_anterior": "pending",
    "estado_nuevo": "cancelled",
    "total_cancelado": 38.99,
    "nota": "El dinero será reembolsado a tu método de pago original"
}
```

**Estados permitidos para cancelación:** `pending`, `confirmed`, `preparing`

**Estados NO permitidos:** `ready`, `en_camino`, `delivered`, `cancelled`

**Comportamiento adicional:**
- Si el pedido tenía pago completado: marca el pago como `refunded`, revierte la comisión vía `CommissionService::refundCommission()`, registra débito contable
- Si el pedido tenía delivery: registra débito por devolución del costo de delivery
- El campo `motivo` es opcional

---

## MÓDULO 18: CAMBIAR DIRECCIÓN (RF16)

### 18.1 Cambiar Dirección de Entrega

**Endpoint:** `PATCH /api/pedidos/{id}/cambiar-direccion`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:client`

**Request:**
```json
{
    "nueva_direccion": "Av. Arequipa 1234, Lince, Lima"
}
```

**Response (200):**
```json
{
    "message": "Dirección actualizada exitosamente",
    "orden_id": 1,
    "direccion_anterior": "Jr. Manco Cápac 456, La Breña, Lima",
    "direccion_nueva": "Av. Arequipa 1234, Lince, Lima",
    "estado_pedido": "pending"
}
```

**Reglas de validación:**
- `nueva_direccion`: Requerido, string, max 255, mínimo 10 caracteres

**Estados permitidos:** `pending`, `confirmed`

**Estados NO permitidos:** `preparing`, `ready`, `en_camino`, `delivered`, `cancelled`

---

## MÓDULO 19: PEDIDOS DEL VENDEDOR (RF17)

### 19.1 Listar Pedidos Activos

**Endpoint:** `GET /api/mis-pedidos`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:vendor`

**Response (200):**
```json
{
    "puesto_nombre": "Anticuchos de la Tía Rosa",
    "pedidos_activos": 3,
    "pedidos": [
        {
            "id": 1,
            "cliente_nombre": "Juan Perez",
            "cliente_telefono": "987654321",
            "estado": "confirmed",
            "total": 38.99,
            "items_count": 2,
            "direccion_entrega": "Jr. Manco Cápac 456, La Breña, Lima",
            "notas": "Sin cebolla por favor",
            "created_at": "2026-03-03T12:00:00.000000Z",
            "estimated_delivery_at": "2026-03-03T12:45:00.000000Z"
        }
    ]
}
```

**Comportamiento:**
- Filtra solo pedidos en estados: `confirmed`, `preparing`, `ready`
- Ordena por fecha de creación ascendente (los más antiguos primero)
- Incluye datos del cliente (nombre, teléfono) y dirección de entrega

---

## MÓDULO 20: GEO / MAPA (RF28-RF32)

### 20.1 Puestos Cercanos (Nearby)

**Endpoint:** `GET /api/puestos/nearby?lat=-12.0464&lng=-77.0428&radius=1&open_now=false&sort=distance&page=1&per_page=10`

**Autenticación:** No requiere

**Query params:**
| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `lat` | float | — | Latitud del punto de búsqueda (requerido) |
| `lng` | float | — | Longitud del punto de búsqueda (requerido) |
| `radius` | float | 1 | Radio de búsqueda en kilómetros |
| `open_now` | boolean | false | Filtrar solo puestos abiertos |
| `sort` | string | "distance" | `distance` o `popularity` |
| `page` | integer | 1 | Número de página |
| `per_page` | integer | 10 | Resultados por página (5-50) |

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Anticuchos de la Tía Rosa",
            "address": "Av. Grau 456, Lima",
            "latitude": -12.0464,
            "longitude": -77.0428,
            "distance_km": 0.50,
            "orders_count": 150,
            "open_now": true,
            "active": true
        }
    ]
}
```

**Comportamiento:**
- Fórmula Haversine para cálculo de distancia en km
- Si `open_now` es true, filtra los puestos que están abiertos en este momento
- Si `sort` es `popularity`, ordena por cantidad de pedidos descendente
- Solo muestra puestos con `active = 1`

### 20.2 Listar Puestos (General)

**Endpoint:** `GET /api/puestos?lat=-12.0464&lng=-77.0428&radius=5&open_now=false&sort=distance&page=1&per_page=10`

**Autenticación:** No requiere

**Comportamiento:**
- Wrapper completo para UI que reutiliza nearby cuando hay coordenadas
- Si no hay coordenadas, retorna todos los puestos activos paginados
- Soporta todos los filtros de nearby

### 20.3 Marcadores para Mapa

**Endpoint:** `GET /api/puestos/map-markers?lat=-12.0464&lng=-77.0428&radius=1`

**Autenticación:** No requiere

**Response (200):**
```json
{
    "markers": [
        {
            "id": 1,
            "lat": -12.0464,
            "lng": -77.0428,
            "name": "Anticuchos de la Tía Rosa",
            "icon": "marker-open",
            "distance_km": 0.50
        }
    ]
}
```

**Comportamiento:**
- Retorna payload mínimo para renderizar marcadores en el mapa
- `icon`: `marker-open` si está abierto, `marker-default` si no, `marker-closed` si inactivo
- Reutiliza el endpoint nearby internamente

### 20.4 Estado del Puesto

**Endpoint:** `GET /api/puestos/{stallId}/status`

**Autenticación:** No requiere

**Response (200):**
```json
{
    "stall_id": 1,
    "open_now": true,
    "in_pause": false,
    "next_opening": null
}
```

**Comportamiento:**
- Si `open_now` es false, calcula `next_opening`:
  - Si la hora actual es antes de la apertura, muestra la apertura de hoy
  - Si ya pasó la hora de cierre, muestra la apertura de mañana

### 20.5 Datos del Puesto + Menú (para Mapa)

**Endpoint:** `GET /api/puestos/{stallId}/map`

**Autenticación:** No requiere

**Response (200):**
```json
{
    "stall": {
        "id": 1,
        "name": "Anticuchos de la Tía Rosa",
        "menuItems": [
            {
                "id": 1,
                "stall_id": 1,
                "name": "Anticucho de Corazón",
                "price": 12.50,
                "active": 1
            }
        ]
    }
}
```

**Comportamiento:** Retorna el puesto con sus items de menú activos.

### 20.6 Obtener URL de Google Maps Directions

**Endpoint:** `GET /api/puestos/{stallId}/directions?from_lat=-12.0600&from_lng=-77.0400&mode=driving`

**Autenticación:** No requiere

**Response (200):**
```json
{
    "directions_url": "https://www.google.com/maps/dir/?api=1&origin=-12.0600,-77.0400&destination=-12.0464,-77.0428&travelmode=driving"
}
```

**Comportamiento:** Genera URL de Google Maps Directions para abrir en navegador o app. `mode` puede ser `driving`, `walking`, `bicycling`.

### 20.7 Estimar Ruta y ETA (Sin API Externa)

**Endpoint:** `GET /api/puestos/{stallId}/route?from_lat=-12.0600&from_lng=-77.0400&mode=driving`

**Autenticación:** No requiere

**Response (200):**
```json
{
    "stall_id": 1,
    "distance_km": 2.35,
    "estimated_time_min": 5,
    "mode": "driving"
}
```

**Velocidades promedio usadas:**
- `driving`: 30 km/h
- `walking`: 5 km/h
- `bicycling`: 15 km/h

---

## MÓDULO 21: COMISIONES (RF18-RF19)

### 21.1 Calcular Comisión de un Pedido

**Endpoint:** `POST /api/comisiones/calcular`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Request:**
```json
{
    "order_id": 1
}
```

**Response (201):**
```json
{
    "exito": true,
    "mensaje": "Comisión calculada exitosamente",
    "datos": {
        "comision_id": 1,
        "pedido_id": 1,
        "monto_pedido": 38.99,
        "porcentaje_comision": "10.00%",
        "monto_comision": 3.90,
        "monto_neto_vendedor": 35.09,
        "regla_aplicada": "Comisión base: 10%",
        "estado": "pending",
        "calculada_en": "2026-03-03T12:01:00.000000Z"
    }
}
```

**Reglas de validación:** `order_id` requerido, debe existir en orders

**Comportamiento:**
- Verifica que el pedido esté en estado `confirmed`
- Aplica la regla de comisión más específica según prioridad:

**Prioridad de reglas:**
1. **price_range** — Si el total del pedido está dentro del rango definido
2. **category** — Si la categoría del primer producto coincide
3. **vendor_type** — Si el tipo del vendedor coincide
4. **base** — Regla por defecto (10%)

- El porcentaje debe estar entre 10% y 25%
- Registra en auditoría (`CommissionAuditLog`)
- Crea registro en `OrderCommission` con estado `pending`

### 21.2 Obtener Comisión de un Pedido

**Endpoint:** `GET /api/comisiones/pedido/{order_id}`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Response (200):**
```json
{
    "exito": true,
    "datos": {
        "comision_id": 1,
        "pedido_id": 1,
        "monto_pedido": 38.99,
        "porcentaje_comision": "10.00%",
        "monto_comision": 3.90,
        "monto_neto_vendedor": 35.09,
        "regla_aplicada": "Comisión base: 10%",
        "estado": "pending",
        "calculada_en": "2026-03-03T12:01:00.000000Z",
        "transferida_en": null
    }
}
```

### 21.3 Listar Reglas de Comisión (Admin)

**Endpoint:** `GET /api/comisiones/reglas?activas=true&tipo=base`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:admin`

**Response (200):**
```json
{
    "exito": true,
    "total": 2,
    "datos": [
        {
            "id": 1,
            "nombre": "Comisión Base",
            "tipo": "base",
            "porcentaje": "10.00%",
            "activa": "Sí",
            "vigencia_desde": null,
            "vigencia_hasta": null,
            "creada_por": "SYSTEM",
            "notas": "Comisión base del sistema",
            "creada_en": "2026-03-01T00:00:00.000000Z",
            "actualizada_en": "2026-03-01T00:00:00.000000Z"
        }
    ]
}
```

**Filtros disponibles:**
- `activas`: boolean — filtra por estado activo/inactivo
- `tipo`: string — filtra por tipo (`base`, `category`, `price_range`, `vendor_type`)

### 21.4 Crear Regla de Comisión (Admin)

**Endpoint:** `POST /api/comisiones/reglas`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:admin`

**Request:**
```json
{
    "nombre": "Comisión por categoría - Anticuchos",
    "tipo": "category",
    "porcentaje_comision": 15,
    "category_id": "1",
    "monto_minimo": null,
    "monto_maximo": null,
    "tipo_vendedor": null,
    "vigencia_desde": "2026-01-01",
    "vigencia_hasta": "2026-12-31",
    "activa": true,
    "notas": "Regla especial para categoría Anticuchos"
}
```

**Response (201):**
```json
{
    "exito": true,
    "mensaje": "Regla de comisión creada exitosamente",
    "datos": {
        "id": 2,
        "nombre": "Comisión por categoría - Anticuchos",
        "tipo": "category",
        "porcentaje": "15.00%"
    }
}
```

**Reglas de validación:**
- `nombre`: Requerido, string, max 255
- `tipo`: Requerido, valores: `base`, `category`, `price_range`, `vendor_type`
- `porcentaje_comision`: Requerido, numeric, min 10, max 25
- `category_id`: Opcional, string
- `monto_minimo`: Opcional, numeric, min 0
- `monto_maximo`: Opcional, numeric, min 0
- `tipo_vendedor`: Opcional, string
- `vigencia_desde`: Opcional, date
- `vigencia_hasta`: Opcional, date
- `activa`: Opcional, boolean
- `notas`: Opcional, string

### 21.5 Actualizar Regla de Comisión (Admin)

**Endpoint:** `PUT /api/comisiones/reglas/{rule_id}`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:admin`

**Request:**
```json
{
    "nombre": "Comisión premium",
    "porcentaje_comision": 18,
    "activa": true
}
```

**Response (200):**
```json
{
    "exito": true,
    "mensaje": "Regla actualizada exitosamente",
    "datos": {
        "id": 1,
        "nombre": "Comisión premium",
        "tipo": "base",
        "porcentaje": "18.00%",
        "actualizada_en": "2026-03-03T12:00:00.000000Z"
    }
}
```

### 21.6 Eliminar (Desactivar) Regla de Comisión (Admin)

**Endpoint:** `DELETE /api/comisiones/reglas/{rule_id}`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:admin`

**Response (200):**
```json
{
    "exito": true,
    "mensaje": "Regla desactivada exitosamente"
}
```

**Comportamiento:** No elimina físicamente, solo marca `is_active = false` (soft delete). Registra en auditoría.

### 21.7 Reporte de Comisiones (Admin)

**Endpoint:** `GET /api/comisiones/reporte?desde=2026-01-01&hasta=2026-03-03`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:admin`

**Response (200):**
```json
{
    "exito": true,
    "reporte": {
        "periodo": {
            "desde": "2026-01-01",
            "hasta": "2026-03-03"
        },
        "resumen": {
            "total_pedidos": 150,
            "monto_total_pedidos": "S/5,848.50",
            "comisiones_totales": "S/584.85",
            "monto_neto_vendedores": "S/5,263.65"
        },
        "por_estado": {
            "pending": 45,
            "completed": 100,
            "refunded": 5
        },
        "por_regla": {
            "1": {
                "cantidad": 150,
                "comision_total": "S/584.85",
                "porcentaje": "10.00%",
                "monto_neto": "S/5,263.65"
            }
        }
    }
}
```

**Reglas de validación:** `desde` y `hasta` son requeridos, formato date.

### 21.8 Auditoría de Comisiones (Admin)

**Endpoint:** `GET /api/comisiones/auditoria?desde=2026-01-01&hasta=2026-03-03&accion=calculated`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:admin`

**Filtros disponibles:**
- `desde`: date — filtrar desde fecha
- `hasta`: date — filtrar hasta fecha
- `accion`: string — filtrar por acción (`created`, `updated`, `deleted`, `calculated`, `transferred`, `refunded`)

**Response (200):**
```json
{
    "exito": true,
    "total": 50,
    "datos": [
        {
            "id": 1,
            "commission_rule_id": 1,
            "action": "calculated",
            "old_values": null,
            "new_values": { "order_id": 1, "commission_amount": 3.90 },
            "changed_by": "ORDER_PAYMENT",
            "ip_address": "127.0.0.1",
            "notes": "Comisión calculada para pedido #1",
            "created_at": "2026-03-03T12:01:00.000000Z"
        }
    ]
}
```

---

## MÓDULO 22: TRANSFERENCIAS (RF20)

### 22.1 Transferir Comisiones Pendientes (Admin)

**Endpoint:** `POST /api/comisiones/transferir`

**Autenticación:** `Bearer Token` (auth:sanctum) + `role:admin`

**Request:** (body opcional)
```json
{
    "vendor_id": 3,
    "method": "mock"
}
```

**Response (200):**
```json
{
    "exito": true,
    "mensaje": "Proceso de transferencia ejecutado (ver detalles).",
    "total_procesadas": 10,
    "detalles": [
        {
            "comision_id": 1,
            "pedido_id": 1,
            "monto_neto_vendedor": 35.09,
            "transferida_en": "2026-03-03T12:00:00.000000Z",
            "transaction_id": "MOCK-TRX-1234567890-1234"
        },
        {
            "comision_id": 2,
            "pedido_id": 5,
            "monto_neto_vendedor": 28.50,
            "error": "Error de conexión"
        }
    ]
}
```

**Comportamiento:**
- Busca comisiones en estado `pending`
- Si se envía `vendor_id`, filtra solo comisiones del vendedor específico
- Ejecuta `TransferService::transferToVendor()` para cada comisión
- Si la transferencia es exitosa: marca la comisión como `completed`, registra `CompanyAccountEntry` (débito)
- Si falla: registra el error en los detalles, notifica al admin por email si está configurado

---

## MÓDULO 23: COMPROBANTES ELECTRÓNICOS (RF21)

### 23.1 Generar Comprobante para Pedido

**Endpoint:** `POST /api/comprobantes/generar/{order_id}`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Response (200):**
```json
{
    "success": true,
    "invoice_id": 1
}
```

**Comportamiento:**
- Crea el registro `Invoice` en estado `pending` con tipo (boleta/factura) basado en los campos del pedido
- Calcula IGV (18%) sobre el subtotal
- Dispara `GenerateElectronicInvoiceJob` a la cola `invoices`
- El job ejecuta:
  1. `OseAdapterMock::sendInvoice()` — genera XML en formato UBL 2.1 compatible con SUNAT
  2. `InvoicePdfGenerator::generate()` — genera PDF profesional
  3. Guarda XML y PDF en storage
  4. Actualiza invoice con estado `generated`, `ose_ticket`, `ose_hash`
  5. Envía email al cliente con `InvoiceGenerated` si tiene email registrado
- Si falla: incrementa `attempts`, si supera el máximo (`invoicing.max_retries`) notifica al admin

### 23.2 Descargar Comprobante PDF

**Endpoint:** `GET /api/comprobantes/{invoice_id}/pdf`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Headers de respuesta:** `application/pdf`

**Response:** Descarga directa del archivo PDF.

### 23.3 Descargar Comprobante XML

**Endpoint:** `GET /api/comprobantes/{invoice_id}/xml`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Headers de respuesta:** `application/xml`

**Response:** Descarga directa del archivo XML.

### 23.4 Reintentar Generación de Comprobante (Admin)

**Endpoint:** `POST /api/comprobantes/{invoice_id}/reintentar`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Response (200):**
```json
{
    "success": true,
    "message": "Reenvío encolado"
}
```

**Response (400):**
```json
{
    "success": false,
    "message": "Comprobante ya generado"
}
```

### 23.5 Listar Comprobantes

**Endpoint:** `GET /api/comprobantes`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Response (200):**
```json
{
    "success": true,
    "invoices": [
        {
            "id": 1,
            "order_id": 1,
            "type": "boleta",
            "status": "generated",
            "total": 38.99
        }
    ]
}
```

**Comportamiento:** Si el usuario es `admin`, lista todos los comprobantes. Si es cliente, solo los suyos.

### 23.6 Ver Detalle de Comprobante

**Endpoint:** `GET /api/comprobantes/{invoice_id}`

**Autenticación:** `Bearer Token` (auth:sanctum)

**Response (200):**
```json
{
    "success": true,
    "invoice": {
        "id": 1,
        "order_id": 1,
        "seller_id": 1,
        "customer_name": "Juan Perez",
        "customer_document_type": "DNI",
        "customer_document_number": "43908196",
        "type": "boleta",
        "subtotal": 30.50,
        "igv": 5.49,
        "total": 38.99,
        "pdf_path": "invoices/1.pdf",
        "xml_path": "invoices/1.xml",
        "ose_ticket": "OSE-20260303120000-1234",
        "ose_hash": "abc123...",
        "status": "generated",
        "attempts": 1,
        "error_message": null,
        "created_at": "2026-03-03T12:01:00.000000Z"
    }
}
```

---

## RESUMEN DEL FLUJO COMPLETO DE USO

### Flujo Cliente
```
1. POST /api/register → Registrarse
2. POST /api/login → Obtener token
3. GET /api/puestos/nearby?lat=...&lng=... → Buscar puestos cercanos
4. GET /api/menu/1 → Ver menú del puesto
5. POST /api/pedidos → Crear pedido
6. POST /api/pedidos/1/pagar → Pagar (async)
7. GET /api/pedidos/1 → Ver timeline de estados
8. GET /api/pedidos?page=1 → Historial de pedidos
9. PATCH /api/pedidos/1/cancelar → Cancelar (si aplica)
10. GET /api/comprobantes → Ver comprobantes
```

### Flujo Vendedor
```
1. POST /api/register → Registrarse como client
2. POST /api/convertirse-vendedor → Crear puesto
3. POST /api/mis-categorias → Crear categorías
4. POST /api/mis-productos → Crear productos
5. POST /api/mis-cremas → Crear cremas
6. POST /api/mis-productos/1/cremas → Asociar cremas
7. POST /api/mis-puesto/generar-qr → Generar QR
8. PUT /api/mis-puesto/horarios → Configurar horarios
9. PUT /api/mis-puesto/tiempos-preparacion → Configurar tiempos
10. GET /api/mis-pedidos → Ver pedidos entrantes
11. PATCH /api/pedidos/1/cambiar-estado → Avanzar estados
```

### Flujo Admin
```
1. GET /api/comisiones/reglas → Ver reglas
2. POST /api/comisiones/reglas → Crear regla
3. POST /api/comisiones/transferir → Transferir comisiones
4. GET /api/comisiones/reporte → Reportes
5. GET /api/comisiones/auditoria → Auditoría
6. POST /api/comprobantes/1/reintentar → Reintentar comprobantes
```

---

*Documento generado el 19 de Junio de 2026*
*Análisis funcional completo del proyecto ALTOQUE*
*Basado en la revisión exhaustiva del código fuente, controllers, services, models, jobs, rutas API y colección Postman.*
