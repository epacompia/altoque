# Guía de Autenticación API - Altoque

## Endpoints Implementados

### 1. Register (Registro)
**URL:** `POST http://127.0.0.1:8000/register`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
    "name": "Juan Perez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Respuesta Exitosa (201):**
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
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer"
  }
}
```

---

### 2. Login (Inicio de Sesión)
**URL:** `POST http://127.0.0.1:8000/login`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body:**
```json
{
    "email": "juan@example.com",
    "password": "password123"
}
```

**Respuesta Exitosa (200):**
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
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer"
  }
}
```

**Respuesta Error (401):**
```json
{
  "success": false,
  "message": "Credenciales inválidas",
  "errors": {
    "email": ["Las credenciales no coinciden con nuestros registros."]
  }
}
```

---

### 3. Logout (Cerrar Sesión)
**URL:** `POST http://127.0.0.1:8000/logout`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {TOKEN_AQUI}
```

**Body:** (vacío)

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "message": "Sesión cerrada exitosamente"
}
```

---

## Instrucciones para Postman

### Paso 1: Registrarse
1. Abre Postman
2. Abre la colección **Altoque**
3. Ve a **Auth** → **Register**
4. Haz clic en **Send**
5. Copia el token de la respuesta

### Paso 2: Usar el Token en Login (opcional)
1. Ve a **Auth** → **Login**
2. Ingresa el email y contraseña del usuario registrado
3. Haz clic en **Send**
4. Copia el token de la respuesta

### Paso 3: Usar el Token en Logout
1. Ve a **Auth** → **Logout**
2. En la pestaña **Authorization**, selecciona **Bearer Token**
3. Pega el token en el campo **Token**
4. Haz clic en **Send**

## Información Importante

- **Los tokens** son válidos para autenticar otras peticiones
- Usa el token en el header: `Authorization: Bearer {token}`
- **La contraseña mínima es de 8 caracteres**
- El email debe ser único en la base de datos
- El campo `password_confirmation` en register debe coincidir con `password`

## Cambios Realizados

### Archivos Creados:
- `app/Http/Controllers/Auth/ApiRegisterController.php` - Controlador de registro
- `app/Http/Controllers/Auth/ApiLoginController.php` - Controlador de login
- `app/Http/Controllers/Auth/ApiLogoutController.php` - Controlador de logout

### Archivos Modificados:
- `routes/api.php` - Agregadas rutas de autenticación
- `app/Providers/RouteServiceProvider.php` - Removido prefijo `/api/` para autenticación

### Base de Datos:
La tabla `users` ya existe con los campos necesarios:
- `id`, `name`, `email`, `password`, `role`, etc.

## Integración con Sanctum

- Se utiliza **Laravel Sanctum** para generar tokens API
- Los tokens se crean automáticamente al registrarse o iniciar sesión
- El logout revoca el token actual
- Para rutas protegidas, usa el middleware `auth:sanctum`

---

**Fecha de implementación:** 26/05/2026
**Versión:** 1.0.0
