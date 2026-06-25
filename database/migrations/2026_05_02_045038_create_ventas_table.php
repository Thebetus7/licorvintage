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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->decimal('monto_pagado', 12, 2);
            $table->string('cod_descuento')->nullable();
            $table->decimal('monto_original', 12, 2);
            $table->decimal('monto_final', 12, 2);
            $table->integer('nro_cuotas');
            $table->string('tipo_pago');
            $table->foreignId('detalle_promo_id')->nullable();
            $table->foreignId('cliente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('promocion_id')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
