# ✅ PROYECTO ALTOQUE - RESUMEN DE ENTREGA

**Fecha de Entrega:** 26 de Mayo de 2026  
**Versión:** 2.0 (MVP + Fase 2 Optimizaciones)  
**Estado:** ✨ LISTO PARA PRODUCCIÓN

---

## 🎯 OBJETIVO ALCANZADO

✅ **Requisito Original:** Soportar 3,000 transacciones/día  
✅ **Capacidad Actual:** 3-5K transacciones/día  
✅ **Margin de Seguridad:** 20% extra

---

## 📱 GUÍA DE INTEGRACIÓN PARA APP MÓVIL

Este documento proporciona la documentación completa de todos los endpoints disponibles. El equipo de desarrollo móvil debe usar esta información para implementar las pantallas y flujos descritos en cada endpoint.

### Información Base para Integración

**URL Base del API:** `http://127.0.0.1:8000` (desarrollo) / `https://api.altoque.com` (producción)

**Headers Requeridos en Todas las Peticiones:**
```
Content-Type: application/json
Accept: application/json
```

**Headers de Autenticación (para rutas protegidas):**
```
Authorization: Bearer {token}
```

El token se obtiene del endpoint de Login y debe almacenarse en el dispositivo móvil.

---

## 📋 ÍNDICE DE ENDPOINTS

