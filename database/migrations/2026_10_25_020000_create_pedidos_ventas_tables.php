<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('mesas', function(Blueprint $t) {
   $t->id(); $t->unsignedInteger('numero')->unique(); $t->unsignedSmallInteger('capacidad')->default(4);
   $t->string('piso',40)->default('PISO 1'); $t->string('estado',20)->default('LIBRE'); $t->timestamps();
  });
  Schema::create('pedidos', function(Blueprint $t) {
   $t->id(); $t->string('codigo',30)->unique(); $t->string('modalidad',20);
   $t->foreignId('mesa_id')->nullable()->constrained('mesas')->restrictOnDelete();
   $t->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
   $t->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
   $t->foreignId('caja_id')->nullable()->constrained('cajas')->restrictOnDelete();
   $t->string('nombre_cliente',160)->nullable();$t->text('nota_cocina')->nullable();
   $t->string('estado',20)->default('EN_COCINA');$t->string('metodo_pago',20)->nullable();
   $t->decimal('total',14,2);$t->timestamp('cobrado_at')->nullable();$t->timestamps();
  });
  Schema::create('pedido_items', function(Blueprint $t) {
   $t->id();$t->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
   $t->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
   $t->decimal('cantidad',12,2);$t->decimal('precio_unitario',12,2);$t->decimal('subtotal',14,2);$t->timestamps();
  });
  foreach ([1,2,3,4,5,6,8,9] as $numero) {
   \Illuminate\Support\Facades\DB::table('mesas')->insert(['numero'=>$numero,'capacidad'=>4,'piso'=>'PISO 1','estado'=>'LIBRE','created_at'=>now(),'updated_at'=>now()]);
  }
 }
 public function down():void {
  Schema::dropIfExists('pedido_items');Schema::dropIfExists('pedidos');Schema::dropIfExists('mesas');
 }
};
