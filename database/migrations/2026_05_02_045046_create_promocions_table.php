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
        Schema::create('promocions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_promo');
            $table->string('codigo_promo')->unique();
            $table->decimal('descuento', 12, 2);
            $table->string('tipo_descuento');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('detalle_promos', function (Blueprint $table): void {
            $table->foreign('promocion_id')->references('id')->on('promocions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_promos', function (Blueprint $table): void {
            $table->dropForeign(['promocion_id']);
        });

        Schema::dropIfExists('promocions');
    }
};
