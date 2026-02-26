# RF18 - REFERENCIA RÁPIDA DE PRUEBA

## 🚀 Inicio Rápido

### Paso 1: Login como Admin
```
GET http://localhost:8000/api/test-token?user_type=admin
```
**Copiar el token**: `TOKEN_ADMIN`

---

## 📝 Crear Reglas de Comisión

### Regla 1: Base (10%)
```bash
POST /api/comisiones/reglas
Authorization: Bearer TOKEN_ADMIN

{
  "nombre": "Comisión Base",
  "tipo": "base",
  "porcentaje_comision": 10,
  "activa": true
}
```
**Response:** `rule_id = 1`

---

### Regla 2: Bebidas (15%)
```bash
POST /api/comisiones/reglas
Authorization: Bearer TOKEN_ADMIN

{
  "nombre": "Bebidas",
  "tipo": "category",
  "porcentaje_comision": 15,
  "category_id": "2",
  "activa": true
}
```

---

### Regla 3: Pedidos Grandes (12%)
```bash
POST /api/comisiones/reglas
Authorization: Bearer TOKEN_ADMIN

{
  "nombre": "Pedidos Grandes",
  "tipo": "price_range",
  "porcentaje_comision": 12,
  "monto_minimo": 100,
  "monto_maximo": 999999,
  "activa": true
}
```

---

## 🧪 Prueba de Cálculo

### Paso 1: Login Cliente
```
GET http://localhost:8000/api/test-token?user_type=cliente
```
**Copiar token**: `TOKEN_CLIENTE`

---

### Paso 2: Crear Pedido
```bash
POST /api/pedidos
Authorization: Bearer TOKEN_CLIENTE

{
  "stall_id": 2,
  "productos": [
    {"producto_id": 1, "cantidad": 1, "cremas": []},
    {"producto_id": 2, "cantidad": 1, "cremas": []}
  ],
  "direccion": "Jr. Lima 123",
  "with_delivery": false,
  "notas": "Test"
}
```
**Response:** `order_id = 1` y `total = 85.00`

---

### Paso 3: Pagar Pedido
```bash
POST /api/pedidos/1/pagar
Authorization: Bearer TOKEN_CLIENTE

{
  "metodo_pago": "mock"
}
```
**Response:** Status cambia a `confirmed`

---

### Paso 4: Calcular Comisión
```bash
POST /api/comisiones/calcular
Authorization: Bearer TOKEN_CLIENTE

{
  "order_id": 1
}
```

**Resultado esperado (Ejemplo):**
```json
{
  "exito": true,
  "datos": {
    "comision_id": 1,
    "pedido_id": 1,
    "monto_pedido": "85.00",
    "porcentaje_comision": "10%",
    "monto_comision": "8.50",
    "monto_neto_vendedor": "76.50",
    "regla_aplicada": "Comisión base: 10%"
  }
}
```

---

### Paso 5: Obtener Detalles
```bash
GET /api/comisiones/pedido/1
Authorization: Bearer TOKEN_CLIENTE
```

---

## 📊 Reportes (Admin)

### Reporte por Período
```bash
GET /api/comisiones/reporte?desde=2026-02-01&hasta=2026-02-28
Authorization: Bearer TOKEN_ADMIN
```

**Muestra:**
- Total de pedidos
- Comisiones totales
- Monto neto para vendedores
- Desglose por estado
- Desglose por regla

---

### Auditoría de Cambios
```bash
GET /api/comisiones/auditoria
Authorization: Bearer TOKEN_ADMIN
```

---

## ✨ Características Claves

| Característica | Detalles |
|---|---|
| **Rango Comisión** | 10% - 25% |
| **Tipos de Reglas** | base, category, price_range, vendor_type |
| **Prioridad** | Rango > Categoría > Tipo Vendedor > Base |
| **Vigencia** | Opcional (desde/hasta) |
| **Auditoría** | Todos los cambios se registran |
| **Validación** | Inmediata al guardar |
| **Cálculo** | Automático al confirmar pedido |

---

## 🎯 Escenarios de Prueba

### Escenario 1: Comisión Base
- Crear pedido: S/ 50
- Comisión (10%): S/ 5.00
- Neto: S/ 45.00

### Escenario 2: Rango de Precio (>100)
- Crear pedido: S/ 150
- Comisión (12%): S/ 18.00
- Neto: S/ 132.00

### Escenario 3: Categoría (Bebidas)
- Crear pedido con bebida: S/ 75
- Comisión (15%): S/ 11.25
- Neto: S/ 63.75

---

## ❌ Errores Comunes

| Error | Causa | Solución |
|---|---|---|
| `Porcentaje fuera de rango` | Porcentaje < 10 o > 25 | Usar 10-25 |
| `Pedido no confirmado` | No se pagó | Llamar endpoint pagar primero |
| `Regla no encontrada` | ID incorrecto | Listar y usar ID válido |
| `No autorizado` | Token inválido/expirado | Obtener nuevo token |

---

## 📱 Postman

Toda la colección está en: `ejemplos_requests/RF18_COMISIONES.json`

Importar en Postman:
1. File → Import
2. Seleccionar RF18_COMISIONES.json
3. Click Import
4. Ejecutar tests en orden

---

## 💾 Base de Datos

Tablas creadas:
- ✅ `commission_rules` - Definición de reglas
- ✅ `order_commissions` - Comisión de cada pedido
- ✅ `commission_audit_logs` - Historial de cambios

---

**¡Sistema RF18 listo para usar!** 🎉
