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
        // Table for login logs and resource accesses
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20); // 'Login', 'Acceso'
            $table->foreignId('user_id')->nullable()->constrained('user')->onDelete('set null');
            $table->text('descripcion'); // e.g., "Inicio de sesión exitoso", "Acceso a Productos"
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('estado', 20)->default('Exitoso'); // 'Exitoso', 'Fallido', 'Permitido', 'Denegado'
            $table->json('metadata')->nullable(); // Additional details like route, method, email, etc.
            $table->timestamp('created_at')->useCurrent();
        });

        // Table for tracking visit counts per route/page
        Schema::create('visita_paginas', function (Blueprint $table) {
            $table->id();
            $table->string('ruta', 150)->unique();
            $table->integer('contador')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visita_paginas');
        Schema::dropIfExists('bitacora');
    }
};