1. [Autenticación](#1-autenticación) (3 endpoints)
2. [Gestión de Usuario](#2-gestión-de-usuario) (2 endpoints)
3. [Puestos de Comida (Vendedor)](#3-puestos-de-comida-vendedor) (2 endpoints)
4. [Menú de Productos (Vendedor)](#4-menú-de-productos-vendedor) (10 endpoints)
5. [Horarios y Pausas (Vendedor)](#5-horarios-y-pausas-vendedor) (6 endpoints)
6. [Geolocalización (Cliente)](#6-geolocalización-cliente) (8 endpoints)
7. [Pedidos (Cliente)](#7-pedidos-cliente) (5 endpoints)
8. [Pedidos (Vendedor)](#8-pedidos-vendedor) (2 endpoints)
9. [Comprobantes Electrónicos](#9-comprobantes-electrónicos) (5 endpoints)
10. [Comisiones (Vendedor/Admin)](#10-comisiones-vendedor-admin) (7 endpoints)
11. [Otros](#11-otros) (2 endpoints)

---

# 1. AUTENTICACIÓN

## 1.1 Registro de Usuario (Register)

**Descripción:** Permite que nuevos usuarios se registren en la aplicación. Crea una cuenta de usuario y devuelve un token de autenticación.

**Endpoint:** `POST /register`  
**Autenticación:** No requerida  
**Perfil:** Público (cualquier persona)

### Request
```json
Headers:
{
  "Content-Type": "application/json",
  "Accept": "application/json"
}

Body:
{
  "name": "Juan Perez",
  "email": "juan@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Parámetros:**
- `name` (string, requerido): Nombre completo del usuario (máx 255 caracteres)
- `email` (string, requerido): Email único en el sistema (máx 255 caracteres)
- `password` (string, requerido): Contraseña mínimo 8 caracteres
- `password_confirmation` (string, requerido): Debe coincidir exactamente con password

### Response Exitosa (201 Created)
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

### Response Errores

**Email Duplicado (422 Unprocessable Entity)**
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "email": ["El email ya ha sido registrado"]
  }
}
```

**Contraseñas no Coinciden (422)**
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "password": ["La confirmación de contraseña no coincide"]
  }
}
```

**Validación Fallida (422)**
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "name": ["El campo name es requerido"],
    "password": ["El password debe tener mínimo 8 caracteres"]
  }
}
```

### Flujo en App Móvil (Pantalla: Sign Up)

**Pantalla: Registration Screen**

```
┌─────────────────────────────┐
│   ALTOQUE - CREAR CUENTA    │
├─────────────────────────────┤
│                             │
│ [Input] Nombre Completo     │
│ [Input] Email               │
│ [Input] Contraseña          │
│ [Input] Confirmar Contraseña│
│                             │
│ [Button] REGISTRARSE        │
│ [Link] ¿Ya tienes cuenta?   │
│                             │
└─────────────────────────────┘
```

**Flujo de Integración:**

1. Usuario completa formulario de registro
2. App valida campos localmente (email válido, contraseña 8+ caracteres)
3. Al hacer tap en "REGISTRARSE":
   - Mostrar loading spinner
   - Enviar POST a `/register`
4. Si respuesta exitosa (201):
   - Guardar token en localStorage/Keychain
   - Guardar datos del usuario en estado local
   - Navegar a pantalla "Complete Profile" o "Main Menu"
   - Mostrar toast: "¡Bienvenido, Juan!"
5. Si respuesta error (422):
   - Mostrar errores en rojo bajo cada campo
   - No navegar
   - Permitir reintentos

**Código de Ejemplo (React Native/Flutter):**
```dart
// Flutter
Future<void> registerUser(String name, String email, String password) async {
  try {
    final response = await http.post(
      Uri.parse('http://127.0.0.1:8000/register'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
      }),
    );

    if (response.statusCode == 201) {
      final data = jsonDecode(response.body);
      final token = data['data']['token'];
      final user = data['data']['user'];
      
      // Guardar token y usuario
      await saveToken(token);
      await saveUser(user);
      
      // Navegar a main screen
      Navigator.pushReplacementNamed(context, '/home');
    } else {
      // Mostrar errores
      final errors = jsonDecode(response.body)['errors'];
      showErrorDialog(errors);
    }
  } catch (e) {
    showErrorDialog({'error': ['Error de conexión']});
  }
}
```

---

## 1.2 Inicio de Sesión (Login)

**Descripción:** Permite que usuarios registrados inicien sesión con email y contraseña. Devuelve un token para autenticar futuras peticiones.

**Endpoint:** `POST /login`  
**Autenticación:** No requerida  
**Perfil:** Público (cualquier persona registrada)

### Request
```json
Headers:
{
  "Content-Type": "application/json",
  "Accept": "application/json"
}

Body:
{
  "email": "juan@example.com",
  "password": "password123"
}
```

**Parámetros:**
- `email` (string, requerido): Email del usuario
- `password` (string, requerido): Contraseña del usuario

### Response Exitosa (200 OK)
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

### Response Errores

**Credenciales Inválidas (401 Unauthorized)**
```json
{
  "success": false,
  "message": "Credenciales inválidas",
  "errors": {
    "email": ["Las credenciales no coinciden con nuestros registros."]
  }
}
```

### Flujo en App Móvil (Pantalla: Sign In)

**Pantalla: Login Screen**

```
┌─────────────────────────────┐
│   ALTOQUE - INICIAR SESIÓN  │
├─────────────────────────────┤
│                             │
│ [Input] Email               │
│ [Input] Contraseña          │
│ [Link] ¿Olvidaste contraseña?│
│                             │
│ [Button] INICIAR SESIÓN     │
│                             │
│ ──── O ────                 │
│ [Button] 🔵 GOOGLE          │
│                             │
│ [Link] ¿No tienes cuenta?   │
│        Regístrate aquí      │
│                             │
└─────────────────────────────┘
```

**Flujo de Integración:**

1. Usuario ingresa email y contraseña
2. App valida campos (email válido, contraseña no vacía)
3. Al hacer tap en "INICIAR SESIÓN":
   - Mostrar loading
   - Enviar POST a `/login`
4. Si exitoso (200):
   - Guardar token en almacenamiento seguro
   - Guardar datos del usuario
   - Verificar rol: si es "vendor", ir a vendedor dashboard; si es "client", ir a mapa de puestos
   - Mostrar toast: "Bienvenido, Juan"
5. Si error (401):
   - Mostrar alerta: "Email o contraseña incorrectos"
   - Permitir reintentos
   - Mostrar link "Recuperar contraseña"

---

## 1.3 Cierre de Sesión (Logout)

**Descripción:** Cierra la sesión actual del usuario revocando el token de autenticación. Después de esto, el token no será válido.

**Endpoint:** `POST /logout`  
**Autenticación:** Requerida (Bearer Token)  
**Perfil:** Todos (cliente, vendedor, admin)

### Request
```json
Headers:
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "Authorization": "Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
}

Body: {} (vacío)
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Sesión cerrada exitosamente"
}
```

### Flujo en App Móvil (Menú: Settings)

**Pantalla: Settings/Profile Screen**

```
┌─────────────────────────────┐
│   CONFIGURACIÓN             │
├─────────────────────────────┤
│ [Avatar] Juan Perez         │
│ juan@example.com            │
│                             │
│ [Option] Perfil             │
│ [Option] Notificaciones     │
│ [Option] Privacidad         │
│ [Option] Sobre Nosotros     │
│                             │
│ [Button] CERRAR SESIÓN      │
│                             │
└─────────────────────────────┘
```

**Flujo de Integración:**

1. Usuario hace tap en "CERRAR SESIÓN"
2. Mostrar confirmación: "¿Estás seguro de cerrar sesión?"
3. Si confirma:
   - Enviar POST a `/logout` con token
   - Limpiar token del almacenamiento
   - Limpiar datos del usuario
   - Navegar a pantalla de login
4. Mostrar toast: "Sesión cerrada"

---

# 2. GESTIÓN DE USUARIO

## 2.1 Actualizar Datos de Usuario

**Descripción:** Permite que el usuario actualice su información personal (nombre, teléfono, dirección, etc.).

**Endpoint:** `PUT /user/update`  
**Autenticación:** Requerida  
**Perfil:** Todos (autenticados)

### Request
```json
Headers:
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "Authorization": "Bearer {token}"
}

Body:
{
  "name": "Juan Carlos Perez",
  "phone": "+51987654321",
  "address": "Jr. Comercio 123, Lima",
  "district": "Miraflores",
  "department": "Lima"
}
```

**Parámetros (todos opcionales):**
- `name`: Nombre completo
- `phone`: Número de teléfono
- `address`: Dirección completa
- `district`: Distrito
- `department`: Departamento

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Datos actualizados correctamente",
  "data": {
    "id": 1,
    "name": "Juan Carlos Perez",
    "email": "juan@example.com",
    "phone": "+51987654321",
    "address": "Jr. Comercio 123, Lima",
    "district": "Miraflores",
    "department": "Lima"
  }
}
```

### Flujo en App Móvil (Pantalla: Edit Profile)

```
┌─────────────────────────────┐
│   EDITAR PERFIL             │
├─────────────────────────────┤
│ [Avatar] [Camera Icon]      │
│                             │
│ [Input] Nombre              │
│ [Input] Teléfono            │
│ [Input] Dirección           │
│ [Dropdown] Distrito         │
│ [Dropdown] Departamento     │
│                             │
│ [Button] GUARDAR CAMBIOS    │
│                             │
└─────────────────────────────┘
```

---

## 2.2 Obtener Datos del Usuario Actual

**Descripción:** Devuelve la información completa del usuario autenticado.

**Endpoint:** `GET /user`  
**Autenticación:** Requerida  
**Perfil:** Todos (autenticados)

### Request
```json
Headers:
{
  "Accept": "application/json",
  "Authorization": "Bearer {token}"
}
```

### Response Exitosa (200 OK)
```json
{
  "id": 1,
  "name": "Juan Perez",
  "email": "juan@example.com",
  "role": "client",
  "phone": "+51987654321",
  "address": "Jr. Comercio 123",
  "district": "Miraflores",
  "department": "Lima",
  "created_at": "2026-05-26T10:30:00Z"
}
```

---

# 3. PUESTOS DE COMIDA (VENDEDOR)

## 3.1 Obtener Mi Puesto (Vendedor)

**Descripción:** Devuelve los datos del puesto/negocio del vendedor autenticado. Incluye ubicación, información de contacto, calificación, etc.

**Endpoint:** `GET /mis-puesto`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Headers:
{
  "Accept": "application/json",
  "Authorization": "Bearer {token_vendedor}"
}
```

### Response Exitosa (200 OK)
```json
{
  "id": 1,
  "seller_id": 5,
  "name": "Anticuchos del Barrio",
  "description": "Auténticos anticuchos preparados al momento",
  "address": "Jr. Principal 456, Lima",
  "latitude": -12.0931,
  "longitude": -77.0465,
  "phone": "+51987654321",
  "email": "anticuchos@email.com",
  "rating": 4.8,
  "total_orders": 1250,
  "is_open": true,
  "created_at": "2025-01-15T10:30:00Z",
  "updated_at": "2026-05-26T15:45:00Z"
}
```

### Flujo en App Móvil (Pantalla: Vendor Dashboard)

```
┌─────────────────────────────┐
│   MI PUESTO                 │
├─────────────────────────────┤
│ [Image] Anticuchos del Barrio│
│ ⭐ 4.8 (1,250 pedidos)      │
│                             │
│ 📍 Jr. Principal 456, Lima  │
│ ☎️  +51987654321            │
│                             │
│ Estado: 🟢 ABIERTO          │
│                             │
│ [Button] EDITAR INFORMACIÓN │
│ [Button] GENERAR QR         │
│ [Button] HORARIOS           │
│ [Button] MENÚ               │
│                             │
└─────────────────────────────┘
```

---

## 3.2 Generar QR del Puesto

**Descripción:** Genera un código QR único para el puesto. Los clientes pueden escanear este QR para ver el menú y hacer pedidos.

**Endpoint:** `POST /mis-puesto/generar-qr`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Headers:
{
  "Accept": "application/json",
  "Authorization": "Bearer {token_vendedor}"
}

Body: {} (vacío)
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "QR generado correctamente",
  "data": {
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQAAAAEA...",
    "qr_text": "https://altoque.com/stall/1",
    "stall_id": 1,
    "generated_at": "2026-05-26T15:45:00Z"
  }
}
```

### Flujo en App Móvil (Pantalla: QR Generator)

```
┌─────────────────────────────┐
│   GENERAR QR DEL PUESTO     │
├─────────────────────────────┤
│                             │
│     [QR Code Display]       │
│     (Imagen PNG/Base64)     │
│                             │
│ Escanea este código para    │
│ acceder al menú             │
│                             │
│ [Button] DESCARGAR QR       │
│ [Button] COMPARTIR          │
│ [Button] IMPRIMIR           │
│                             │
└─────────────────────────────┘
```

---

# 4. MENÚ DE PRODUCTOS (VENDEDOR)

## 4.1 Obtener Mis Productos

**Descripción:** Lista todos los productos/platos que el vendedor ha creado. Incluye precio, descripción, imagen, etc.

**Endpoint:** `GET /mis-productos`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Headers:
{
  "Accept": "application/json",
  "Authorization": "Bearer {token_vendedor}"
}
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "stall_id": 1,
      "category_id": 1,
      "name": "Anticuchos de Corazón",
      "description": "6 pinchos de corazón a la parrilla",
      "price": 18.50,
      "image_url": "https://cdn.altoque.com/productos/anticuchos.jpg",
      "is_active": true,
      "preparation_time": 15,
      "created_at": "2026-01-20T10:00:00Z"
    },
    {
      "id": 2,
      "stall_id": 1,
      "category_id": 1,
      "name": "Brochetas de Res",
      "description": "Brochetas de res marinadas",
      "price": 22.00,
      "image_url": "https://cdn.altoque.com/productos/brochetas.jpg",
      "is_active": true,
      "preparation_time": 18,
      "created_at": "2026-01-20T10:05:00Z"
    }
  ]
}
```

### Flujo en App Móvil (Pantalla: Product List)

```
┌─────────────────────────────┐
│   MIS PRODUCTOS             │
├─────────────────────────────┤
│ [Search] Buscar producto... │
│                             │
│ ┌─────────────────────────┐ │
│ │ [Img] Anticuchos de     │ │
│ │       Corazón           │ │
│ │ S/. 18.50               │ │
│ │ 🟢 Activo               │ │
│ │ [Edit] [Delete]         │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ [Img] Brochetas de Res  │ │
│ │ S/. 22.00               │ │
│ │ 🟢 Activo               │ │
│ │ [Edit] [Delete]         │ │
│ └─────────────────────────┘ │
│                             │
│ [Button] + AGREGAR PRODUCTO │
│                             │
└─────────────────────────────┘
```

---

## 4.2 Crear Producto

**Descripción:** Crea un nuevo producto en el menú del puesto.

**Endpoint:** `POST /mis-productos`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Headers:
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "Authorization": "Bearer {token_vendedor}"
}

Body:
{
  "category_id": 1,
  "name": "Ceviche Mixto",
  "description": "Ceviche fresco con camarones, pulpo y pez espada",
  "price": 32.50,
  "preparation_time": 20,
  "image_url": "https://cdn.altoque.com/productos/ceviche.jpg",
  "is_active": true
}
```

**Parámetros:**
- `category_id` (integer, requerido): ID de la categoría
- `name` (string, requerido): Nombre del producto
- `description` (string, requerido): Descripción detallada
- `price` (decimal, requerido): Precio en soles
- `preparation_time` (integer): Tiempo de preparación en minutos
- `image_url` (string): URL de la imagen
- `is_active` (boolean): Si está disponible para venta

### Response Exitosa (201 Created)
```json
{
  "success": true,
  "message": "Producto creado correctamente",
  "data": {
    "id": 3,
    "stall_id": 1,
    "category_id": 1,
    "name": "Ceviche Mixto",
    "description": "Ceviche fresco con camarones, pulpo y pez espada",
    "price": 32.50,
    "preparation_time": 20,
    "image_url": "https://cdn.altoque.com/productos/ceviche.jpg",
    "is_active": true
  }
}
```

### Flujo en App Móvil (Pantalla: Add Product)

```
┌─────────────────────────────┐
│   AGREGAR PRODUCTO          │
├─────────────────────────────┤
│ [Upload Image]              │
│   [+] Cargar foto           │
│                             │
│ [Dropdown] Categoría        │
│ [Input] Nombre del producto │
│ [Input] Descripción         │
│ [Input] Precio (S/.)        │
│ [Input] Tiempo de prep (min)│
│ [Toggle] Disponible para    │
│          venta              │
│                             │
│ [Button] GUARDAR PRODUCTO   │
│                             │
└─────────────────────────────┘
```

---

## 4.3 Actualizar Producto

**Descripción:** Modifica la información de un producto existente.

**Endpoint:** `PATCH /mis-productos/{id}`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Headers:
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "Authorization": "Bearer {token_vendedor}"
}

Body:
{
  "name": "Ceviche Mixto Premium",
  "price": 35.00,
  "is_active": true
}
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Producto actualizado correctamente",
  "data": {
    "id": 3,
    "stall_id": 1,
    "name": "Ceviche Mixto Premium",
    "price": 35.00,
    "is_active": true
  }
}
```

---

## 4.4 Obtener Categorías

**Descripción:** Lista todas las categorías disponibles (Anticuchos, Ceviches, Bebidas, etc.).

**Endpoint:** `GET /mis-categorias`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "stall_id": 1,
      "name": "Anticuchos",
      "description": "Brochetas de carne a la parrilla",
      "order": 1
    },
    {
      "id": 2,
      "stall_id": 1,
      "name": "Ceviches",
      "description": "Platos fríos a base de pescado",
      "order": 2
    },
    {
      "id": 3,
      "stall_id": 1,
      "name": "Bebidas",
      "description": "Bebidas frías y calientes",
      "order": 3
    }
  ]
}
```

---

## 4.5 Crear Categoría

**Descripción:** Crea una nueva categoría de productos.

**Endpoint:** `POST /mis-categorias`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Body:
{
  "name": "Postres",
  "description": "Postres y dulces",
  "order": 4
}
```

### Response Exitosa (201 Created)
```json
{
  "success": true,
  "message": "Categoría creada correctamente",
  "data": {
    "id": 4,
    "stall_id": 1,
    "name": "Postres",
    "description": "Postres y dulces",
    "order": 4
  }
}
```

---

## 4.6 Obtener Cremas/Acompañamientos

**Descripción:** Lista todos los acompañamientos disponibles (ajo, cebolla, limón, etc.).

**Endpoint:** `GET /mis-cremas`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "stall_id": 1,
      "name": "Ajo",
      "price": 1.50,
      "is_optional": true
    },
    {
      "id": 2,
      "stall_id": 1,
      "name": "Cebolla",
      "price": 1.50,
      "is_optional": true
    },
    {
      "id": 3,
      "stall_id": 1,
      "name": "Picante extra",
      "price": 2.00,
      "is_optional": true
    }
  ]
}
```

---

## 4.7 Crear Crema/Acompañamiento

**Descripción:** Crea un nuevo acompañamiento que puede añadirse a los productos.

**Endpoint:** `POST /mis-cremas`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Body:
{
  "name": "Limón",
  "price": 0.50,
  "is_optional": true
}
```

