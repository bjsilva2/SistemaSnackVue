<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('sucursal', 150)->default('Sistema Snack');
            $table->decimal('monto_inicial', 14, 2);
            $table->decimal('ingresos', 14, 2)->default(0);
            $table->decimal('egresos', 14, 2)->default(0);
            $table->decimal('total_ventas', 14, 2)->default(0);
            $table->decimal('saldo', 14, 2);
            $table->timestamp('fecha_apertura');
            $table->timestamp('fecha_cierre')->nullable();
            $table->string('estado', 15)->default('ABIERTO');
            $table->timestamps();
            $table->index(['usuario_id', 'estado']);
        });
    }
    public function down(): void { Schema::dropIfExists('cajas'); }
};
