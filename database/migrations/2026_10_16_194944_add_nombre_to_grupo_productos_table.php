
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupo_productos', function (Blueprint $table) {
            if (!Schema::hasColumn('grupo_productos', 'nombre')) {
                $table->string('nombre', 150)->nullable();
            }

            if (!Schema::hasColumn('grupo_productos', 'estado')) {
                $table->boolean('estado')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('grupo_productos', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });
    }
};
