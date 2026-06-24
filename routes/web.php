<?php

use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClienteProductoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use App\Models\Producto;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'productos' => Producto::query()
            ->where('estado', true)
            ->with('stock')
            ->orderBy('nombre')
            ->take(6)
            ->get(),
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        if (auth()->user()->hasRole('cliente')) {
            return redirect()->route('cliente.productos');
        }

        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::middleware('role:propietario|vendedor')->group(function () {
        Route::resource('productos', ProductoController::class)->except(['create', 'show', 'edit']);
        Route::resource('compras', CompraController::class)->except(['create', 'show', 'edit']);
        Route::resource('proveedores', ProveedorController::class)->except(['create', 'show', 'edit']);
        Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
        Route::post('/caja/open', [CajaController::class, 'open'])->name('caja.open');
        Route::put('/caja/{caja}/close', [CajaController::class, 'close'])->name('caja.close');
        Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    });

    Route::middleware('role:propietario')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except(['create', 'show', 'edit']);

        Route::prefix('inventario')->name('inventario.')->group(function () {
            Route::get('/', [InventarioController::class, 'index'])->name('index');
            Route::get('/movimientos', [InventarioController::class, 'movimientos'])->name('movimientos');
            Route::get('/kardex', [InventarioController::class, 'kardex'])->name('kardex');
            Route::get('/valorizacion', [InventarioController::class, 'valorizacion'])->name('valorizacion');
            Route::get('/conteo', [InventarioController::class, 'conteo'])->name('conteo');
            Route::post('/conteo', [InventarioController::class, 'guardarConteo'])->name('conteo.store');
            Route::post('/ingreso', [InventarioController::class, 'storeIngreso'])->name('ingreso.store');
            Route::post('/salida', [InventarioController::class, 'storeSalida'])->name('salida.store');
        });
    });

    Route::middleware('role:cliente')->group(function () {
        Route::get('/cliente/productos', ClienteProductoController::class)->name('cliente.productos');
    });
});
