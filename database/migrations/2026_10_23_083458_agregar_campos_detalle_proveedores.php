<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('tipo_proveedor', 20)
                ->default('Natural');

            $table->string('procedencia', 20)
                ->default('NACIONAL');

            $table->string('contacto', 150)
                ->nullable();

            $table->string('celular_contacto', 30)
                ->nullable();

            $table->string('nombre_cheque', 200)
                ->nullable();

            $table->string('nombre_lcv', 200)
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_proveedor',
                'procedencia',
                'contacto',
                'celular_contacto',
                'nombre_cheque',
                'nombre_lcv',
            ]);
        });
    }
};