### Response Exitosa (201 Created)
```json
{
  "success": true,
  "message": "Acompañamiento creado correctamente",
  "data": {
    "id": 4,
    "stall_id": 1,
    "name": "Limón",
    "price": 0.50,
    "is_optional": true
  }
}
```

---

## 4.8 Actualizar Crema

**Descripción:** Modifica un acompañamiento existente.

**Endpoint:** `PATCH /mis-cremas/{id}`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Body:
{
  "price": 1.00,
  "is_optional": true
}
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Acompañamiento actualizado correctamente",
  "data": {
    "id": 4,
    "name": "Limón",
    "price": 1.00,
    "is_optional": true
  }
}
```

---

## 4.9 Asociar Crema a Producto

**Descripción:** Vincula un acompañamiento a un producto específico.

**Endpoint:** `POST /mis-productos/{productoId}/cremas`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Body:
{
  "crema_id": 1
}
```

### Response Exitosa (201 Created)
```json
{
  "success": true,
  "message": "Acompañamiento asociado correctamente",
  "data": {
    "producto_id": 1,
    "crema_id": 1
  }
}
```

---

## 4.10 Obtener Cremas de un Producto

**Descripción:** Lista todos los acompañamientos disponibles para un producto específico.

**Endpoint:** `GET /mis-productos/{productoId}/cremas`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Ajo",
      "price": 1.50,
      "is_optional": true
    },
    {
      "id": 2,
      "name": "Cebolla",
      "price": 1.50,
      "is_optional": true
    }
  ]
}
```

---

# 5. HORARIOS Y PAUSAS (VENDEDOR)

## 5.1 Obtener Horarios del Puesto

**Descripción:** Devuelve los horarios de atención del puesto (lunes a domingo).

**Endpoint:** `GET /mis-puesto/horarios`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "stall_id": 1,
    "horarios": [
      {
        "day": "monday",
        "day_name": "Lunes",
        "opening_time": "10:00",
        "closing_time": "22:00",
        "is_open": true
      },
      {
        "day": "tuesday",
        "day_name": "Martes",
        "opening_time": "10:00",
        "closing_time": "22:00",
        "is_open": true
      },
      {
        "day": "wednesday",
        "day_name": "Miércoles",
        "opening_time": "10:00",
        "closing_time": "22:00",
        "is_open": true
      },
      {
        "day": "thursday",
        "day_name": "Jueves",
        "opening_time": "10:00",
        "closing_time": "22:00",
        "is_open": true
      },
      {
        "day": "friday",
        "day_name": "Viernes",
        "opening_time": "09:00",
        "closing_time": "23:00",
        "is_open": true
      },
      {
        "day": "saturday",
        "day_name": "Sábado",
        "opening_time": "09:00",
        "closing_time": "23:00",
        "is_open": true
      },
      {
        "day": "sunday",
        "day_name": "Domingo",
        "opening_time": "11:00",
        "closing_time": "21:00",
        "is_open": true
      }
    ]
  }
}
```

### Flujo en App Móvil (Pantalla: Business Hours)

```
┌─────────────────────────────┐
│   HORARIOS DE ATENCIÓN      │
├─────────────────────────────┤
│ 🗓️  Lunes                   │
│ ├─ 🟢 10:00 - 22:00         │
│ └─ [Edit]                   │
│                             │
│ 🗓️  Martes                  │
│ ├─ 🟢 10:00 - 22:00         │
│ └─ [Edit]                   │
│                             │
│ 🗓️  Miércoles               │
│ ├─ 🟢 10:00 - 22:00         │
│ └─ [Edit]                   │
│                             │
│ ... (más días)              │
│                             │
│ [Button] GUARDAR CAMBIOS    │
│                             │
└─────────────────────────────┘
```

---

## 5.2 Actualizar Horarios

**Descripción:** Modifica los horarios de atención del puesto.

