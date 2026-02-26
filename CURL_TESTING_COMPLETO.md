# 🖥️ TESTING CON cURL - RF7 & RF8

Para testing desde PowerShell o terminal, usa estos comandos.

---

## ⚠️ PASO 0: OBTENER TOKEN

```powershell
$token = curl.exe -X POST "http://localhost:8000/api/login" `
  -H "Content-Type: application/json" `
  -d '{
    "email": "vendedor@example.com",
    "password": "password"
  }' | ConvertFrom-Json | Select-Object -ExpandProperty token

echo "Token: $token"
```

O manualmente:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"vendedor@example.com","password":"password"}'
```

---

## 📝 RF8: CREMAS

### 1️⃣ CREAR CREMA

```powershell
$token = "TU_TOKEN_AQUI"

$body = @{
    nombre = "Queso Cheddar"
    precio_adicional = 2.50
    descripcion = "Queso cheddar derretido"
    activa = $true
} | ConvertTo-Json

curl.exe -X POST "http://localhost:8000/api/mis-cremas" `
  -H "Authorization: Bearer $token" `
  -H "Content-Type: application/json" `
  -d $body
```

---

### 2️⃣ VER TODAS LAS CREMAS

```powershell
$token = "TU_TOKEN_AQUI"

curl.exe -X GET "http://localhost:8000/api/mis-cremas" `
  -H "Authorization: Bearer $token"
```

---

### 3️⃣ ACTUALIZAR CREMA

```powershell
$token = "TU_TOKEN_AQUI"

$body = @{
    precio_adicional = 3.00
} | ConvertTo-Json

curl.exe -X PATCH "http://localhost:8000/api/mis-cremas/1" `
  -H "Authorization: Bearer $token" `
  -H "Content-Type: application/json" `
  -d $body
```

---

## 📦 ASOCIAR CREMAS A PRODUCTOS

### 4️⃣ ASOCIAR CREMA (OPCIONAL)

```powershell
$token = "TU_TOKEN_AQUI"

$body = @{
    crema_id = 1
    requerida = $false
} | ConvertTo-Json

curl.exe -X POST "http://localhost:8000/api/mis-productos/1/cremas" `
  -H "Authorization: Bearer $token" `
  -H "Content-Type: application/json" `
  -d $body
```

---

### 5️⃣ ASOCIAR CREMA (REQUERIDA)

```powershell
$token = "TU_TOKEN_AQUI"

$body = @{
    crema_id = 2
    requerida = $true
} | ConvertTo-Json

curl.exe -X POST "http://localhost:8000/api/mis-productos/1/cremas" `
  -H "Authorization: Bearer $token" `
  -H "Content-Type: application/json" `
  -d $body
```

---

### 6️⃣ VER CREMAS DE PRODUCTO

```powershell
$token = "TU_TOKEN_AQUI"

curl.exe -X GET "http://localhost:8000/api/mis-productos/1/cremas" `
  -H "Authorization: Bearer $token"
```

---

### 7️⃣ DESASOCIAR CREMA

```powershell
$token = "TU_TOKEN_AQUI"

curl.exe -X DELETE "http://localhost:8000/api/mis-productos/1/cremas/2" `
  -H "Authorization: Bearer $token"
```

---

## 🎁 RF7: PRODUCTOS CON IMÁGENES

### 8️⃣ CREAR PRODUCTO CON IMAGEN

⚠️ **IMPORTANTE:** En cURL, usar multipart/form-data

```powershell
$token = "TU_TOKEN_AQUI"
$imagePath = "C:\ruta\a\imagen.jpg"  # Ruta a tu imagen

curl.exe -X POST "http://localhost:8000/api/mis-productos" `
  -H "Authorization: Bearer $token" `
  -F "nombre=Salchipapas Premium" `
  -F "descripcion=Papa crispy con salchichón" `
  -F "precio=12.50" `
  -F "categoria_id=1" `
  -F "activo=true" `
  -F "destacado=true" `
  -F "imagen=@$imagePath"
```

---

### 9️⃣ ACTUALIZAR PRODUCTO CON IMAGEN

```powershell
$token = "TU_TOKEN_AQUI"
$imagePath = "C:\ruta\a\imagen_nueva.jpg"

curl.exe -X PATCH "http://localhost:8000/api/mis-productos/1" `
  -H "Authorization: Bearer $token" `
  -F "nombre=Salchipapas Premium Deluxe" `
  -F "precio=14.50" `
  -F "imagen=@$imagePath"
```

---

### 🔟 VER PRODUCTOS (CON CREMAS)

```powershell
$token = "TU_TOKEN_AQUI"

curl.exe -X GET "http://localhost:8000/api/mis-productos" `
  -H "Authorization: Bearer $token"
```

---

## 👥 MENÚ PÚBLICO

### 1️⃣1️⃣ VER MENÚ (SIN TOKEN)

```powershell
curl.exe -X GET "http://localhost:8000/api/menu/1"
```

O por slug:
```powershell
curl.exe -X GET "http://localhost:8000/api/menu/salchipapas-don-juan-abc123"
```

---

## 🔧 SCRIPT COMPLETO DE TESTING

Guarda esto en un archivo `test.ps1`:

```powershell
# Variables
$baseUrl = "http://localhost:8000/api"
$email = "vendedor@example.com"
$password = "password"

