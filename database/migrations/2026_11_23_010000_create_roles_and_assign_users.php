<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('roles', function(Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->jsonb('permisos')->default('[]');
            $table->timestamps();
        });
        Schema::table('users', function(Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->constrained('roles')->nullOnDelete();
        });
        foreach (['Administrador','Cajero','Mesero','Cocinero','Ventas'] as $nombre) {
            DB::table('roles')->insert(['nombre'=>$nombre,
                'permisos'=>json_encode($nombre==='Administrador'?['*']:[]),
                'created_at'=>now(),'updated_at'=>now()]);
        }
        // Asignar administrador solo a la cuenta existente más antigua, sin modificar contraseñas.
        $primero = DB::table('users')->orderBy('id')->value('id');
        if ($primero) DB::table('users')->where('id',$primero)->update([
            'rol_id'=>DB::table('roles')->where('nombre','Administrador')->value('id'),
        ]);
    }
    public function down(): void {
        Schema::table('users', fn(Blueprint $table)=>$table->dropConstrainedForeignId('rol_id'));
        Schema::dropIfExists('roles');
    }
};
