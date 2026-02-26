# RF4 - Dashboard API: Colección Postman

Esta colección permite probar el endpoint `/api/dashboard` (RF4 - Panel de entrada personalizado) en diversos escenarios.

## Casos incluidos

1. **RF4 - Test 1 - Dashboard como Cliente**
   - Prueba el endpoint autenticado como cliente.
   - Header: `Authorization: Bearer TOKEN_CLIENTE_AQUI`

2. **RF4 - Test 2 - Dashboard como Vendedor**
   - Prueba el endpoint autenticado como vendedor.
   - Header: `Authorization: Bearer TOKEN_VENDEDOR_AQUI`

3. **RF4 - Test 3 - Dashboard como Admin**
   - Prueba el endpoint autenticado como admin.
   - Header: `Authorization: Bearer TOKEN_ADMIN_AQUI`

4. **RF4 - Test 4 - Token inválido**
   - Prueba el endpoint con un token inválido.
   - Header: `Authorization: Bearer TOKEN_INVALIDO`

5. **RF4 - Test 5 - Sin token (no autenticado)**
   - Prueba el endpoint sin autenticación.

## Ejemplo de petición

```
GET http://localhost:8000/api/dashboard
Authorization: Bearer TU_TOKEN_AQUI
```

## Ejemplo de respuesta

```json
{
  "role": "client",
  "quick_access": [
    {"icon": "shopping_cart", "label": "Nuevo Pedido", "route": "/pedidos"},
    // ...otros 11 accesos...
  ],
  "suggestions": [
    {"title": "Salchipapas Clásicas", "image": "/images/salchipapas.jpg", "route": "/menu/1"},
    // ...otros 2...
  ]
}
```

## Importar colección

Guarda el archivo `.json` generado en la respuesta anterior y cárgalo en Postman para ejecutar los tests.