# 1. LOGIN
Write-Host "1. Obteniendo token..." -ForegroundColor Green
$loginResponse = curl.exe -X POST "$baseUrl/login" `
  -H "Content-Type: application/json" `
  -d "{`"email`":`"$email`",`"password`":`"$password`"}" | ConvertFrom-Json

$token = $loginResponse.token
Write-Host "✓ Token obtenido: $($token.Substring(0, 20))..." -ForegroundColor Green

# 2. CREAR CREMA
Write-Host "`n2. Creando crema..." -ForegroundColor Green
$creamaBody = @{
    nombre = "Queso Cheddar"
    precio_adicional = 2.50
    activa = $true
} | ConvertTo-Json

$cremaResponse = curl.exe -X POST "$baseUrl/mis-cremas" `
  -H "Authorization: Bearer $token" `
  -H "Content-Type: application/json" `
  -d $creamaBody | ConvertFrom-Json

$cremaId = $cremaResponse.crema.id
Write-Host "✓ Crema creada: ID=$cremaId" -ForegroundColor Green

# 3. VER CREMAS
Write-Host "`n3. Viendo cremas..." -ForegroundColor Green
$creamasResponse = curl.exe -X GET "$baseUrl/mis-cremas" `
  -H "Authorization: Bearer $token" | ConvertFrom-Json

Write-Host "✓ Total cremas: $($creamasResponse.total_cremas)" -ForegroundColor Green

# 4. CREAR PRODUCTO CON IMAGEN
Write-Host "`n4. Creando producto con imagen..." -ForegroundColor Green
$imagePath = "C:\ruta\a\imagen.jpg"  # CAMBIAR POR RUTA REAL

$productoResponse = curl.exe -X POST "$baseUrl/mis-productos" `
  -H "Authorization: Bearer $token" `
  -F "nombre=Salchipapas Premium" `
  -F "descripcion=Papa crispy" `
  -F "precio=12.50" `
  -F "categoria_id=1" `
  -F "destacado=true" `
  -F "imagen=@$imagePath" | ConvertFrom-Json

$productoId = $productoResponse.producto.id
Write-Host "✓ Producto creado: ID=$productoId" -ForegroundColor Green
Write-Host "  Imagen: $($productoResponse.producto.imagen)" -ForegroundColor Cyan

# 5. ASOCIAR CREMA
Write-Host "`n5. Asociando crema a producto..." -ForegroundColor Green
$asociarBody = @{
    crema_id = $cremaId
    requerida = $false
} | ConvertTo-Json

curl.exe -X POST "$baseUrl/mis-productos/$productoId/cremas" `
  -H "Authorization: Bearer $token" `
  -H "Content-Type: application/json" `
  -d $asociarBody > $null

Write-Host "✓ Crema asociada" -ForegroundColor Green

# 6. VER PRODUCTO CON CREMAS
Write-Host "`n6. Viendo producto con cremas..." -ForegroundColor Green
$productoConCremasResponse = curl.exe -X GET "$baseUrl/mis-productos" `
  -H "Authorization: Bearer $token" | ConvertFrom-Json

$producto = $productoConCremasResponse.productos[0]
Write-Host "✓ Producto: $($producto.nombre)" -ForegroundColor Green
Write-Host "  Cremas asociadas: $($producto.cremas.Count)" -ForegroundColor Cyan

# 7. VER MENÚ PÚBLICO
Write-Host "`n7. Viendo menú público (SIN TOKEN)..." -ForegroundColor Green
$menuResponse = curl.exe -X GET "$baseUrl/menu/1" | ConvertFrom-Json

Write-Host "✓ Menú visualizado" -ForegroundColor Green
Write-Host "  Puesto: $($menuResponse.puesto.nombre)" -ForegroundColor Cyan
Write-Host "  Productos: $($menuResponse.total_productos)" -ForegroundColor Cyan
Write-Host "  Primer producto: $($menuResponse.productos[0].nombre)" -ForegroundColor Cyan
Write-Host "  Cremas del primer producto: $($menuResponse.productos[0].cremas.Count)" -ForegroundColor Cyan

Write-Host "`n✓ ¡Todos los tests completados exitosamente!" -ForegroundColor Green
```

### Ejecutar el script:
```powershell
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
.\test.ps1
```

---

## 📊 TABLA RESUMEN DE URLs

```
CREMAS
POST   http://localhost:8000/api/mis-cremas
GET    http://localhost:8000/api/mis-cremas
PATCH  http://localhost:8000/api/mis-cremas/:id

PRODUCTOS
POST   http://localhost:8000/api/mis-productos
GET    http://localhost:8000/api/mis-productos
PATCH  http://localhost:8000/api/mis-productos/:id

ASOCIAR CREMAS
POST   http://localhost:8000/api/mis-productos/:producto_id/cremas
GET    http://localhost:8000/api/mis-productos/:producto_id/cremas
DELETE http://localhost:8000/api/mis-productos/:producto_id/cremas/:crema_id

MENÚ PÚBLICO
GET    http://localhost:8000/api/menu/:stallId
```

---

## 🎯 VERIFICAR RESPUESTAS

Guarda respuestas en JSON:

```powershell
$token = "TU_TOKEN"

# Guardar respuesta en archivo
curl.exe -X GET "http://localhost:8000/api/mis-productos" `
  -H "Authorization: Bearer $token" | Out-File -Encoding UTF8 productos.json

# Visualizar
Get-Content productos.json | ConvertFrom-Json | Format-List
```

---

¡Usa estos comandos para testing rápido! 🚀

