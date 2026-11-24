<?php

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function usuarioParaCobroDirecto(): User
{
    $rol=DB::table('roles')->where('nombre','Cajero')->value('id');
    DB::table('roles')->where('id',$rol)->update([
        'permisos'=>json_encode(['Venta.crear','Venta.cobrar']),
    ]);
    return User::factory()->create(['rol_id'=>$rol]);
}

function productoParaCobroDirecto(): Producto
{
    return Producto::create([
        'codigo'=>'PRD900001','nombre'=>'Producto de prueba cobro directo',
        'tipo'=>'PRODUCTO','precio_compra'=>3.00,'precio_venta'=>7.00,
        'stock_actual'=>10,'stock_minimo'=>2,'estado'=>true,
        'orden_menu'=>0,
    ]);
}

function datosCobroDirecto(Producto $producto): array
{
    return [
        'modalidad'=>'LLEVAR','nombre_cliente'=>'Cliente prueba',
        'metodo_pago'=>'EFECTIVO',
        'items'=>[['producto_id'=>$producto->id,'cantidad'=>2]],
    ];
}

test('no registra ni cobra para llevar sin caja abierta', function () {
    $usuario=usuarioParaCobroDirecto();
    $producto=productoParaCobroDirecto();
    $this->actingAs($usuario)->post('/ventas/pedidos/cobrar-directo',datosCobroDirecto($producto))
        ->assertSessionHasErrors('caja');
    expect(DB::table('pedidos')->count())->toBe(0);
    expect((float)$producto->fresh()->stock_actual)->toBe(10.0);
});

test('el cobro directo registra pedido descontando stock y aumentando caja', function () {
    $usuario=usuarioParaCobroDirecto();
    $producto=productoParaCobroDirecto();
    $cajaId=DB::table('cajas')->insertGetId([
        'usuario_id'=>$usuario->id,'sucursal'=>'Sistema Snack','monto_inicial'=>100,
        'saldo'=>100,'total_ventas'=>0,'estado'=>'ABIERTO',
        'fecha_apertura'=>now(),'created_at'=>now(),'updated_at'=>now(),
    ]);
    $this->actingAs($usuario)->post('/ventas/pedidos/cobrar-directo',datosCobroDirecto($producto))
        ->assertRedirect()->assertSessionHasNoErrors();
    $pedido=DB::table('pedidos')->first();
    expect($pedido->estado)->toBe('COBRADO')
        ->and($pedido->modalidad)->toBe('LLEVAR')
        ->and($pedido->caja_id)->toBe($cajaId)
        ->and((float)$pedido->total)->toBe(14.0)
        ->and((float)$producto->fresh()->stock_actual)->toBe(8.0);
    $caja=DB::table('cajas')->where('id',$cajaId)->first();
    expect((float)$caja->total_ventas)->toBe(14.0)
        ->and((float)$caja->saldo)->toBe(114.0);
    expect(DB::table('pedido_items')->where('pedido_id',$pedido->id)->count())->toBe(1);
});

test('si falta stock no registra pedido ni modifica la caja', function () {
    $usuario=usuarioParaCobroDirecto();
    $producto=productoParaCobroDirecto();
    DB::table('cajas')->insert([
        'usuario_id'=>$usuario->id,'monto_inicial'=>100,'saldo'=>100,
        'estado'=>'ABIERTO','fecha_apertura'=>now(),'created_at'=>now(),'updated_at'=>now(),
    ]);
    $data=datosCobroDirecto($producto);$data['items'][0]['cantidad']=11;
    $this->actingAs($usuario)->post('/ventas/pedidos/cobrar-directo',$data)
        ->assertSessionHasErrors('stock');
    expect(DB::table('pedidos')->count())->toBe(0)
        ->and((float)$producto->fresh()->stock_actual)->toBe(10.0)
        ->and((float)DB::table('cajas')->value('saldo'))->toBe(100.0);
});

test('cobro directo exige permiso de crear y de cobrar', function () {
    $usuario=usuarioParaCobroDirecto();
    $producto=productoParaCobroDirecto();
    DB::table('roles')->where('id',$usuario->rol_id)->update([
        'permisos'=>json_encode(['Venta.crear']),
    ]);
    $this->actingAs($usuario)->post('/ventas/pedidos/cobrar-directo',datosCobroDirecto($producto))
        ->assertForbidden();
    DB::table('roles')->where('id',$usuario->rol_id)->update([
        'permisos'=>json_encode(['Venta.cobrar']),
    ]);
    $this->actingAs($usuario)->post('/ventas/pedidos/cobrar-directo',datosCobroDirecto($producto))
        ->assertForbidden();
});
