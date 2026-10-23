<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('compra_recepciones', function (Blueprint $table) {
            $table->string('recibo_factura', 80)->nullable();
            $table->string('sucursal', 150)->default('Sistema Snack');
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('a_cuenta', 14, 2)->default(0);
            $table->decimal('saldo', 14, 2)->default(0);
            $table->string('tipo_pago', 30)->default('CONTADO');
            $table->unsignedInteger('dias_credito')->default(0);
            $table->string('metodo_pago', 30)->default('EFECTIVO');
            $table->decimal('efectivo', 14, 2)->default(0);
            $table->string('enviar_a_banco', 150)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 30)->default('RECIBIDO');
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::table('compra_recepcion_items', function (Blueprint $table) {
            $table->decimal('pu_desc', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('sub_desc', 12, 2)->default(0);
            $table->decimal('ganancia', 7, 2)->default(0);
            $table->decimal('pv', 12, 2)->default(0);
            $table->decimal('pv2', 12, 2)->default(0);
        });
    }

    public function down(): void {
        Schema::table('compra_recepcion_items', fn (Blueprint $t) => $t->dropColumn(['pu_desc','descuento','sub_desc','ganancia','pv','pv2']));
        Schema::table('compra_recepciones', function (Blueprint $t) {
            $t->dropConstrainedForeignId('solicitado_por');
            $t->dropColumn(['recibo_factura','sucursal','descuento','subtotal','a_cuenta','saldo','tipo_pago','dias_credito','metodo_pago','efectivo','enviar_a_banco','observaciones','estado']);
        });
    }
};