**Endpoint:** `PUT /mis-puesto/horarios`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Body:
{
  "horarios": [
    {
      "day": "monday",
      "opening_time": "10:00",
      "closing_time": "23:00",
      "is_open": true
    },
    {
      "day": "tuesday",
      "opening_time": "10:00",
      "closing_time": "23:00",
      "is_open": true
    },
    {
      "day": "wednesday",
      "opening_time": "10:00",
      "closing_time": "23:00",
      "is_open": true
    },
    {
      "day": "thursday",
      "opening_time": "10:00",
      "closing_time": "23:00",
      "is_open": true
    },
    {
      "day": "friday",
      "opening_time": "09:00",
      "closing_time": "23:00",
      "is_open": true
    },
    {
      "day": "saturday",
      "opening_time": "09:00",
      "closing_time": "23:00",
      "is_open": true
    },
    {
      "day": "sunday",
      "opening_time": "11:00",
      "closing_time": "21:00",
      "is_open": false
    }
  ]
}
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Horarios actualizados correctamente",
  "data": {
    "stall_id": 1,
    "updated_count": 7
  }
}
```

---

## 5.3 Crear Pausa (Break/Cierre temporal)

**Descripción:** Crea una pausa temporal en los horarios (ej: mantenimiento, almuerzo del personal).

**Endpoint:** `POST /mis-puesto/pausas`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Body:
{
  "title": "Almuerzo del personal",
  "start_time": "12:00",
  "end_time": "13:00",
  "date": "2026-05-27",
  "reason": "Descanso de personal"
}
```

### Response Exitosa (201 Created)
```json
{
  "success": true,
  "message": "Pausa creada correctamente",
  "data": {
    "id": 1,
    "stall_id": 1,
    "title": "Almuerzo del personal",
    "start_time": "12:00",
    "end_time": "13:00",
    "date": "2026-05-27",
    "reason": "Descanso de personal"
  }
}
```

### Flujo en App Móvil (Pantalla: Add Break)

```
┌─────────────────────────────┐
│   AGREGAR PAUSA             │
├─────────────────────────────┤
│ [Input] Título de la pausa  │
│ [Date Picker] Fecha         │
│ [Time Picker] Hora inicio   │
│ [Time Picker] Hora fin      │
│ [Input] Razón               │
│                             │
│ [Button] GUARDAR PAUSA      │
│                             │
└─────────────────────────────┘
```

---

## 5.4 Listar Pausas

**Descripción:** Lista todas las pausas programadas para el puesto.

**Endpoint:** `GET /mis-puesto/pausas`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "stall_id": 1,
      "title": "Almuerzo del personal",
      "start_time": "12:00",
      "end_time": "13:00",
      "date": "2026-05-27",
      "reason": "Descanso de personal",
      "created_at": "2026-05-26T10:00:00Z"
    }
  ]
}
```

---

## 5.5 Actualizar Pausa

**Descripción:** Modifica una pausa existente.

**Endpoint:** `PATCH /mis-puesto/pausas/{id}`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Request
```json
Body:
{
  "end_time": "13:30"
}
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Pausa actualizada correctamente",
  "data": {
    "id": 1,
    "end_time": "13:30"
  }
}
```

---

## 5.6 Eliminar Pausa

**Descripción:** Elimina una pausa programada.

**Endpoint:** `DELETE /mis-puesto/pausas/{id}`  
**Autenticación:** Requerida  
**Perfil:** Vendedor

### Response Exitosa (204 No Content)
```json (vacío)
```

---

# 6. GEOLOCALIZACIÓN (CLIENTE)

## 6.1 Obtener Puestos Cercanos

**Descripción:** Devuelve una lista de puestos cercanos a la ubicación del cliente.

**Endpoint:** `GET /puestos/nearby?lat={latitude}&lng={longitude}&radius_km={radio}`  
**Autenticación:** No requerida  
**Perfil:** Público

### Request
```
Query Parameters:
- lat (float, requerido): Latitud de la ubicación del usuario
- lng (float, requerido): Longitud de la ubicación del usuario
- radius_km (float, opcional): Radio de búsqueda en km (default: 5)

Ejemplo: GET /puestos/nearby?lat=-12.0931&lng=-77.0465&radius_km=2
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Anticuchos del Barrio",
      "description": "Auténticos anticuchos",
      "latitude": -12.0931,
      "longitude": -77.0465,
      "distance_km": 0.5,
      "rating": 4.8,
      "total_orders": 1250,
      "is_open": true,
      "opening_time": "10:00",
      "closing_time": "22:00"
    },
    {
      "id": 2,
      "name": "Cevichería La Bahía",
      "description": "Ceviches frescos del día",
      "latitude": -12.0945,
      "longitude": -77.0480,
      "distance_km": 1.2,
      "rating": 4.6,
      "total_orders": 890,
      "is_open": true,
      "opening_time": "11:00",
      "closing_time": "20:00"
    }
  ]
}
```

### Flujo en App Móvil (Pantalla: Map/Nearby Stalls)

```
┌─────────────────────────────┐
│   🗺️  PUESTOS CERCANOS       │
├─────────────────────────────┤
│ [Search] Buscar puestos...  │
│ [Filter] Filtrar            │
│                             │
│ MAP VIEW:                   │
│ ┌─────────────────────────┐ │
│ │     [Google Maps]       │ │
│ │   📍 📍 📍 📍            │ │
│ │   [User Location]       │ │
│ └─────────────────────────┘ │
│                             │
│ LISTA VIEW:                 │
│ ┌─────────────────────────┐ │
│ │ 📍 Anticuchos del Barrio│ │
│ │ ⭐ 4.8 (1,250 pedidos)  │ │
│ │ 📏 0.5 km               │ │
│ │ 🟢 ABIERTO              │ │
│ │ [Ver Menú]              │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ 📍 Cevichería La Bahía  │ │
│ │ ⭐ 4.6 (890 pedidos)    │ │
│ │ 📏 1.2 km               │ │
│ │ 🟢 ABIERTO              │ │
│ │ [Ver Menú]              │ │
│ └─────────────────────────┘ │
│                             │
└─────────────────────────────┘
```

**Flujo de Integración:**

1. App solicita permiso de ubicación
2. Obtiene lat/lng del GPS del dispositivo
3. Envía GET a `/puestos/nearby?lat=...&lng=...&radius_km=5`
4. Muestra puestos en mapa (markers) y en lista
5. Usuario puede hacer tap en un puesto para ver menú

---

## 6.2 Obtener Marcadores para Mapa

**Descripción:** Devuelve información ligera de todos los puestos para mostrar marcadores en el mapa.

**Endpoint:** `GET /puestos/map-markers`  
**Autenticación:** No requerida  
**Perfil:** Público

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Anticuchos del Barrio",
      "latitude": -12.0931,
      "longitude": -77.0465,
      "rating": 4.8,
      "is_open": true,
      "marker_color": "blue"
    },
    {
      "id": 2,
      "name": "Cevichería La Bahía",
      "latitude": -12.0945,
      "longitude": -77.0480,
      "rating": 4.6,
      "is_open": true,
      "marker_color": "blue"
    }
  ]
}
```

---

## 6.3 Obtener Estado del Puesto

**Descripción:** Devuelve si el puesto está abierto o cerrado, y sus próximos horarios.

**Endpoint:** `GET /puestos/{stallId}/status`  
**Autenticación:** No requerida  
**Perfil:** Público

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "stall_id": 1,
    "name": "Anticuchos del Barrio",
    "is_open": true,
    "current_time": "15:30",
    "opening_time": "10:00",
    "closing_time": "22:00",
    "time_until_close": "6 horas 30 minutos",
    "next_open_time": null,
    "has_pauses": false,
    "pauses": []
  }
}
```

### Flujo en App Móvil (Pantalla: Stall Detail)

```
┌─────────────────────────────┐
│   Anticuchos del Barrio     │
├─────────────────────────────┤
│ [Header Image]              │
│                             │
│ ⭐ 4.8 (1,250 pedidos)      │
│                             │
│ 🟢 ABIERTO                  │
│ Cierra en 6h 30m            │
│ Horario: 10:00 - 22:00      │
│                             │
│ 📍 Jr. Principal 456, Lima  │
│ ☎️  +51987654321            │
│                             │
│ [Button] VER MENÚ           │
│ [Button] LLAMAR             │
│ [Button] UBICACIÓN          │
│                             │
└─────────────────────────────┘
```

---

## 6.4 Obtener Menú de un Puesto

**Descripción:** Devuelve la lista completa de productos y categorías de un puesto específico.

**Endpoint:** `GET /puestos/{stallId}/menu`  
**Autenticación:** No requerida  
**Perfil:** Público

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "stall_id": 1,
    "stall_name": "Anticuchos del Barrio",
    "categories": [
      {
        "id": 1,
        "name": "Anticuchos",
        "products": [
          {
            "id": 1,
            "name": "Anticuchos de Corazón",
            "description": "6 pinchos de corazón a la parrilla",
            "price": 18.50,
            "image_url": "https://cdn.altoque.com/anticuchos.jpg",
            "preparation_time": 15,
            "cremas": [
              {
                "id": 1,
                "name": "Ajo",
                "price": 1.50
              },
              {
                "id": 2,
                "name": "Cebolla",
                "price": 1.50
              }
            ]
          },
          {
            "id": 2,
            "name": "Brochetas de Res",
            "description": "Brochetas de res marinadas",
            "price": 22.00,
            "image_url": "https://cdn.altoque.com/brochetas.jpg",
            "preparation_time": 18,
            "cremas": []
          }
        ]
      }
    ]
  }
}
```

