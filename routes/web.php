
<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\GrupoProductoController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CompraRecepcionController;
use App\Http\Controllers\PagoProveedorController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\PedidoMesaController;
use App\Http\Controllers\MonitorCocinaController;
use App\Http\Controllers\ReporteVentasController;
use App\Http\Controllers\ReporteComprasController;
use App\Http\Controllers\ReporteCajasController;
use App\Http\Controllers\ReporteStockController;
use App\Http\Controllers\InformeMensualController;
use App\Http\Controllers\AjustesUsuarioController;
use App\Http\Controllers\AjustesRolController;
use App\Http\Controllers\DashboardController;

// Página de bienvenida
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Rutas protegidas
Route::middleware(['auth', 'verified', 'module.permission'])->group(function () {

    // ADMINISTRAR USUARIOS
    Route::get('/ajustes/usuarios', [AjustesUsuarioController::class, 'index'])->name('ajustes.usuarios');
    Route::post('/ajustes/usuarios', [AjustesUsuarioController::class, 'store'])->name('ajustes.usuarios.store');
    Route::put('/ajustes/usuarios/{usuario}', [AjustesUsuarioController::class, 'update'])->name('ajustes.usuarios.update');

    // ADMINISTRAR ROLES Y PERMISOS
    Route::get('/ajustes/roles', [AjustesRolController::class, 'index'])->name('ajustes.roles');
    Route::post('/ajustes/roles', [AjustesRolController::class, 'store'])->name('ajustes.roles.store');
    Route::put('/ajustes/roles/{rol}', [AjustesRolController::class, 'update'])->name('ajustes.roles.update');
    Route::put('/ajustes/roles/{rol}/permisos', [AjustesRolController::class, 'permisos'])->name('ajustes.roles.permisos');
    Route::delete('/ajustes/roles/{rol}', [AjustesRolController::class, 'destroy'])->name('ajustes.roles.destroy');

    // PANEL PRINCIPAL
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // GRUPOS DE PRODUCTOS
    Route::get('/catalogos/grupos', [GrupoProductoController::class, 'index'])
        ->name('grupos.index');

    Route::post('/catalogos/grupos', [GrupoProductoController::class, 'store'])
        ->name('grupos.store');

    Route::put('/catalogos/grupos/{grupo}', [GrupoProductoController::class, 'update'])
        ->name('grupos.update');

    Route::patch('/catalogos/grupos/{grupo}/estado', [GrupoProductoController::class, 'cambiarEstado'])
        ->name('grupos.estado');

    // MARCAS DE INSUMOS
    Route::get('/catalogos/marcas', [MarcaController::class, 'index'])
        ->name('marcas.index');

    Route::post('/catalogos/marcas', [MarcaController::class, 'store'])
        ->name('marcas.store');

    Route::put('/catalogos/marcas/{marca}', [MarcaController::class, 'update'])
        ->name('marcas.update');

    Route::patch('/catalogos/marcas/{marca}/estado', [MarcaController::class, 'cambiarEstado'])
        ->name('marcas.estado');
    
    // UNIDADES DE MEDIDA
    Route::get('/catalogos/unidades', [UnidadMedidaController::class, 'index'])
        ->name('unidades.index');

    Route::post('/catalogos/unidades', [UnidadMedidaController::class, 'store'])
        ->name('unidades.store');

    Route::put('/catalogos/unidades/{unidad}', [UnidadMedidaController::class, 'update'])
        ->name('unidades.update');

    Route::patch('/catalogos/unidades/{unidad}/estado', [UnidadMedidaController::class, 'cambiarEstado'])
        ->name('unidades.estado');
    
    // PRODUCTOS Y SERVICIOS

    Route::get('/catalogos/productos', [
        ProductoController::class, 'index'
    ])->name('productos.index');

    Route::post('/catalogos/productos', [
        ProductoController::class, 'store'
    ])->name('productos.store');

    Route::put('/catalogos/productos/{producto}', [
        ProductoController::class, 'update'
    ])->name('productos.update');

    Route::patch('/catalogos/productos/{producto}/estado', [
        ProductoController::class, 'cambiarEstado'
    ])->name('productos.estado');

    // REPORTE GENERAL DE VENTAS
    Route::get('/reportes/ventas', [ReporteVentasController::class, 'index'])->name('reportes.ventas');
    Route::get('/reportes/ventas/exportar', [ReporteVentasController::class, 'exportar'])->name('reportes.ventas.exportar');

    // REPORTE GENERAL DE COMPRAS
    Route::get('/reportes/compras', [ReporteComprasController::class, 'index'])->name('reportes.compras');
    Route::get('/reportes/compras/exportar', [ReporteComprasController::class, 'exportar'])->name('reportes.compras.exportar');

    // REPORTE GENERAL DE CAJAS
    Route::get('/reportes/cajas', [ReporteCajasController::class, 'index'])->name('reportes.cajas');
    Route::get('/reportes/cajas/exportar', [ReporteCajasController::class, 'exportar'])->name('reportes.cajas.exportar');

    // REPORTE GENERAL DE STOCK
    Route::get('/reportes/stock', [ReporteStockController::class, 'index'])->name('reportes.stock');
    Route::get('/reportes/stock/exportar', [ReporteStockController::class, 'exportar'])->name('reportes.stock.exportar');

    // INFORME MENSUAL Y GESTIÓN
    Route::get('/reportes/mensual', [InformeMensualController::class, 'index'])->name('reportes.mensual');

    // PEDIDOS DE MESA / PARA LLEVAR / YANGO
    Route::get('/ventas/pedidos', [PedidoMesaController::class, 'index'])->name('pedidos.index');
    Route::post('/ventas/pedidos', [PedidoMesaController::class, 'store'])->name('pedidos.store');
    Route::post('/ventas/pedidos/{pedido}/cobrar', [PedidoMesaController::class, 'cobrar'])->name('pedidos.cobrar');
    Route::patch('/ventas/mesas/{mesa}/liberar', [PedidoMesaController::class, 'liberarMesa'])->name('mesas.liberar');

    // MONITOR COCINA
    Route::get('/ventas/cocina', [MonitorCocinaController::class, 'index'])->name('cocina.index');
    Route::patch('/ventas/cocina/{pedido}/estado', [MonitorCocinaController::class, 'cambiarEstado'])->name('cocina.estado');

    // ADMINISTRAR CAJAS
    Route::get('/ventas/cajas', [CajaController::class, 'index'])->name('cajas.index');
    Route::post('/ventas/cajas', [CajaController::class, 'store'])->name('cajas.store');
    Route::patch('/ventas/cajas/{caja}/cerrar', [CajaController::class, 'cerrar'])->name('cajas.cerrar');

    // CLIENTES
    Route::get('/ventas/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::post('/ventas/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    Route::put('/ventas/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::patch('/ventas/clientes/{cliente}/estado', [ClienteController::class, 'cambiarEstado'])->name('clientes.estado');

    // PROVEEDORES
    Route::get('/compras/proveedores', [ProveedorController::class, 'index'])
        ->name('proveedores.index');
    
    Route::post('/compras/proveedores', [ProveedorController::class, 'store'])
        ->name('proveedores.store');
    
    Route::put('/compras/proveedores/{proveedor}', [ProveedorController::class, 'update'])
        ->name('proveedores.update');
    
    Route::patch('/compras/proveedores/{proveedor}/estado', [ProveedorController::class, 'cambiarEstado'])
        ->name('proveedores.estado');


    // PAGOS A PROVEEDORES
    Route::get('/compras/pagos', [PagoProveedorController::class, 'index'])->name('compras.pagos.index');
    Route::post('/compras/pagos', [PagoProveedorController::class, 'store'])->name('compras.pagos.store');

    // LISTADO DE ÓRDENES Y RECEPCIÓN DE COMPRAS
    Route::get('/compras/ordenes', [CompraRecepcionController::class, 'index'])->name('compras.ordenes.index');
    Route::get('/compras/recepciones', [CompraRecepcionController::class, 'index'])->name('compras.recepciones.index');
    Route::post('/compras/recepciones', [CompraRecepcionController::class, 'store'])->name('compras.recepciones.store');

});

// Rutas de configuración y autenticación
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
