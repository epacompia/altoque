# RF18 - SISTEMA DE COMISIONES - IMPLEMENTACIÓN COMPLETA

## ✅ Resumen de Implementación

Se ha implementado completamente el **RF18 - Cálculo automático de comisiones por pedido** con todas las características especificadas. El sistema calcula automáticamente la comisión de cada pedido pagado aplicando reglas de negocio flexibles y configurables.

---

## 📊 Componentes Creados

### 1. **Migraciones** (`2026_02_21_create_commission_rules_table.php`)
- ✅ Tabla `commission_rules`: Define reglas de comisión
- ✅ Tabla `order_commissions`: Registra comisión de cada pedido
- ✅ Tabla `commission_audit_logs`: Auditoría de cambios en reglas

### 2. **Modelos**
- ✅ `CommissionRule`: Gestiona reglas de comisión
- ✅ `OrderCommission`: Registro de comisión por pedido
- ✅ `CommissionAuditLog`: Historial de cambios

### 3. **Servicios**
- ✅ `CommissionService`: Lógica de cálculo de comisiones

### 4. **Controlador**
- ✅ `CommissionController`: Todos los endpoints

### 5. **Rutas API**
- ✅ Endpoints de comisiones agregadas a `routes/api.php`

---

## 🎯 Características Principales

### Sistema de Reglas de Comisión (4 Tipos)

| Tipo | Descripción | Ejemplo |
|---|---|---|
| **base** | Comisión predeterminada | 10% para todos |
| **category** | Por categoría de producto | 15% para bebidas |
| **price_range** | Por rango de monto | 12% para pedidos >S/100 |
| **vendor_type** | Por tipo de vendedor | 8% para premium |

### Rangos Permitidos
- ✅ Comisión mínima: 10%
- ✅ Comisión máxima: 25%
- ✅ Vigencia temporal: Desde/Hasta (opcional)

### Cálculo de Comisión
El sistema aplica la regla en este **orden de prioridad**:
1. 🥇 **Rango de precio** (más específico)
2. 🥈 **Categoría de producto**
3. 🥉 **Tipo de vendedor**
4. 🏆 **Comisión base** (fallback)

---

## 📡 Endpoints API

### **Gestión de Reglas (Admin only)**

#### 1. Listar Reglas
```
GET /api/comisiones/reglas?activas=true&tipo=base
```
**Respuesta:**
```json
{
  "exito": true,
  "total": 4,
  "datos": [
    {
      "id": 1,
      "nombre": "Comisión Base",
      "tipo": "base",
      "porcentaje": "10%",
      "activa": "Sí"
    }
  ]
}
```

#### 2. Crear Regla
```
POST /api/comisiones/reglas
```
**Body:**
```json
{
  "nombre": "Comisión Base",
  "tipo": "base",
  "porcentaje_comision": 10,
  "vigencia_desde": "2026-02-01",
  "vigencia_hasta": "2026-12-31",
  "activa": true,
  "notas": "Comisión por defecto"
}
```

#### 3. Actualizar Regla
```
PUT /api/comisiones/reglas/{rule_id}
```

#### 4. Desactivar Regla
```
DELETE /api/comisiones/reglas/{rule_id}
```

---

### **Cálculo de Comisiones**

#### 5. Calcular Comisión de un Pedido
```
POST /api/comisiones/calcular
```
**Body:**
```json
{
  "order_id": 1
}
```
**Respuesta:**
```json
{
  "exito": true,
  "mensaje": "Comisión calculada exitosamente",
  "datos": {
    "comision_id": 5,
    "pedido_id": 1,
    "monto_pedido": "S/125.50",
    "porcentaje_comision": "10%",
    "monto_comision": "S/12.55",
    "monto_neto_vendedor": "S/112.95",
    "regla_aplicada": "Comisión base: 10%",
    "estado": "pending",
    "calculada_en": "2026-02-21T10:30:00"
  }
}
```

#### 6. Obtener Comisión de un Pedido
```
GET /api/comisiones/pedido/{order_id}
```

---

### **Reportes (Admin only)**

#### 7. Reporte de Comisiones
```
GET /api/comisiones/reporte?desde=2026-02-01&hasta=2026-02-28
```
**Respuesta:**
```json
{
  "exito": true,
  "reporte": {
    "periodo": {
      "desde": "2026-02-01",
      "hasta": "2026-02-28"
    },
    "resumen": {
      "total_pedidos": 25,
      "monto_total_pedidos": "S/3,250.00",
      "comisiones_totales": "S/325.00",
      "monto_neto_vendedores": "S/2,925.00"
    },
    "por_estado": {
      "pending": 5,
      "completed": 20,
      "refunded": 0
    }
  }
}
```

#### 8. Auditoría de Cambios
```
GET /api/comisiones/auditoria?desde=2026-02-01&hasta=2026-02-28&accion=updated
```

---

## 🔐 Validaciones

✅ Porcentaje entre 10% y 25%
✅ Solo pedidos con estado "confirmed" pueden calcular comisión
✅ Vigencia temporal se valida automáticamente
✅ Auditoría de todos los cambios
✅ Cálculo inmediato al confirmar pago

