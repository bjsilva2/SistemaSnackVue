<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('compra_recepciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });
        Schema::create('compra_recepcion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('compra_recepciones')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->decimal('cantidad', 12, 2);
            $table->decimal('precio_compra', 12, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('compra_recepcion_items');
        Schema::dropIfExists('compra_recepciones');
    }
};
