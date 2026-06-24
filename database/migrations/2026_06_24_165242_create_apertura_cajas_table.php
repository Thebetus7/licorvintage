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
        Schema::create('apertura_cajas', function (Blueprint $table) {
            $table->id();
            $table->double('monto_inicial');
            $table->double('monto_sistema');
            $table->double('monto_real');
            $table->double('diferencia');
            $table->timestamp('tiempo_apertura');
            $table->timestamp('tiempo_cierre');
            $table->string('estado');
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
        Schema::dropIfExists('apertura_cajas');
    }
};