---

## 📋 Estructura de BD

### Tabla: `commission_rules`
```sql
- id (PK)
- name (string)
- type (enum: base, category, price_range, vendor_type)
- commission_percentage (decimal 5,2)
- category_id (nullable)
- min_amount, max_amount (nullable)
- vendor_type (nullable)
- valid_from, valid_until (nullable dates)
- is_active (boolean)
- created_by (string)
- notes (text)
- timestamps
```

### Tabla: `order_commissions`
```sql
- id (PK)
- order_id (FK)
- commission_rule_id (FK)
- order_total (decimal)
- commission_percentage (decimal)
- commission_amount (decimal)
- net_amount (decimal)
- status (pending, completed, refunded)
- rule_applied (string)
- calculated_at (timestamp)
- transferred_at (nullable timestamp)
- timestamps
```

### Tabla: `commission_audit_logs`
```sql
- id (PK)
- commission_rule_id (FK, nullable)
- action (created, updated, deleted, calculated, transferred, refunded)
- old_values (json)
- new_values (json)
- changed_by (string)
- ip_address (nullable)
- notes (text)
- timestamps
```

---

## 🧪 Colección Postman

**Archivo:** `ejemplos_requests/RF18_COMISIONES.json`

### Tests Incluidos (16 solicitudes):
1. ✅ Login Admin
2. ✅ Login Cliente
3. ✅ Login Vendedor
4. ✅ Crear Regla Base (10%)
5. ✅ Crear Regla Bebidas (15%)
6. ✅ Crear Regla Rango de Precio
7. ✅ Crear Regla Vendedor Premium
8. ✅ Listar todas las Reglas
9. ✅ Crear Pedido
10. ✅ Pagar Pedido
11. ✅ Calcular Comisión
12. ✅ Obtener Comisión del Pedido
13. ✅ Actualizar Regla
14. ✅ Reporte de Comisiones
15. ✅ Auditoría de Cambios
16. ✅ Desactivar Regla
17. ✅ Regla con Vigencia Temporal
18. ✅ Error: Porcentaje inválido
19. ✅ Error: Calcular sin pago

---

## 🚀 Cómo Usar

### 1. Importar Colección en Postman
```
1. Abrir Postman
2. Click en "Import"
3. Seleccionar: ejemplos_requests/RF18_COMISIONES.json
4. Click "Import"
```

### 2. Flujo de Prueba Recomendado
```
1. Ejecutar "Setup - Login como Admin"
   → Copiar token al Bearer Token
   
2. Ejecutar "Test 1-4: Crear Reglas"
   → Crear 4 tipos de reglas diferentes
   
3. Ejecutar "Test 5: Listar Reglas"
   → Verificar que se crearon correctamente
   
4. Ejecutar "Setup - Login como Cliente"
   → Cambiar token al de cliente
   
5. Ejecutar "Test 6: Crear Pedido"
   → Anotatar el order_id de la respuesta
   
6. Ejecutar "Test 7: Pagar Pedido"
   → El pedido cambia a estado "confirmed"
   
7. Ejecutar "Test 8: Calcular Comisión"
   → Calcula automáticamente basado en las reglas
   
8. Ejecutar "Test 9: Obtener Comisión"
   → Ver detalles de la comisión calculada
   
9. Volver a "Setup - Login como Admin"
   
10. Ejecutar "Test 11: Reporte de Comisiones"
    → Ver resumen de todas las comisiones
    
11. Ejecutar "Test 12: Auditoría"
    → Ver historial de cambios
```

---

## 💡 Lógica de Cálculo

**Ejemplo con Pedido de S/ 125.50:**

```
1. Verificar reglas activas y vigentes
   - Base: 10% (siempre hay)
   - Rango: Si monto > 100 → 12%
   
2. Aplicar prioridad:
   - ¿Cumple price_range? Sí (125.50 > 100) → Usar 12%
   
3. Calcular:
   - Comisión = 125.50 × 12% = S/15.06
   - Neto vendedor = 125.50 - 15.06 = S/110.44
   
4. Registrar:
   - commission_id: 1
   - status: "pending"
   - rule_applied: "Comisión por rango de precio: 12%"
```

---

## 📝 Notas Importantes

⚠️ **Auditoría Inmutable:** Todos los cambios se registran y no pueden eliminarse
⚠️ **Validación Rígida:** El porcentaje siempre estará entre 10% y 25%
⚠️ **Regla por Defecto:** Si no hay regla activa, el sistema crea una base automáticamente
⚠️ **Cálculo Exacto:** Se redondea a 2 decimales (moneda peruana)
⚠️ **Solo Confirmed:** Solo calcula para pedidos pagados (status='confirmed')

---

## 🔄 Próximos Pasos (RF19-RF20)

Una vez RF18 esté validado:
- **RF19:** Variables dinámicas de comisión según políticas
- **RF20:** Flujo financiero automático (empresa → vendedor)
- **RF21:** Boletas/Facturas con desglose de comisión

---

## 📞 Contacto

Para dudas o mejoras en la implementación de comisiones, contactar al equipo de desarrollo.
