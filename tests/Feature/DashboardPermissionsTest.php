<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('dashboard filtra las secciones según los permisos del rol', function () {
    $rol = DB::table('roles')->where('nombre', 'Cajero')->value('id');
    DB::table('roles')->where('id', $rol)->update([
        'permisos' => json_encode(['Dashboard.ver', 'Dashboard.ProductosStockCero']),
    ]);
    $usuario = User::factory()->create(['rol_id' => $rol]);

    $this->actingAs($usuario)->get('/dashboard')->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('visibilidad.stockCero', true)
            ->where('visibilidad.stockMinimo', false)
            ->where('visibilidad.masVendidos', false)
            ->has('stockMinimo', 0)
            ->has('masVendidos', 0));
});

test('permisos de dashboard no conceden acceso a otros módulos', function () {
    $rol = DB::table('roles')->where('nombre', 'Cocinero')->value('id');
    DB::table('roles')->where('id', $rol)->update([
        'permisos' => json_encode(['Dashboard.ver', 'Dashboard.Productos+Vendidos']),
    ]);
    $usuario = User::factory()->create(['rol_id' => $rol]);

    $this->actingAs($usuario)->get('/dashboard')->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('visibilidad.masVendidos', true)
            ->where('visibilidad.stockCero', false)
            ->where('visibilidad.stockMinimo', false));
    $this->actingAs($usuario)->get('/catalogos/productos')->assertForbidden();
});

test('sin permiso dashboard ver se deniega el acceso completo', function () {
    $rol = DB::table('roles')->where('nombre', 'Cajero')->value('id');
    DB::table('roles')->where('id', $rol)->update([
        'permisos' => json_encode(['Dashboard.ProductosStockMin']),
    ]);
    $usuario = User::factory()->create(['rol_id' => $rol]);
    $this->actingAs($usuario)->get('/dashboard')->assertForbidden();
});
