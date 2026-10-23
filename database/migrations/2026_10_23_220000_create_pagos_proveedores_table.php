<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pagos_proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('compra_recepciones')->restrictOnDelete();
            $table->dateTime('fecha_pago');
            $table->decimal('monto', 14, 2);
            $table->string('metodo_pago', 30);
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('pagos_proveedores');
    }
};
