<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\StallController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\VendedorController;
use App\Http\Controllers\StallScheduleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TestTokenController;
use App\Http\Controllers\CommissionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========== TESTING ENDPOINTS (solo para desarrollo) ==========
Route::get('/test-token', [TestTokenController::class, 'generateTestToken']);

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas de recuperación de contraseña
Route::post('/send-otp', [PasswordResetController::class, 'sendOtp']);
Route::post('/verify-otp', [PasswordResetController::class, 'verifyOtp']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Ruta para actualizar datos de usuario
Route::middleware('auth:sanctum')->put('/user/update', [UserController::class, 'update']);

// Ruta para convertirse en vendedor (clientes solamente)
Route::middleware(['auth:sanctum', 'role:client'])->post('/convertirse-vendedor', [VendedorController::class, 'convertirseAVendedor']);

// ========== RF6: PUESTOS Y MENÚS ==========

// VENDEDOR: Gestión de su puesto
Route::middleware(['auth:sanctum', 'role:vendor'])->group(function () {
    // Puesto
    Route::get('/mis-puesto', [StallController::class, 'obtenerMiPuesto']);
    Route::post('/mis-puesto/generar-qr', [StallController::class, 'generarQr']);
    
    // ========== RF10: HORARIOS Y PAUSAS ==========
    Route::get('/mis-puesto/horarios', [StallScheduleController::class, 'obtenerHorarios']);
    Route::put('/mis-puesto/horarios', [StallScheduleController::class, 'actualizarHorarios']);
    Route::post('/mis-puesto/pausas', [StallScheduleController::class, 'crearPausa']);
    Route::get('/mis-puesto/pausas', [StallScheduleController::class, 'listarPausas']);
    Route::patch('/mis-puesto/pausas/{id}', [StallScheduleController::class, 'actualizarPausa']);
    Route::delete('/mis-puesto/pausas/{id}', [StallScheduleController::class, 'eliminarPausa']);
    
    // ========== RF13: TIEMPO ESTIMADO DE ENTREGA ==========
    Route::put('/mis-puesto/tiempos-preparacion', [StallScheduleController::class, 'configurarTiemposPreparacion']);
    
    // Productos (RF7)
    Route::get('/mis-productos', [MenuController::class, 'obtenerMisProductos']);
    // RF9: Descargar menú offline (solo productos activos)
    Route::get('/mis-productos/offline', [MenuController::class, 'descargarMenuOffline']);
    Route::post('/mis-productos', [MenuController::class, 'crearProducto']);
    Route::patch('/mis-productos/{id}', [MenuController::class, 'actualizarProducto']);
    
    // Cremas/Acompañamientos (RF8)
    Route::get('/mis-cremas', [MenuController::class, 'obtenerCremas']);
    Route::post('/mis-cremas', [MenuController::class, 'crearCrema']);
    Route::patch('/mis-cremas/{id}', [MenuController::class, 'actualizarCrema']);
    Route::delete('/mis-cremas/{id}', [MenuController::class, 'eliminarCrema']);
    
    // Asociar cremas a productos
    Route::post('/mis-productos/{productoId}/cremas', [MenuController::class, 'asociarCremaAProducto']);
    Route::delete('/mis-productos/{productoId}/cremas/{cremaId}', [MenuController::class, 'desasociarCremaDeProducto']);
    Route::get('/mis-productos/{productoId}/cremas', [MenuController::class, 'obtenerCremasDeProducto']);
});

// CLIENTE: Ver menú público (sin autenticación)
Route::get('/menu/{stallId}', [MenuController::class, 'verMenu']);

// ========== RF11, RF12, RF17: PEDIDOS Y PAGOS ==========
Route::middleware(['auth:sanctum', 'role:client'])->group(function () {
    // Crear pedido SIN pago (RF11 - Paso 1)
    Route::post('/pedidos', [OrderController::class, 'crearPedido']);
    
    // Pagar pedido (RF11 - Paso 2)
    Route::post('/pedidos/{id}/pagar', [OrderController::class, 'pagarPedido']);
    
    // Ver detalle de pedido (RF12)
    Route::get('/pedidos/{id}', [OrderController::class, 'obtenerPedido']);
    
    // Listar mis pedidos (RF12)
    Route::get('/pedidos', [OrderController::class, 'listarPedidosCliente']);
    
    // ========== RF15: CANCELAR PEDIDO ==========
    Route::patch('/pedidos/{id}/cancelar', [OrderController::class, 'cancelarPedido']);
    
    // ========== RF16: CAMBIAR DIRECCIÓN ==========
    Route::patch('/pedidos/{id}/cambiar-direccion', [OrderController::class, 'cambiarDireccion']);
});

// Vendedor: Ver sus pedidos activos (RF17)
Route::middleware(['auth:sanctum', 'role:vendor'])->group(function () {
    Route::get('/mis-pedidos', [OrderController::class, 'listarMisPedidos']);
    
    // ========== RF14: ESTADOS DEL PEDIDO ==========
    Route::patch('/pedidos/{id}/cambiar-estado', [OrderController::class, 'cambiarEstado']);
});

// ========== Rutas existentes ==========
Route::middleware(['auth:sanctum', 'role:vendor'])->group(function () {
    Route::post('/stalls', [StallController::class, 'store']);
    Route::get('/stalls/{stall}/qr', [StallController::class, 'qr']);
});

// ========== RF18: COMISIONES ==========
Route::middleware(['auth:sanctum'])->group(function () {
    // Calcular comisión de un pedido
    Route::post('/comisiones/calcular', [CommissionController::class, 'calcularComision']);
    
    // Obtener comisión de un pedido
    Route::get('/comisiones/pedido/{order_id}', [CommissionController::class, 'obtenerComisionPedido']);
    
    // Solo admin
    Route::middleware(['role:admin'])->group(function () {
        // Gestión de reglas
        Route::get('/comisiones/reglas', [CommissionController::class, 'listarReglas']);
        Route::post('/comisiones/reglas', [CommissionController::class, 'crearRegla']);
        Route::put('/comisiones/reglas/{rule_id}', [CommissionController::class, 'actualizarRegla']);
        Route::delete('/comisiones/reglas/{rule_id}', [CommissionController::class, 'eliminarRegla']);
        
        // Reportes
        Route::get('/comisiones/reporte', [CommissionController::class, 'reporteComisiones']);
        Route::get('/comisiones/auditoria', [CommissionController::class, 'auditoria']);

        // RF20: Transferir comisiones pendientes a vendedores
        Route::post('/comisiones/transferir', [CommissionController::class, 'transferirComisiones']);
    });
});

// ========== RF21: COMPROBANTES ELECTRÓNICOS ==========
Route::middleware(['auth:sanctum'])->group(function () {
    // Cliente puede solicitar generación manual de comprobante para su pedido
    Route::post('/comprobantes/generar/{order_id}', [\App\Http\Controllers\InvoiceController::class, 'generateForOrder']);

    // Descargar comprobantes
    Route::get('/comprobantes/{invoice_id}/pdf', [\App\Http\Controllers\InvoiceController::class, 'downloadPdf']);
    Route::get('/comprobantes/{invoice_id}/xml', [\App\Http\Controllers\InvoiceController::class, 'downloadXml']);

    // Reintentar generación (admin)
    Route::post('/comprobantes/{invoice_id}/reintentar', [\App\Http\Controllers\InvoiceController::class, 'resend']);
    // Listar comprobantes (clientes listan los suyos; admin lista todo)
    Route::get('/comprobantes', [\App\Http\Controllers\InvoiceController::class, 'index']);
    Route::get('/comprobantes/{invoice_id}', [\App\Http\Controllers\InvoiceController::class, 'show']);
});

// ========== RF4: DASHBOARD ==========
Route::middleware(['auth:sanctum'])->get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);
