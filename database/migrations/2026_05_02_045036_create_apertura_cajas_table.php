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
            $table->decimal('monto_inicial', 12, 2);
            $table->decimal('monto_sistema', 12, 2);
            $table->decimal('monto_real', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->timestamp('tiempo_apertura');
            $table->timestamp('tiempo_cierre')->nullable();
            $table->string('estado');
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
        Schema::dropIfExists('apertura_cajas');
    }
};
