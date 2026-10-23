<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            // Datos básicos
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 200);
            $table->enum('tipo', ['PRODUCTO', 'SERVICIO'])
                ->default('PRODUCTO');

            // Relaciones con catálogos
            $table->foreignId('grupo_id')
                ->nullable()
                ->constrained('grupo_productos')
                ->restrictOnDelete();

            $table->foreignId('marca_id')
                ->nullable()
                ->constrained('marcas')
                ->restrictOnDelete();

            $table->foreignId('unidad_medida_id')
                ->nullable()
                ->constrained('unidad_medidas')
                ->restrictOnDelete();

            // Información adicional
            $table->string('procedencia', 150)->nullable();
            $table->string('ubicacion', 150)->nullable();
            $table->string('caso_uso', 200)->nullable();

            // Precios y existencias
            $table->decimal('precio_compra', 12, 2)->default(0);
            $table->decimal('precio_venta', 12, 2)->default(0);
            $table->decimal('stock_actual', 12, 2)->default(0);
            $table->decimal('stock_minimo', 12, 2)->default(0);

            // Estado
            $table->boolean('estado')->default(true);
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