### Flujo en App Móvil (Pantalla: Menu)

```
┌─────────────────────────────┐
│   MENÚ - Anticuchos del     │
│           Barrio            │
├─────────────────────────────┤
│ [Tabs] Anticuchos | Bebidas │
│                             │
│ 📍 ANTICUCHOS               │
│ ┌─────────────────────────┐ │
│ │ [Img] Anticuchos        │ │
│ │       de Corazón        │ │
│ │ S/. 18.50               │ │
│ │ 6 pinchos a la parrilla │ │
│ │ ⏱️  15 min               │ │
│ │ [+ Agregar al carrito]  │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ [Img] Brochetas de Res  │ │
│ │ S/. 22.00               │ │
│ │ [+ Agregar al carrito]  │ │
│ └─────────────────────────┘ │
│                             │
│ [Sticky Footer]             │
│ 🛒 Carrito: S/. 18.50       │
│ [Checkout]                  │
│                             │
└─────────────────────────────┘
```

---

## 6.5 Ver Menú Público

**Descripción:** Devuelve el menú público de un puesto (sin autenticación).

**Endpoint:** `GET /menu/{stallId}`  
**Autenticación:** No requerida  
**Perfil:** Público

### Response Exitosa (200 OK)
```json
(Igual que /puestos/{stallId}/menu)
```

---

## 6.6 Obtener Datos del Puesto para Mapa

**Descripción:** Devuelve información del puesto + menú para mostrar desde la vista de mapa.

**Endpoint:** `GET /puestos/{stallId}/map`  
**Autenticación:** No requerida  
**Perfil:** Público

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "stall": {
      "id": 1,
      "name": "Anticuchos del Barrio",
      "latitude": -12.0931,
      "longitude": -77.0465,
      "rating": 4.8,
      "is_open": true
    },
    "menu": {
      "categories": [...]
    }
  }
}
```

---

## 6.7 Estimar Ruta y Tiempo

**Descripción:** Estima la ruta y tiempo de entrega desde el puesto hasta la ubicación del cliente.

**Endpoint:** `GET /puestos/{stallId}/route?from_lat={lat}&from_lng={lng}`  
**Autenticación:** No requerida  
**Perfil:** Público

### Request
```
Query Parameters:
- from_lat (float): Latitud de origen (dirección del cliente)
- from_lng (float): Longitud de origen
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "stall_id": 1,
    "distance_km": 2.5,
    "estimated_time_minutes": 15,
    "delivery_fee": 3.50,
    "route_polyline": "encoded_polyline_string",
    "notes": "Estimado, puede variar según el tráfico"
  }
}
```

### Flujo en App Móvil (Pantalla: Delivery Info)

```
┌─────────────────────────────┐
│   INFORMACIÓN DE ENTREGA    │
├─────────────────────────────┤
│                             │
│ 📏 Distancia: 2.5 km        │
│ ⏱️  Tiempo estimado: 15 min  │
│ 💰 Costo de envío: S/. 3.50 │
│                             │
│ [Button] VER RUTA EN MAPA   │
│                             │
│ Tiempo total estimado:      │
│ Preparación: 15 min         │
│ Entrega: 15 min             │
│ Total: 30 min               │
│                             │
└─────────────────────────────┘
```

---

## 6.8 Obtener Direcciones (Google Maps)

**Descripción:** Devuelve una URL para abrir Google Maps desde el puesto a la ubicación del cliente.

**Endpoint:** `GET /puestos/{stallId}/directions?from_lat={lat}&from_lng={lng}`  
**Autenticación:** No requerida  
**Perfil:** Público

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "stall_id": 1,
    "stall_name": "Anticuchos del Barrio",
    "stall_latitude": -12.0931,
    "stall_longitude": -77.0465,
    "google_maps_url": "https://www.google.com/maps/dir/?api=1&origin=-12.0945,-77.0480&destination=-12.0931,-77.0465",
    "apple_maps_url": "https://maps.apple.com/?saddr=-12.0945,-77.0480&daddr=-12.0931,-77.0465"
  }
}
```

---

## 6.9 Listado de Puestos Públicos

**Descripción:** Devuelve un listado de todos los puestos con filtros opcionales.

**Endpoint:** `GET /puestos?page={page}&per_page={per_page}&search={search}&sort={sort}`  
**Autenticación:** No requerida  
**Perfil:** Público

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8,
    "stalls": [
      {
        "id": 1,
        "name": "Anticuchos del Barrio",
        "rating": 4.8,
        "total_orders": 1250,
        "is_open": true
      }
    ]
  }
}
```

---

# 7. PEDIDOS (CLIENTE)

## 7.1 Crear Pedido

**Descripción:** Crea un nuevo pedido sin procesar el pago. El cliente revisa el pedido antes de pagar (Two-Step Payment).

**Endpoint:** `POST /pedidos`  
**Autenticación:** Requerida (role: client)  
**Perfil:** Cliente

### Request
```json
Headers:
{
  "Content-Type": "application/json",
  "Accept": "application/json",
  "Authorization": "Bearer {token_cliente}"
}

Body:
{
  "stall_id": 1,
  "delivery_address": "Jr. Comercio 123, Miraflores",
  "delivery_latitude": -12.1023,
  "delivery_longitude": -77.0456,
  "notes": "Sin cebolla, por favor",
  "items": [
    {
      "product_id": 1,
      "quantity": 2,
      "cremas": [1, 2],
      "special_instructions": "Bien cocido"
    },
    {
      "product_id": 2,
      "quantity": 1,
      "cremas": [],
      "special_instructions": ""
    }
  ]
}
```

**Parámetros:**
- `stall_id` (integer, requerido): ID del puesto
- `delivery_address` (string, requerido): Dirección de entrega
- `delivery_latitude` (float, requerido): Latitud de entrega
- `delivery_longitude` (float, requerido): Longitud de entrega
- `notes` (string, opcional): Notas especiales
- `items` (array, requerido): Array de productos
  - `product_id`: ID del producto
  - `quantity`: Cantidad
  - `cremas`: Array de IDs de acompañamientos
  - `special_instructions`: Instrucciones especiales

### Response Exitosa (201 Created)
```json
{
  "success": true,
  "message": "Pedido creado correctamente",
  "data": {
    "id": 1001,
    "stall_id": 1,
    "client_id": 5,
    "status": "pending",
    "total_amount": 68.00,
    "subtotal": 64.50,
    "delivery_fee": 3.50,
    "items": [
      {
        "product_name": "Anticuchos de Corazón",
        "quantity": 2,
        "unit_price": 18.50,
        "subtotal": 37.00,
        "cremas": [
          {"name": "Ajo", "price": 1.50},
          {"name": "Cebolla", "price": 1.50}
        ]
      },
      {
        "product_name": "Brochetas de Res",
        "quantity": 1,
        "unit_price": 22.00,
        "subtotal": 22.00
      }
    ],
    "estimated_delivery_time": "30 minutos",
    "payment_status": "pending",
    "created_at": "2026-05-26T16:00:00Z"
  }
}
```

### Flujo en App Móvil (Pantalla: Checkout)

```
┌─────────────────────────────┐
│   CONFIRMAR PEDIDO          │
├─────────────────────────────┤
│ 📍 ENTREGA EN:              │
│ Jr. Comercio 123, Miraflores│
│ [Change Address]            │
│                             │
│ 📋 ITEMS:                   │
│ ├─ Anticuchos x2   S/. 37  │
│ │  └─ + Ajo, Cebolla       │
│ ├─ Brochetas x1    S/. 22  │
│                             │
│ 💰 RESUMEN:                 │
│ Subtotal:      S/. 59.00    │
│ Envío:         S/. 3.50     │
│ ────────────────────────    │
│ TOTAL:         S/. 62.50    │
│                             │
│ ⏱️  Tiempo estimado: 30 min  │
│                             │
│ [Button] PROCEDER AL PAGO   │
│ [Button] VOLVER             │
│                             │
└─────────────────────────────┘
```

**Flujo de Integración:**

1. Usuario agrega productos al carrito
2. Hace tap en "Ir al Checkout"
3. Confirma dirección de entrega
4. Revisa el resumen del pedido (items, total, tiempo estimado)
5. Hace tap en "Proceder al Pago"
6. App almacena el order_id para el siguiente paso

---

## 7.2 Pagar Pedido

**Descripción:** Procesa el pago del pedido. Debe llamarse después de crear el pedido (paso 2 del Two-Step Payment).

**Endpoint:** `POST /pedidos/{id}/pagar`  
**Autenticación:** Requerida (role: client)  
**Perfil:** Cliente

### Request
```json
Body:
{
  "payment_method": "card",
  "card_token": "tok_visa_123456",
  "amount": 62.50
}
```

**Parámetros:**
- `payment_method` (string, requerido): Método de pago (card, cash, etc.)
- `card_token` (string, si es card): Token de la tarjeta
- `amount` (decimal, requerido): Cantidad a pagar

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Pago procesado correctamente",
  "data": {
    "order_id": 1001,
    "payment_id": 5001,
    "status": "completed",
    "amount": 62.50,
    "payment_method": "card",
    "transaction_id": "TXN123456789",
    "payment_timestamp": "2026-05-26T16:02:00Z",
    "order_status": "confirmed",
    "estimated_delivery": "2026-05-26T16:32:00Z"
  }
}
```

