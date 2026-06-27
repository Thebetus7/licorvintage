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
        Schema::table('apertura_cajas', function (Blueprint $table) {
            $table->foreignId('opened_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->json('totales_sistema')->nullable();
            $table->json('totales_caja')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('apertura_cajas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opened_by');
            $table->dropColumn(['totales_sistema', 'totales_caja']);
        });
    }
};
