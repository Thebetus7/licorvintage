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
        Schema::create('detalle_promos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('promocion_id');
            $table->timestamps();
        });

        Schema::table('ventas', function (Blueprint $table): void {
            $table->foreign('detalle_promo_id')->references('id')->on('detalle_promos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table): void {
            $table->dropForeign(['detalle_promo_id']);
        });

        Schema::dropIfExists('detalle_promos');
    }
};
