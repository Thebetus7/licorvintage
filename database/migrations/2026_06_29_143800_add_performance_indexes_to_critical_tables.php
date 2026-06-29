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
        // 1. Tablas de Seguridad y Auditoría
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('user_id');
            $table->index('event_type');
        });

        // 2. Tablas de Productos y Stock
        Schema::table('productos', function (Blueprint $table) {
            $table->index('nombre');
            $table->index('deleted_at');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->index('producto_id');
            $table->index('deleted_at');
        });

        // 3. Tablas de Ventas
        Schema::table('ventas', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('created_at');
            $table->index('deleted_at');
        });

        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->index('venta_id');
            $table->index('producto_id');
            $table->index('deleted_at');
        });

        // 4. Tablas de Compras
        Schema::table('compras', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('created_at');
            $table->index('deleted_at');
        });

        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->index('compra_id');
            $table->index('producto_id');
            $table->index('deleted_at');
        });

        // 5. Tablas de Caja
        Schema::table('apertura_cajas', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('deleted_at');
        });

        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->index('apertura_caja_id');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimiento_cajas', function (Blueprint $table) {
            $table->dropIndex(['apertura_caja_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('apertura_cajas', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('detalle_compras', function (Blueprint $table) {
            $table->dropIndex(['compra_id']);
            $table->dropIndex(['producto_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropIndex(['venta_id']);
            $table->dropIndex(['producto_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex(['producto_id']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['nombre']);
            $table->dropIndex(['deleted_at']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['event_type']);
        });
    }
};
