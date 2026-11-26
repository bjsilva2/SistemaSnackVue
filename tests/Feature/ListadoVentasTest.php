<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('Listado de Ventas requiere el permiso Venta.listar', function () {
    $id=DB::table('roles')->where('nombre','Cajero')->value('id');
    $user=User::factory()->create(['rol_id'=>$id]);
    $this->actingAs($user)->get('/ventas/listado')->assertForbidden();

    DB::table('roles')->where('id',$id)->update([
        'permisos'=>json_encode(['Venta.listar']),
    ]);
    $this->actingAs($user)->get('/ventas/listado')->assertOk()
        ->assertInertia(fn (Assert $page)=>$page
            ->component('Ventas/ListadoVentas')
            ->has('ventas.data',0));
});

test('Listado de Ventas usa filtros validados', function () {
    $id=DB::table('roles')->where('nombre','Cajero')->value('id');
    DB::table('roles')->where('id',$id)->update([
        'permisos'=>json_encode(['Venta.listar']),
    ]);
    $user=User::factory()->create(['rol_id'=>$id]);
    $this->actingAs($user)->get('/ventas/listado?estado=INVALIDO')
        ->assertSessionHasErrors(['estado']);
});
