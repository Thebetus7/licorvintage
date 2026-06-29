<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimiento_inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->enum('tipo', [
                'ingreso_compra',
                'ingreso_devolucion',
                'ingreso_ajuste',
                'ingreso_inicial',
                'salida_venta',
                'salida_merma',
                'salida_ajuste',
            ]);
            $table->integer('cantidad');
            $table->decimal('costo_unitario', 12, 2);
            $table->integer('saldo_cantidad');
            $table->decimal('saldo_costo_promedio', 12, 2);
            $table->nullableMorphs('referencia');
            $table->string('motivo')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimiento_inventarios');
    }
};