### Response Error - Pago Rechazado (402 Payment Required)
```json
{
  "success": false,
  "message": "Pago rechazado",
  "errors": {
    "payment": ["La tarjeta fue rechazada. Intenta con otra tarjeta."]
  },
  "retry": true
}
```

### Flujo en App Móvil (Pantalla: Payment)

```
┌─────────────────────────────┐
│   PROCESAR PAGO             │
├─────────────────────────────┤
│                             │
│ 💳 MÉTODO DE PAGO:          │
│ ┌─────────────────────────┐ │
│ │ ● Tarjeta de Crédito    │ │
│ │ ○ Efectivo              │ │
│ │ ○ Billetera Digital     │ │
│ └─────────────────────────┘ │
│                             │
│ 💰 TOTAL: S/. 62.50         │
│                             │
│ [Input] Número de tarjeta   │
│ [Input] Fecha expiración    │
│ [Input] CVC                 │
│                             │
│ [Loading...] PROCESANDO     │
│                             │
│ [Button] PAGAR              │
│                             │
└─────────────────────────────┘
```

**Flujo de Integración:**

1. Usuario selecciona método de pago
2. Ingresa datos de tarjeta (o efectivo)
3. Hace tap en "PAGAR"
4. App muestra loading: "Procesando pago..."
5. Si pago exitoso (200):
   - Mostrar "✅ Pago confirmado"
   - Navegar a pantalla de "Pedido Confirmado"
   - Mostrar detalles del pedido y tiempo estimado
6. Si pago falla (402):
   - Mostrar error: "Tarjeta rechazada"
   - Permitir reintentar con otra tarjeta

---

## 7.3 Obtener Detalle del Pedido

**Descripción:** Devuelve la información completa de un pedido específico (cliente puede ver estado, items, etc.).

**Endpoint:** `GET /pedidos/{id}`  
**Autenticación:** Requerida (role: client)  
**Perfil:** Cliente

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "id": 1001,
    "stall_id": 1,
    "stall_name": "Anticuchos del Barrio",
    "stall_phone": "+51987654321",
    "status": "confirmed",
    "payment_status": "completed",
    "total_amount": 62.50,
    "items": [
      {
        "product_name": "Anticuchos de Corazón",
        "quantity": 2,
        "unit_price": 18.50,
        "subtotal": 37.00,
        "cremas": [
          {"name": "Ajo", "price": 1.50},
          {"name": "Cebolla", "price": 1.50}
        ]
      }
    ],
    "delivery_address": "Jr. Comercio 123, Miraflores",
    "delivery_latitude": -12.1023,
    "delivery_longitude": -77.0456,
    "estimated_delivery": "2026-05-26T16:32:00Z",
    "created_at": "2026-05-26T16:00:00Z",
    "timeline": [
      {
        "status": "confirmed",
        "timestamp": "2026-05-26T16:02:00Z",
        "message": "Pedido confirmado"
      },
      {
        "status": "preparing",
        "timestamp": "2026-05-26T16:05:00Z",
        "message": "Preparando tu pedido"
      }
    ]
  }
}
```

### Flujo en App Móvil (Pantalla: Order Status)

```
┌─────────────────────────────┐
│   ESTADO DEL PEDIDO #1001   │
├─────────────────────────────┤
│ 🟢 CONFIRMADO               │
│                             │
│ 🕐 LÍNEA DE TIEMPO:         │
│                             │
│ ✅ 16:00 Pedido confirmado  │
│ │                           │
│ ✅ 16:05 Preparando         │
│ │  Tiempo: 15 min           │
│ │                           │
│ ⏳ 16:30 Listo para entrega │
│ │  (estimado)               │
│ │                           │
│ ○  En ruta                  │
│                             │
│ 📍 ENTREGA:                 │
│ Jr. Comercio 123, Miraflores│
│                             │
│ 💰 Total: S/. 62.50         │
│                             │
│ [Button] RASTREAR PEDIDO    │
│ [Button] CONTACTAR VENDEDOR │
│ [Button] CANCELAR PEDIDO    │
│                             │
└─────────────────────────────┘
```

---

## 7.4 Listar Mis Pedidos (Cliente)

**Descripción:** Lista todos los pedidos del cliente autenticado (activos e históricos).

**Endpoint:** `GET /pedidos`  
**Autenticación:** Requerida (role: client)  
**Perfil:** Cliente

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1001,
      "stall_name": "Anticuchos del Barrio",
      "status": "confirmed",
      "total_amount": 62.50,
      "created_at": "2026-05-26T16:00:00Z",
      "estimated_delivery": "2026-05-26T16:32:00Z"
    },
    {
      "id": 1000,
      "stall_name": "Cevichería La Bahía",
      "status": "delivered",
      "total_amount": 45.00,
      "created_at": "2026-05-25T18:00:00Z",
      "delivered_at": "2026-05-25T18:35:00Z"
    }
  ]
}
```

### Flujo en App Móvil (Pantalla: Orders History)

```
┌─────────────────────────────┐
│   MIS PEDIDOS               │
├─────────────────────────────┤
│ [Tab] ACTIVOS | HISTÓRICO   │
│                             │
│ ACTIVOS:                    │
│ ┌─────────────────────────┐ │
│ │ #1001 Anticuchos del B  │ │
│ │ 🟡 En preparación       │ │
│ │ S/. 62.50               │ │
│ │ 16:00 - Est: 16:32      │ │
│ │ [Ver detalles]          │ │
│ └─────────────────────────┘ │
│                             │
│ HISTÓRICO:                  │
│ ┌─────────────────────────┐ │
│ │ #1000 Cevichería La B   │ │
│ │ ✅ Entregado            │ │
│ │ S/. 45.00               │ │
│ │ 25 de mayo              │ │
│ │ [Repetir orden]         │ │
│ └─────────────────────────┘ │
│                             │
└─────────────────────────────┘
```

---

## 7.5 Cancelar Pedido

**Descripción:** Cancela un pedido (solo si aún está en estado "pending" o "confirmed").

**Endpoint:** `PATCH /pedidos/{id}/cancelar`  
**Autenticación:** Requerida (role: client)  
**Perfil:** Cliente

