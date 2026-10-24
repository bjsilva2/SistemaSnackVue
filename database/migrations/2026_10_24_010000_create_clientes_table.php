<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre_completo', 200);
            $table->string('ci_nit', 50)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('domicilio', 250)->nullable();
            $table->string('tipo_cliente', 50)->default('Normal');
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('clientes'); }
};
