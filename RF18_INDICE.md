# 📑 RF18 - ÍNDICE DE DOCUMENTACIÓN Y ARCHIVOS

## 📍 INICIO RÁPIDO

Para empezar **AHORA MISMO** en 5 minutos:

1. 📥 Importar: `ejemplos_requests/RF18_DEMO_SIMPLE.json` en Postman
2. ▶️ Ejecutar los 8 tests en orden
3. 🎯 Ver cómo se calcula la comisión automáticamente

---

## 📚 DOCUMENTACIÓN (Lee según tu necesidad)

### 🚀 Si tienes 5 minutos
```
📄 RF18_QUICK_START.md
   └─ Referencia rápida
   └─ Flujo de 5 pasos
   └─ Ejemplos prácticos
   └─ Troubleshooting básico
```

### 🎓 Si tienes 15 minutos
```
📄 RF18_INSTRUCCIONES.md
   └─ Manual completo de prueba
   └─ Cómo funciona
   └─ Todos los endpoints
   └─ Ejemplos de uso
```

### 📊 Si tienes 30 minutos
```
📄 RF18_IMPLEMENTACION.md
   └─ Especificación técnica
   └─ Estructura de código
   └─ Detalles de BD
   └─ Lógica de cálculo
   └─ Características avanzadas
```

### ✅ Si quieres un resumen
```
📄 RF18_RESUMEN.md
   └─ Estado final
   └─ Checklist de validación
   └─ Próximos pasos
```

---

## 📁 ARCHIVOS DE CÓDIGO

### Modelos (app/Models/)
```
CommissionRule.php
├─ Reglas de comisión configurables
├─ Métodos para obtener reglas activas
├─ Validación de porcentajes
└─ Relaciones con OrderCommission

OrderCommission.php
├─ Registro de comisión por pedido
├─ Relaciones con Order y CommissionRule
├─ Métodos de filtrado
└─ Seguimiento de estado

CommissionAuditLog.php
├─ Log inmutable de cambios
├─ Relación con CommissionRule
└─ Método de registro
```

### Servicios (app/Services/)
```
CommissionService.php
├─ calculateCommissionForOrder() ← CORE
├─ selectBestRule()
├─ calculateAmount()
├─ getCommissionReport()
├─ markAsCompleted()
├─ refundCommission()
├─ updateRule()
└─ createRule()
```

### Controlador (app/Http/Controllers/)
```
CommissionController.php
├─ calcularComision()
├─ obtenerComisionPedido()
├─ listarReglas()
├─ crearRegla()
├─ actualizarRegla()
├─ eliminarRegla()
├─ reporteComisiones()
└─ auditoria()
```

### Migración (database/migrations/)
```
2026_02_21_create_commission_rules_table.php
├─ Tabla: commission_rules
├─ Tabla: order_commissions
└─ Tabla: commission_audit_logs
```

### Rutas (routes/api.php)
```
POST   /api/comisiones/calcular
GET    /api/comisiones/pedido/{order_id}
GET    /api/comisiones/reglas
POST   /api/comisiones/reglas
PUT    /api/comisiones/reglas/{rule_id}
DELETE /api/comisiones/reglas/{rule_id}
GET    /api/comisiones/reporte
GET    /api/comisiones/auditoria
```

---

## 📡 COLECCIONES POSTMAN

### 1️⃣ RF18_DEMO_SIMPLE.json (⭐ RECOMENDADO)
```
Propósito: Demostración en 5 pasos
Duración: 5 minutos
Tests: 8

Flujo:
  RF18 - Setup 0 - Login como Admin (admin)
  RF18 - Test 1 - Crear Regla Base 10% (admin)
  RF18 - Test 2 - Crear Regla Rango de Precio (admin)
  RF18 - Setup 1 - Login como Cliente (cliente)
  RF18 - Test 3 - Crear Pedido con Productos (cliente)
  RF18 - Test 4 - Procesar Pago del Pedido (cliente)
  RF18 - Test 5 - Calcular Comisión del Pedido (cliente)
  RF18 - Test 6 - Reporte de Comisiones por Período (admin)
```

