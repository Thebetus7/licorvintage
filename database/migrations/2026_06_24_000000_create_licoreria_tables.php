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
        // =========================================================================
        // 1. TABLAS MAESTRAS (Catálogos)
        // =========================================================================

        Schema::create('metodoPago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 100);
            $table->integer('cantidadesCuotas');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('producto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2);
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('proveedor', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('promo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->decimal('descuento', 10, 2);
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tiposSalida', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion', 100);
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // =========================================================================
        // 2. TABLAS DE NIVEL 1
        // =========================================================================

        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->unique()->constrained('producto')->onDelete('cascade');
            $table->integer('cantidad');
            $table->integer('min');
            $table->integer('max');
            $table->timestamps();
        });

        Schema::create('lote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('producto')->onDelete('cascade');
            $table->integer('cantidad');
            $table->date('fechaExpiracion');
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });

        Schema::create('compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedor')->onDelete('set null');
            $table->timestamp('fecha')->useCurrent();
            $table->decimal('total', 10, 2);
            $table->string('estado', 20)->default('Completada');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detallePromo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promo')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('producto')->onDelete('cascade');
            $table->integer('cantidad');
            $table->timestamps();
        });

        Schema::create('notaSalida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiposSalida_id')->constrained('tiposSalida')->onDelete('restrict');
            $table->timestamp('fecha')->useCurrent();
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // =========================================================================
        // 3. TABLAS DE NIVEL 2
        // =========================================================================

        Schema::create('aperturaCaja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->onDelete('restrict');
            $table->timestamp('fechaInicio')->useCurrent();
            $table->timestamp('fechaCierre')->nullable();
            $table->decimal('totalEfectivo', 10, 2)->default(0);
            $table->decimal('totalQR', 10, 2)->default(0);
            $table->decimal('totalTarjeta', 10, 2)->default(0);
            $table->decimal('totalSistema', 10, 2)->default(0);
            $table->decimal('cajaChica', 10, 2)->default(0);
            
            $table->decimal('realEfectivo', 10, 2)->nullable();
            $table->decimal('realQR', 10, 2)->nullable();
            $table->decimal('realTarjeta', 10, 2)->nullable();
            $table->decimal('realTotal', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->string('estado', 20)->default('Abierta');
            $table->timestamps();
        });

        Schema::create('venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->onDelete('restrict');
            $table->timestamp('fecha')->useCurrent();
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('totalOriginal', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('estado', 20)->default('Completada');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detalleCompra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compra')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('producto')->onDelete('restrict');
            $table->integer('cantidad');
            $table->decimal('precioCompra', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::create('salidaDetalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notaSalida_id')->constrained('notaSalida')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('producto')->onDelete('restrict');
            $table->integer('cantidad');
            $table->timestamps();
        });

        // =========================================================================
        // 4. TABLAS DE NIVEL 3
        // =========================================================================

        Schema::create('detalleVenta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('venta')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('producto')->onDelete('restrict');
            $table->integer('cantidad');
            $table->decimal('precioVenta', 10, 2);
            $table->decimal('subTotal', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('ventaPromo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('venta')->onDelete('cascade');
            $table->foreignId('promo_id')->constrained('promo')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('detalleCuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('venta')->onDelete('cascade');
            $table->foreignId('cuotas_id')->constrained('cuotas')->onDelete('restrict');
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->string('estado', 20)->default('Pendiente');
            $table->timestamps();
        });

        // =========================================================================
        // 5. TABLA DE TRANSACCIONES (Nivel 4)
        // =========================================================================

        Schema::create('transaccion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->nullable()->constrained('venta')->onDelete('cascade');
            $table->foreignId('aperturaCaja_id')->constrained('aperturaCaja')->onDelete('restrict');
            $table->foreignId('metodoPago_id')->constrained('metodoPago')->onDelete('restrict');
            $table->foreignId('detalleCuotas_id')->nullable()->constrained('detalleCuotas')->onDelete('set null');
            $table->decimal('monto', 10, 2);
            $table->string('estado', 20)->default('Completada'); // QR or other payment statuses
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaccion');
        Schema::dropIfExists('detalleCuotas');
        Schema::dropIfExists('ventaPromo');
        Schema::dropIfExists('detalleVenta');
        Schema::dropIfExists('salidaDetalle');
        Schema::dropIfExists('detalleCompra');
        Schema::dropIfExists('venta');
        Schema::dropIfExists('aperturaCaja');
        Schema::dropIfExists('notaSalida');
        Schema::dropIfExists('detallePromo');
        Schema::dropIfExists('compra');
        Schema::dropIfExists('lote');
        Schema::dropIfExists('stock');
        Schema::dropIfExists('tiposSalida');
        Schema::dropIfExists('promo');
        Schema::dropIfExists('proveedor');
        Schema::dropIfExists('producto');
        Schema::dropIfExists('cuotas');
        Schema::dropIfExists('metodoPago');
    }
};
