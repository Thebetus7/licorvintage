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
            $table->double('monto_pagado');
            $table->string('cod_descuento')->nullable();
            $table->double('monto_original');
            $table->double('monto_final');
            $table->integer('nro_cuotas');
            $table->string('tipo_pago');
            $table->foreignId('detalle_promo_id')->nullable();
            $table->foreignId('user_id');
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
