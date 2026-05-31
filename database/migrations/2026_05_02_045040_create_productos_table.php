<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->integer('mililitros');
            $table->decimal('costo', 12, 2)->default(0);
            $table->decimal('precio_venta', 12, 2)->default(0);
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->string('codigo_barra')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('detalle_ventas', function (Blueprint $table): void {
            $table->foreign('producto_id')->references('id')->on('productos')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table): void {
            $table->dropForeign(['producto_id']);
        });

        Schema::dropIfExists('productos');
    }
};
