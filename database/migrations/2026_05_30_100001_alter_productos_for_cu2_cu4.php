<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->json('fotos')->nullable()->after('imagen');
            $table->string('codigo_qr')->nullable()->after('codigo_barra');
            $table->boolean('publicado')->default(true)->after('codigo_qr');
            $table->decimal('costo_promedio', 12, 2)->default(0)->after('costo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['fotos', 'codigo_qr', 'publicado', 'costo_promedio']);
        });
    }
};
