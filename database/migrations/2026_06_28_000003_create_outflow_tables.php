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
        Schema::create('tipo_salidas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        Schema::create('nota_salidas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_nota')->nullable()->unique();
            $table->foreignId('tipo_salida_id')->constrained('tipo_salidas')->restrictOnDelete();
            $table->dateTime('fecha');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detalle_salidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_salida_id')->constrained('nota_salidas')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->onDelete('set null');
            $table->integer('cantidad');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_salidas');
        Schema::dropIfExists('nota_salidas');
        Schema::dropIfExists('tipo_salidas');
    }
};