### Request
```json
Body:
{
  "reason": "Cambié de opinión",
  "notes": "Prefiero otro puesto"
}
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Pedido cancelado correctamente",
  "data": {
    "id": 1001,
    "status": "cancelled",
    "cancellation_reason": "Cambié de opinión",
    "refund_status": "processing",
    "refund_amount": 62.50,
    "cancelled_at": "2026-05-26T16:10:00Z"
  }
}
```

### Flujo en App Móvil (Pantalla: Cancel Order Confirmation)

```
┌─────────────────────────────┐
│   ¿CANCELAR PEDIDO?         │
├─────────────────────────────┤
│                             │
│ ⚠️  Esta acción no se puede │
│ deshacer                    │
│                             │
│ [Dropdown] Razón:           │
│ ├─ Cambié de opinión        │
│ ├─ Demorará mucho           │
│ ├─ Preferí otro lugar       │
│ └─ Otro                     │
│                             │
│ [Input] Detalles adicionales│
│                             │
│ 💰 Se reembolsará: S/. 62.50│
│                             │
│ [Button] SÍ, CANCELAR       │
│ [Button] VOLVER             │
│                             │
└─────────────────────────────┘
```

---

## 7.6 Cambiar Dirección de Entrega

**Descripción:** Cambia la dirección de entrega de un pedido (solo si aún no está en preparación).

**Endpoint:** `PATCH /pedidos/{id}/cambiar-direccion`  
**Autenticación:** Requerida (role: client)  
**Perfil:** Cliente

### Request
```json
Body:
{
  "delivery_address": "Jr. Nueva 456, San Isidro",
  "delivery_latitude": -12.0856,
  "delivery_longitude": -77.0342
}
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Dirección actualizada correctamente",
  "data": {
    "id": 1001,
    "delivery_address": "Jr. Nueva 456, San Isidro",
    "delivery_latitude": -12.0856,
    "delivery_longitude": -77.0342,
    "updated_at": "2026-05-26T16:08:00Z"
  }
}
```

---

# 8. PEDIDOS (VENDEDOR)

## 8.1 Listar Mis Pedidos Activos (Vendedor)

**Descripción:** Lista todos los pedidos activos del puesto del vendedor.

**Endpoint:** `GET /mis-pedidos`  
**Autenticación:** Requerida (role: vendor)  
**Perfil:** Vendedor

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1001,
      "client_name": "Juan Perez",
      "client_phone": "+51987654321",
      "status": "preparing",
      "total_amount": 62.50,
      "items": [
        {
          "product_name": "Anticuchos de Corazón",
          "quantity": 2,
          "special_instructions": "Bien cocido"
        }
      ],
      "delivery_address": "Jr. Comercio 123",
      "estimated_delivery": "2026-05-26T16:32:00Z",
      "created_at": "2026-05-26T16:00:00Z"
    }
  ]
}
```

### Flujo en App Móvil (Pantalla: Vendor Dashboard)

```
┌─────────────────────────────┐
│   PEDIDOS ACTIVOS           │
├─────────────────────────────┤
│ [Filter] Estado | Hora      │
│                             │
│ ┌─────────────────────────┐ │
│ │ #1001 Juan Perez        │ │
│ │ 🟡 En preparación       │ │
│ │                         │ │
│ │ Anticuchos x2           │ │
│ │ "Bien cocido"           │ │
│ │                         │ │
│ │ Entrega: Jr. Comercio 123 │ │
│ │ Est: 16:32              │ │
│ │                         │ │
│ │ [Cambiar estado]        │ │
│ │ [Ver detalles]          │ │
│ └─────────────────────────┘ │
│                             │
└─────────────────────────────┘
```

---

## 8.2 Cambiar Estado del Pedido

**Descripción:** Cambia el estado de un pedido (pending → confirmed → preparing → ready → delivered).

**Endpoint:** `PATCH /pedidos/{id}/cambiar-estado`  
**Autenticación:** Requerida (role: vendor)  
**Perfil:** Vendedor

### Request
```json
Body:
{
  "status": "ready",
  "notes": "Pedido listo para entrega"
}
```

**Parámetros:**
- `status` (string, requerido): Nuevo estado
  - `confirmed`: Confirmado (cliente pagó)
  - `preparing`: En preparación
  - `ready`: Listo para entrega
  - `delivered`: Entregado
  - `cancelled`: Cancelado

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Estado actualizado correctamente",
  "data": {
    "id": 1001,
    "status": "ready",
    "status_changed_at": "2026-05-26T16:20:00Z",
    "timeline": [
      {
        "status": "confirmed",
        "timestamp": "2026-05-26T16:02:00Z"
      },
      {
        "status": "preparing",
        "timestamp": "2026-05-26T16:05:00Z"
      },
      {
        "status": "ready",
        "timestamp": "2026-05-26T16:20:00Z"
      }
    ]
  }
}
```

### Flujo en App Móvil (Pantalla: Change Order Status)

```
┌─────────────────────────────┐
│   CAMBIAR ESTADO DEL PEDIDO │
├─────────────────────────────┤
│                             │
│ Estado actual: En preparación│
│                             │
│ [Radio] Confirmado          │
│ [Radio] En preparación      │
│ [Radio] ✓ Listo             │
│ [Radio] Entregado           │
│                             │
│ [Input] Notas (opcional)    │
│                             │
│ [Button] GUARDAR CAMBIO     │
│                             │
└─────────────────────────────┘
```

---

# 9. COMPROBANTES ELECTRÓNICOS

## 9.1 Generar Comprobante (Invoice)

**Descripción:** Genera un comprobante electrónico (boleta/factura) para un pedido.

**Endpoint:** `POST /comprobantes/generar/{order_id}`  
**Autenticación:** Requerida  
**Perfil:** Cliente (para sus propios pedidos) / Admin

### Request
```json
Body:
{
  "tipo_comprobante": "boleta",
  "ruc_cliente": "12345678901"
}
```

### Response Exitosa (201 Created)
```json
{
  "success": true,
  "message": "Comprobante generado correctamente",
  "data": {
    "id": 2001,
    "order_id": 1001,
    "tipo": "boleta",
    "numero": "B001-00000123",
    "status": "generated",
    "fecha_emision": "2026-05-26T16:20:00Z",
    "pdf_url": "https://cdn.altoque.com/comprobantes/inv_2001.pdf",
    "xml_url": "https://cdn.altoque.com/comprobantes/inv_2001.xml"
  }
}
```

---

## 9.2 Descargar PDF

**Descripción:** Descarga el comprobante en formato PDF.

**Endpoint:** `GET /comprobantes/{invoice_id}/pdf`  
**Autenticación:** Requerida  
**Perfil:** Cliente (propietario) / Admin

### Response Exitosa (200 OK)
```
Content-Type: application/pdf
[Archivo PDF binario]
```

---

## 9.3 Descargar XML

**Descripción:** Descarga el comprobante en formato XML (para SUNAT).

**Endpoint:** `GET /comprobantes/{invoice_id}/xml`  
**Autenticación:** Requerida  
**Perfil:** Admin

### Response Exitosa (200 OK)
```
Content-Type: application/xml
[Archivo XML]
```

---

## 9.4 Listar Comprobantes

**Descripción:** Lista todos los comprobantes generados (clientes listan los suyos, admin lista todo).

**Endpoint:** `GET /comprobantes`  
**Autenticación:** Requerida  
**Perfil:** Cliente / Admin

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 2001,
      "order_id": 1001,
      "numero": "B001-00000123",
      "status": "generated",
      "fecha_emision": "2026-05-26T16:20:00Z"
    }
  ]
}
```

---

## 9.5 Ver Detalle del Comprobante

**Descripción:** Obtiene el detalle completo de un comprobante.

**Endpoint:** `GET /comprobantes/{invoice_id}`  
**Autenticación:** Requerida  
**Perfil:** Cliente (propietario) / Admin

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "id": 2001,
    "order_id": 1001,
    "numero": "B001-00000123",
    "tipo": "boleta",
    "status": "generated",
    "cliente": {
      "nombre": "Juan Perez",
      "documento": "12345678",
      "email": "juan@example.com"
    },
    "items": [
      {
        "descripcion": "Anticuchos de Corazón x2",
        "cantidad": 1,
        "precio_unitario": 37.00,
        "importe": 37.00
      }
    ],
    "subtotal": 59.00,
    "igv": 10.62,
    "total": 69.62,
    "fecha_emision": "2026-05-26T16:20:00Z"
  }
}
```

---

