
<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\GrupoProductoController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CompraRecepcionController;

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

    // PROVEEDORES
    Route::get('/compras/proveedores', [ProveedorController::class, 'index'])
        ->name('proveedores.index');
    
    Route::post('/compras/proveedores', [ProveedorController::class, 'store'])
        ->name('proveedores.store');
    
    Route::put('/compras/proveedores/{proveedor}', [ProveedorController::class, 'update'])
        ->name('proveedores.update');
    
    Route::patch('/compras/proveedores/{proveedor}/estado', [ProveedorController::class, 'cambiarEstado'])
        ->name('proveedores.estado');


    // LISTADO DE ÓRDENES Y RECEPCIÓN DE COMPRAS
    Route::get('/compras/ordenes', [CompraRecepcionController::class, 'index'])->name('compras.ordenes.index');
    Route::get('/compras/recepciones', [CompraRecepcionController::class, 'index'])->name('compras.recepciones.index');
    Route::post('/compras/recepciones', [CompraRecepcionController::class, 'store'])->name('compras.recepciones.store');

});

// Rutas de configuración y autenticación
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