### 2️⃣ RF18_COMISIONES.json (COMPLETO)
```
Propósito: Prueba exhaustiva
Duración: 20 minutos
Tests: 19

Incluye:
  - Todos los tipos de reglas
  - Todos los casos de uso
  - Casos de error
  - Validaciones
  - Auditoría
  - Reportes
```

---

## 🔄 TIPO DE REGLAS (4 Opciones)

### Base (Comisión por defecto)
```json
{
  "tipo": "base",
  "porcentaje_comision": 10,
  "ejemplo": "Aplica a todos si no hay otra regla"
}
```

### Categoría (Por tipo de producto)
```json
{
  "tipo": "category",
  "porcentaje_comision": 15,
  "category_id": "2",
  "ejemplo": "15% para bebidas, 10% para comidas"
}
```

### Rango de Precio (Por monto de pedido)
```json
{
  "tipo": "price_range",
  "porcentaje_comision": 12,
  "monto_minimo": 100,
  "monto_maximo": 999999,
  "ejemplo": "12% para pedidos >S/100"
}
```

### Tipo Vendedor (Por nivel del vendedor)
```json
{
  "tipo": "vendor_type",
  "porcentaje_comision": 8,
  "vendor_type": "premium",
  "ejemplo": "8% para vendedores premium"
}
```

---

## 📊 PRIORIDAD DE APLICACIÓN

```
Cuando se calcula una comisión, el sistema busca en este orden:

1️⃣ Rango de Precio (MÁS ESPECÍFICO)
   ├─ ¿Cumple? → Usar esta regla
   └─ ¿No cumple? → Siguiente

2️⃣ Categoría de Producto
   ├─ ¿Cumple? → Usar esta regla
   └─ ¿No cumple? → Siguiente

3️⃣ Tipo de Vendedor
   ├─ ¿Cumple? → Usar esta regla
   └─ ¿No cumple? → Siguiente

4️⃣ Base (MENOS ESPECÍFICO)
   └─ Siempre existe, es el fallback
```

---

## 🧮 EJEMPLO PRÁCTICO

```
Pedido: S/150 (2 productos, no bebida, vendedor normal)

Reglas activas:
  - Base: 10%
  - Rango: 12% (pedidos >S/100)
  - Bebidas: 15%

Proceso:
  1. ¿Cumple rango (150 > 100)? SÍ ✓
     → Usar regla "Rango": 12%
  
  2. Calcular:
     Comisión = 150 × 12% = S/18.00
     Neto = 150 - 18 = S/132.00

Resultado:
  ✅ Comisión: S/18.00
  ✅ Monto para vendedor: S/132.00
  ✅ % aplicado: 12% (por rango)
```

---

## 🔐 VALIDACIONES IMPLEMENTADAS

✅ Porcentaje entre 10% y 25% (obligatorio)
✅ Solo calcula para pedidos con status "confirmed"
✅ Vigencia temporal se valida automáticamente
✅ No se puede eliminar, solo desactivar
✅ Auditoría inmutable de cambios
✅ Cálculo en 2 decimales (moneda peruana)
✅ Redondeo correcto para soles

---

## 📈 REPORTES DISPONIBLES

### Reporte por Período
```
GET /api/comisiones/reporte?desde=2026-02-01&hasta=2026-02-28

Devuelve:
  - Total de pedidos
  - Monto total de pedidos
  - Total de comisiones
  - Monto neto para vendedores
  - Desglose por estado (pending/completed/refunded)
  - Desglose por regla aplicada
```

### Auditoría de Cambios
```
GET /api/comisiones/auditoria?desde=2026-02-01&hasta=2026-02-28

Devuelve:
  - Quién cambió la regla
  - Cuándo cambió
  - Qué valores tenía antes
  - Qué valores tiene ahora
  - Desde qué IP se hizo el cambio
  - Notas del cambio
```

