<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedInteger('orden_menu')->default(999);
            $table->string('sucursal', 150)->default('Sistema Snack');
            $table->string('imagen')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['orden_menu', 'sucursal', 'imagen']);
        });
    }
};
