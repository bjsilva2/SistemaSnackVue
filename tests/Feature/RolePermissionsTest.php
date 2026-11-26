<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('una cuenta sin permisos no puede abrir modulos ni ejecutar operaciones', function () {
    $rol=DB::table('roles')->where('nombre','Cajero')->value('id');
    $usuario=User::factory()->create(['rol_id'=>$rol]);

    $this->actingAs($usuario)->get('/dashboard')->assertForbidden();
    $this->actingAs($usuario)->get('/ventas/cajas')->assertForbidden();
    $this->actingAs($usuario)->post('/ventas/cajas',[
        'sucursal'=>'Sistema Snack','monto_inicial'=>100,
    ])->assertForbidden();

    expect(DB::table('cajas')->count())->toBe(0);
});

test('listar cajas y abrir cajas son permisos separados', function () {
    $rol=DB::table('roles')->where('nombre','Cajero')->value('id');
    DB::table('roles')->where('id',$rol)->update([
        'permisos'=>json_encode(['Caja.listar']),
    ]);
    $usuario=User::factory()->create(['rol_id'=>$rol]);

    $this->actingAs($usuario)->get('/ventas/cajas')->assertOk();
    $this->actingAs($usuario)->post('/ventas/cajas',[
        'sucursal'=>'Sistema Snack','monto_inicial'=>100,
    ])->assertForbidden();

    DB::table('roles')->where('id',$rol)->update([
        'permisos'=>json_encode(['Caja.listar','Caja.crear']),
    ]);
    $this->actingAs($usuario)->post('/ventas/cajas',[
        'sucursal'=>'Sistema Snack','monto_inicial'=>100,
    ])->assertRedirect();

    expect(DB::table('cajas')->where('usuario_id',$usuario->id)->count())->toBe(1);
});

test('un cocinero con permiso puede ver cocina pero no editar usuarios', function () {
    $rol=DB::table('roles')->where('nombre','Cocinero')->value('id');
    DB::table('roles')->where('id',$rol)->update(['permisos'=>json_encode(['Cocina.listar'])]);
    $usuario=User::factory()->create(['rol_id'=>$rol]);

    $this->actingAs($usuario)->get('/ventas/cocina')->assertOk();
    $this->actingAs($usuario)->get('/ajustes/usuarios')->assertForbidden();
    $this->actingAs($usuario)->get('/compras/ordenes')->assertForbidden();
});

test('un usuario no administrador no puede alterar permisos aunque la casilla este marcada', function () {
    $rol=DB::table('roles')->where('nombre','Cajero')->value('id');
    DB::table('roles')->where('id',$rol)->update([
        'permisos'=>json_encode(['Roles.asignar_permisos']),
    ]);
    $usuario=User::factory()->create(['rol_id'=>$rol]);
    $otroRol=DB::table('roles')->where('nombre','Cocinero')->value('id');

    $this->actingAs($usuario)->put('/ajustes/roles/'.$otroRol.'/permisos',[
        'permisos'=>['Caja.crear'],
    ])->assertForbidden();
    expect(json_decode(DB::table('roles')->where('id',$otroRol)->value('permisos'),true))->toBe([]);
});