---

## 🚦 ESTADOS DE COMISIÓN

```
pending
├─ Comisión calculada pero no transferida
└─ Se genera al confirmar pago

completed
├─ Comisión transferida al vendedor
└─ Se completa cuando se ejecuta la transferencia

refunded
├─ Comisión reembolsada (pedido cancelado)
└─ Se usa si el pedido se devuelve
```

---

## ✅ CHECKLIST ANTES DE USAR EN PRODUCCIÓN

- [ ] Migraciones ejecutadas: `php artisan migrate`
- [ ] Base de datos creada con 3 tablas nuevas
- [ ] Controlador importado en routes/api.php
- [ ] Servicio disponible para inyectar
- [ ] Permisos de admin configurados
- [ ] Colección Postman importada
- [ ] Al menos 1 test pasado en Postman
- [ ] Reporte de comisiones funciona
- [ ] Auditoría muestra cambios
- [ ] Tokens de prueba generados

---

## 🎯 PRÓXIMOS PASOS

Con RF18 implementado, el siguiente ciclo es:

### RF19 (Variables Dinámicas)
```
- Diferentes porcentajes por hora
- Descuentos por volumen
- Bonificaciones temporales
```

### RF20 (Flujo Financiero)
```
- Transferencia automática al vendedor
- Integración con Yape/PLIN Business
- Notificaciones de transferencia
```

### RF21 (Boletas Electrónicas)
```
- Desglose de comisión en boleta
- Integración con SUNAT
- Descarga de comprobantes
```

---

## 📞 PREGUNTAS FRECUENTES

### ¿Cuál es la comisión mínima?
**Respuesta:** 10%

### ¿Cuál es la comisión máxima?
**Respuesta:** 25%

### ¿Se puede cambiar la comisión?
**Respuesta:** Sí, pero queda registrado en auditoría

### ¿Se puede tener comisión de 8%?
**Respuesta:** No, el sistema rechazará valores < 10%

### ¿Las reglas se aplican retroactivamente?
**Respuesta:** No, solo afectan nuevos pedidos

### ¿Quién puede crear reglas?
**Respuesta:** Solo admin

### ¿Los clientes ven la comisión?
**Respuesta:** No, es interna. El cliente ve solo el total a pagar

### ¿Se puede desactivar una regla sin perder historial?
**Respuesta:** Sí, se marca como inactiva pero queda el historial

---

## 🎓 RECURSOS

```
Documentos:
  ├─ RF18_QUICK_START.md         (5 min)
  ├─ RF18_INSTRUCCIONES.md       (15 min)
  ├─ RF18_IMPLEMENTACION.md      (30 min)
  └─ RF18_RESUMEN.md             (10 min)

Código:
  ├─ app/Models/                 (3 modelos)
  ├─ app/Services/CommissionService.php
  ├─ app/Http/Controllers/CommissionController.php
  ├─ database/migrations/2026_02_21*
  └─ routes/api.php               (8 rutas)

Postman:
  ├─ RF18_DEMO_SIMPLE.json        (⭐ START HERE)
  └─ RF18_COMISIONES.json         (Completo)
```

---

## 🏆 RESUMEN

| Aspecto | Status |
|---------|--------|
| **Implementación** | ✅ 100% Completada |
| **Testing** | ✅ 19 tests incluidos |
| **Documentación** | ✅ 4 documentos |
| **Código** | ✅ Producción lista |
| **Base de Datos** | ✅ Migraciones lista |
| **API** | ✅ 8 endpoints funcionales |
| **Validaciones** | ✅ Robustas |
| **Auditoría** | ✅ Inmutable |
| **Reportes** | ✅ Generados |
| **MVP Ready** | ✅ LISTO |

---

**Fecha:** 21 de Febrero 2026  
**RF:** 18  
**Versión:** 1.0  
**Estado:** ✅ PRODUCCIÓN

**¡Sistema de Comisiones Completamente Implementado!** 🚀
