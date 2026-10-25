
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

// Página de bienvenida
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Rutas protegidas
Route::middleware(['auth', 'verified'])->group(function () {

    // PANEL PRINCIPAL
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

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

    // PEDIDOS DE MESA / PARA LLEVAR / YANGO
    Route::get('/ventas/pedidos', [PedidoMesaController::class, 'index'])->name('pedidos.index');
    Route::post('/ventas/pedidos', [PedidoMesaController::class, 'store'])->name('pedidos.store');
    Route::post('/ventas/pedidos/{pedido}/cobrar', [PedidoMesaController::class, 'cobrar'])->name('pedidos.cobrar');
    Route::patch('/ventas/mesas/{mesa}/liberar', [PedidoMesaController::class, 'liberarMesa'])->name('mesas.liberar');

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
