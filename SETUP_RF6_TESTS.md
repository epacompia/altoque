# SCRIPT DE SETUP PARA PRUEBAS RF6

## Paso 1: Crear usuario vendedor con puesto

```bash
php artisan tinker
```

```php
# Crear usuario vendedor
$user = App\Models\User::create([
    'name' => 'Juan Vendedor',
    'first_name' => 'Juan',
    'last_name' => 'Vendedor',
    'email' => 'juan.vendedor@test.com',
    'role' => 'vendor',
    'password' => null
]);

# Crear puesto
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

# Obtener token
$token = $user->createToken('TestToken')->plainTextToken;
echo "TOKEN: " . $token;

# Copiar el token para Postman
```

---

## Paso 2: Crear categorías (requerido para agregar productos)

```php
$cat1 = App\Models\Category::firstOrCreate(
    ['name' => 'Platos'],
    ['name' => 'Platos']
);

$cat2 = App\Models\Category::firstOrCreate(
    ['name' => 'Bebidas'],
    ['name' => 'Bebidas']
);

echo "Categorías creadas";
```

Presionar `exit` para salir de Tinker

---

## Paso 3: Probar en Postman (usando el token)

Reemplaza `{TOKEN}` con el token obtenido arriba.

### 1. GET /api/mis-puesto
```
GET http://localhost/api/mis-puesto
Headers:
  Authorization: Bearer {TOKEN}
```

### 2. POST /api/mis-puesto/generar-qr
```
POST http://localhost/api/mis-puesto/generar-qr
Headers:
  Authorization: Bearer {TOKEN}
Body: {} (vacío)
```

### 3. POST /api/mis-productos
```
POST http://localhost/api/mis-productos
Headers:
  Authorization: Bearer {TOKEN}
Body:
{
  "nombre": "Salchipapas Clásica",
  "descripcion": "Papa con salchichón y crema casera",
  "precio": 8.50,
  "categoria_id": 1,
  "activo": true,
  "destacado": false
}
```

### 4. POST /api/mis-productos (con otro producto)
```
POST http://localhost/api/mis-productos
Headers:
  Authorization: Bearer {TOKEN}
Body:
{
  "nombre": "Oferta del Día",
  "descripcion": "Salchipapas a mitad de precio",
  "precio": 5.00,
  "categoria_id": 1,
  "activo": true,
  "destacado": true
}
```

### 5. GET /api/mis-productos
```
GET http://localhost/api/mis-productos
Headers:
  Authorization: Bearer {TOKEN}
```

### 6. PATCH /api/mis-productos/1
```
PATCH http://localhost/api/mis-productos/1
Headers:
  Authorization: Bearer {TOKEN}
Body:
{
  "activo": false,
  "destacado": true,
  "precio": 7.50
}
```

### 7. GET /api/menu/salchipapas-don-juan (SIN token - Cliente)
```
GET http://localhost/api/menu/salchipapas-don-juan
Headers:
  Content-Type: application/json
(SIN Authorization)
```

### 8. GET /api/menu/salchipapas-don-juan (CON token - Cliente autenticado)
```
GET http://localhost/api/menu/salchipapas-don-juan
Headers:
  Authorization: Bearer {CUALQUIER_TOKEN_DE_CLIENTE}
  Content-Type: application/json
```

---

## Notas importantes

- El puesto está creado en `convertirse-vendedor`, pero aquí lo creamos manualmente para pruebas
- Las categorías deben existir antes de crear productos
- El `slug` se usa en la URL del menú: `/menu/{slug}`
- `usuario_autenticado` es un flag que retorna true/false dependiendo si hay token
