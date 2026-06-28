<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClienteCuotaController;
use App\Http\Controllers\ClienteProductoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoImagenController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use App\Models\ActivityLog;
use App\Models\AperturaCaja;
use App\Models\Compra;
use App\Models\PageView;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/api/search', [SearchController::class, 'quickSearch'])->name('api.search');

// Rutas de autenticación con Google SSO
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Completado de perfil para clientes logueados por SSO
    Route::get('/complete-profile', [GoogleController::class, 'showCompleteProfileForm'])->name('profile.complete');
    Route::post('/complete-profile', [GoogleController::class, 'storeCompleteProfile'])->name('profile.complete.store');

    Route::get('/dashboard', function () {
        if (auth()->user()->hasRole('cliente')) {
            return redirect()->route('cliente.productos');
        }

        if (auth()->user()->hasRole('vendedor')) {
            return redirect()->route('caja.index');
        }

        // Ventas de hoy
        $ventasHoy = Venta::whereDate('created_at', today())->sum('monto_final');

        // Compras del mes
        $comprasMes = Compra::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('costo');

        // Monto actual en caja (última caja abierta)
        $cajaActiva = AperturaCaja::where('estado', 'abierta')->latest()->first();
        $montoCaja = $cajaActiva ? $cajaActiva->monto_sistema : 0;

        // Total productos
        $totalProductos = Producto::count();

        // Top 5 páginas más visitadas
        $paginasMasVisitadas = PageView::orderByDesc('views_count')->limit(5)->get();

        // Top 5 recursos más accedidos de la bitácora
        $recursosMasAccedidos = ActivityLog::where('event_type', 'resource_access')
            ->select('resource_name', DB::raw('count(*) as total'))
            ->groupBy('resource_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'ventas_hoy' => (float) $ventasHoy,
                'compras_mes' => (float) $comprasMes,
                'monto_caja' => (float) $montoCaja,
                'total_productos' => $totalProductos,
            ],
            'paginas_visitadas' => $paginasMasVisitadas,
            'recursos_accedidos' => $recursosMasAccedidos,
        ]);
    })->name('dashboard');

    // Módulo de Seguridad (Exclusivo para el Propietario)
    Route::middleware('role:propietario')->group(function () {
        Route::get('/security', [SecurityController::class, 'index'])->name('security.index');
        Route::post('/security/matrix', [SecurityController::class, 'updateMatrix'])->name('security.matrix.update');
    });

    // Rutas protegidas dinámicamente según la Matriz de Acceso
    Route::middleware('menu.auth')->group(function () {
        Route::resource('productos', ProductoController::class)->except(['create', 'show', 'edit']);
        Route::post('/productos/imagen', [ProductoImagenController::class, 'store'])->name('productos.imagen.store');

        Route::resource('compras', CompraController::class)->except(['create', 'show', 'edit']);

        Route::resource('proveedores', ProveedorController::class)->except(['create', 'show', 'edit']);

        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');

        Route::get('/caja', [CajaController::class, 'index'])->name('caja.index');
        Route::post('/caja/open', [CajaController::class, 'open'])->name('caja.open');
        Route::put('/caja/{caja}/close', [CajaController::class, 'close'])->name('caja.close');

        Route::resource('usuarios', UsuarioController::class)->except(['create', 'show', 'edit']);

        Route::prefix('inventario')->name('inventario.')->group(function () {
            Route::get('/', [InventarioController::class, 'index'])->name('index');
            Route::get('/movimientos', [InventarioController::class, 'movimientos'])->name('movimientos');
            Route::get('/kardex', [InventarioController::class, 'kardex'])->name('kardex');
            Route::get('/valorizacion', [InventarioController::class, 'valorizacion'])->name('valorizacion');
            Route::get('/lotes', [InventarioController::class, 'lotes'])->name('lotes');
            Route::get('/salidas', [InventarioController::class, 'salidas'])->name('salidas.index');
            Route::post('/salidas', [InventarioController::class, 'storeSalida'])->name('salidas.store');
        });

        Route::resource('promociones', PromocionController::class)->except(['create', 'show', 'edit']);
        Route::post('/caja/cuotas/{cuota}/pagar', [CajaController::class, 'payInstallment'])->name('caja.cuotas.pagar');
        Route::post('/caja/clientes/rapido', function (Request $request) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
            ]);
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->assignRole('cliente');

            return back()->with('success', 'Cliente registrado correctamente.');
        })->name('caja.clientes.rapido');

        Route::get('/cliente/catalogo', ClienteProductoController::class)->name('cliente.productos');
        Route::post('/cliente/cuotas/{cuota}/pagar', [ClienteCuotaController::class, 'pay'])->name('cliente.cuotas.pagar');
    });

    // Ruta de ventas disponible para todos los roles autenticados (clientes incluidos)
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');

    // Pago QR
    Route::post('/pago/qr/generar', [PagoController::class, 'generateQR'])->name('pago.qr.generar');
    Route::post('/pago/qr/check-status', [PagoController::class, 'checkStatus'])->name('pago.qr.checkStatus');
});