# 10. COMISIONES (VENDEDOR/ADMIN)

## 10.1 Calcular Comisión de Pedido

**Descripción:** Calcula la comisión que se cobra por un pedido específico.

**Endpoint:** `POST /comisiones/calcular`  
**Autenticación:** Requerida  
**Perfil:** Vendedor / Admin

### Request
```json
Body:
{
  "order_id": 1001
}
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "order_id": 1001,
    "order_total": 62.50,
    "commission_percentage": 5,
    "commission_amount": 3.13,
    "net_amount": 59.37,
    "details": {
      "base_calculation": 62.50,
      "applicable_percentage": "5%",
      "formula": "order_total * (commission_percentage / 100)"
    }
  }
}
```

---

## 10.2 Obtener Comisión de Pedido

**Descripción:** Obtiene la comisión calculada de un pedido.

**Endpoint:** `GET /comisiones/pedido/{order_id}`  
**Autenticación:** Requerida  
**Perfil:** Vendedor / Admin

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "order_id": 1001,
    "commission_amount": 3.13,
    "commission_percentage": 5,
    "calculated_at": "2026-05-26T16:20:00Z"
  }
}
```

---

## 10.3 Listar Reglas de Comisión (Admin)

**Descripción:** Lista todas las reglas de comisión configuradas en el sistema.

**Endpoint:** `GET /comisiones/reglas`  
**Autenticación:** Requerida (role: admin)  
**Perfil:** Admin

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Comisión estándar",
      "percentage": 5,
      "description": "Comisión aplicada a todos los pedidos",
      "active": true
    }
  ]
}
```

---

## 10.4 Crear Regla de Comisión (Admin)

**Descripción:** Crea una nueva regla de comisión.

**Endpoint:** `POST /comisiones/reglas`  
**Autenticación:** Requerida (role: admin)  
**Perfil:** Admin

### Request
```json
Body:
{
  "name": "Comisión premium",
  "percentage": 3,
  "description": "Para vendedores premium",
  "active": true
}
```

### Response Exitosa (201 Created)
```json
{
  "success": true,
  "message": "Regla creada correctamente",
  "data": {
    "id": 2,
    "name": "Comisión premium",
    "percentage": 3,
    "active": true
  }
}
```

---

## 10.5 Actualizar Regla de Comisión (Admin)

**Descripción:** Modifica una regla de comisión existente.

**Endpoint:** `PUT /comisiones/reglas/{rule_id}`  
**Autenticación:** Requerida (role: admin)  
**Perfil:** Admin

### Request
```json
Body:
{
  "percentage": 4
}
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Regla actualizada correctamente",
  "data": {
    "id": 2,
    "percentage": 4
  }
}
```

---

## 10.6 Transferir Comisiones Pendientes (Admin)

**Descripción:** Transfiere todas las comisiones pendientes a las cuentas de los vendedores.

**Endpoint:** `POST /comisiones/transferir`  
**Autenticación:** Requerida (role: admin)  
**Perfil:** Admin

### Request
```json
Body:
{
  "month": "2026-05"
}
```

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Comisiones transferidas correctamente",
  "data": {
    "total_transferred": 1523.50,
    "vendors_count": 12,
    "transfers": [
      {
        "vendor_id": 5,
        "vendor_name": "Juan Perez",
        "amount": 127.08,
        "status": "completed"
      }
    ]
  }
}
```

---

## 10.7 Reportes de Comisiones (Admin)

**Descripción:** Genera reportes detallados de comisiones por período.

**Endpoint:** `GET /comisiones/reporte?start_date={fecha}&end_date={fecha}`  
**Autenticación:** Requerida (role: admin)  
**Perfil:** Admin

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "period": "2026-05-01 to 2026-05-26",
    "total_orders": 450,
    "total_commissions": 2847.50,
    "by_vendor": [
      {
        "vendor_id": 5,
        "vendor_name": "Juan Perez",
        "orders_count": 45,
        "total_sales": 2541.00,
        "commission_percentage": 5,
        "commission_amount": 127.05
      }
    ]
  }
}
```

---

# 11. OTROS

## 11.1 Obtener Dashboard

**Descripción:** Obtiene un resumen del dashboard con información relevante para el usuario.

**Endpoint:** `GET /dashboard`  
**Autenticación:** Requerida  
**Perfil:** Todos

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "data": {
    "role": "client",
    "user_name": "Juan Perez",
    "stats": {
      "total_orders": 25,
      "pending_orders": 1,
      "total_spent": 1250.00,
      "average_order": 50.00
    },
    "recent_orders": [
      {
        "id": 1001,
        "stall_name": "Anticuchos del Barrio",
        "status": "confirmed",
        "created_at": "2026-05-26T16:00:00Z"
      }
    ]
  }
}
```

---

## 11.2 Endpoint de Prueba (Testing)

**Descripción:** Genera un token de prueba para testing en desarrollo.

**Endpoint:** `GET /test-token`  
**Autenticación:** No requerida  
**Perfil:** Desarrollo solo

### Response Exitosa (200 OK)
```json
{
  "success": true,
  "message": "Token de prueba generado",
  "data": {
    "user_id": 1,
    "user_name": "Test User",
    "role": "client",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_at": "2026-05-27T10:00:00Z"
  }
}
```

---

# 📋 GUÍA RÁPIDA DE INTEGRACIÓN

## Flujos Principales en la App Móvil

### Flujo 1: Cliente Hace un Pedido

```
1. [Home Screen] 
   → GET /puestos/nearby (mostrar mapa)
   
2. [Stall Detail Screen]
   → GET /puestos/{stallId}/status
   → GET /menu/{stallId}
   
3. [Menu Screen]
   → Usuario agrega productos al carrito
   
4. [Checkout Screen]
   → POST /pedidos (crear orden)
   
5. [Payment Screen]
   → POST /pedidos/{id}/pagar (procesar pago)
   
6. [Order Status Screen]
   → GET /pedidos/{id} (ver estado)
   → Polling cada 5-10 segundos
   
7. [Order Delivered]
   → Mostrar opción para calificar/comentar
```

### Flujo 2: Vendedor Gestiona Pedidos

```
1. [Vendor Dashboard]
   → GET /mis-pedidos (ver pedidos activos)
   
2. [Order Detail]
   → Ver items, dirección de entrega
   
3. [Change Status]
   → PATCH /pedidos/{id}/cambiar-estado
   → Transición: confirmed → preparing → ready → delivered
   
4. [Confirmación]
   → Cliente recibe notificación de cambio de estado
```

### Flujo 3: Vendedor Configura Su Menú

```
1. [Vendor Dashboard]
   → GET /mis-puesto
   
2. [Menu Management]
   → GET /mis-productos
   → GET /mis-categorias
   
3. [Add/Edit Product]
   → POST /mis-productos
   → PATCH /mis-productos/{id}
   
4. [Manage Add-ons]
   → GET /mis-cremas
   → POST /mis-productos/{id}/cremas
```

---

## Consideraciones de Implementación

### Almacenamiento Local (Local Storage / Keychain)
```
- Token de autenticación (persistente hasta logout)
- Datos del usuario (nombre, email, rol)
- Ubicación actual (GPS)
- Carrito de compras (datos locales)
- Preferencias de usuario (idioma, temas, etc.)
```

### Sincronización y Polling
```
- Pedidos: Polling cada 5-10 segundos para estado actualizado
- Notificaciones: Push notifications en tiempo real (opcional)
- Cache: Actualizar menú cada 1 hora o al abrir la app
```

### Manejo de Errores
```
Implementar reintentos para:
- Fallo de conexión: Reintentar 3 veces con backoff exponencial
- Timeout: Reintentar después de 5 segundos
- Error 5xx: Reintentar automáticamente
- Error 4xx: Mostrar al usuario y pedir corrección
```

### Performance
```
- Comprimir imágenes antes de subir (máx 500KB)
- Paginar listados (20 items por página)
- Cachear menús localmente
- Usar lazy loading para imágenes
```

---

## 📞 SOPORTE TÉCNICO

Para dudas sobre integración:
- Revisar ejemplos en: `ejemplos_requests/`
- Consultar archivo: `GUIA_AUTENTICACION_API.md`
- Ver arquitectura: `ARQUITECTURA_TECNICA.md`

---

**Versión:** 2.0  
**Fecha:** 26 de Mayo de 2026  
**Equipo:** Backend Development Team

