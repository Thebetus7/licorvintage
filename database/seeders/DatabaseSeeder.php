<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = ['propietario', 'vendedor', 'cliente'];

        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }

        $admin = User::updateOrCreate([
            'email' => 'admin@gmail.com',
        ], [
            'name' => 'Propietario',
            'ci' => '12345678',
            'phone' => '70000001',
            'password' => Hash::make('123456789'),
        ]);

        $admin->syncRoles(['propietario']);

        $cliente = User::updateOrCreate([
            'email' => 'cliente@gmail.com',
        ], [
            'name' => 'UsuarioCliente',
            'ci' => '87654321',
            'phone' => '70000002',
            'password' => Hash::make('123456789'),
        ]);

        $cliente->syncRoles(['cliente']);

        $vendedor = User::updateOrCreate([
            'email' => 'vendedor@gmail.com',
        ], [
            'name' => 'UsuarioVendedor',
            'password' => Hash::make('123456789'),
        ]);

        $vendedor->syncRoles(['vendedor']);

        $bebidas = [
            [
                'nombre' => 'Fernet Branca',
                'mililitros' => 750,
                'precio_venta' => 120.00,
                'costo' => 80.00,
                'costo_promedio' => 80.00,
                'descripcion' => 'Fernet Branca de 750ml, el clásico sabor italiano.',
                'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500&auto=format&fit=crop&q=60',
                'codigo_barra' => '7790895000431',
                'publicado' => true,
                'stock' => 15,
                'min' => 5,
                'max' => 50,
            ],
            [
                'nombre' => 'Whisky Johnnie Walker Red Label',
                'mililitros' => 700,
                'precio_venta' => 150.00,
                'costo' => 100.00,
                'costo_promedio' => 100.00,
                'descripcion' => 'Whisky Escocés Red Label.',
                'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&auto=format&fit=crop&q=60',
                'codigo_barra' => '5000267014205',
                'publicado' => true,
                'stock' => 8,
                'min' => 2,
                'max' => 20,
            ],
            [
                'nombre' => 'Ron Flor de Caña 7 Años',
                'mililitros' => 750,
                'precio_venta' => 110.00,
                'costo' => 75.00,
                'costo_promedio' => 75.00,
                'descripcion' => 'Ron gran reserva premium de Nicaragua.',
                'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&auto=format&fit=crop&q=60',
                'codigo_barra' => '093952002347',
                'publicado' => true,
                'stock' => 12,
                'min' => 3,
                'max' => 30,
            ],
        ];

        foreach ($bebidas as $bebida) {
            $producto = Producto::updateOrCreate([
                'codigo_barra' => $bebida['codigo_barra'],
            ], [
                'nombre' => $bebida['nombre'],
                'mililitros' => $bebida['mililitros'],
                'precio_venta' => $bebida['precio_venta'],
                'costo' => $bebida['costo'],
                'costo_promedio' => $bebida['costo_promedio'],
                'descripcion' => $bebida['descripcion'],
                'imagen' => $bebida['imagen'],
                'publicado' => $bebida['publicado'],
            ]);

            $producto->stockActual()->updateOrCreate([
                'producto_id' => $producto->id,
            ], [
                'stock' => $bebida['stock'],
                'min' => $bebida['min'],
                'max' => $bebida['max'],
            ]);
        }

        $menus = [
            ['label' => 'Dashboard', 'route_name' => 'dashboard', 'roles' => ['propietario', 'vendedor', 'cliente']],
            ['label' => 'Productos', 'route_name' => 'productos.index', 'roles' => ['propietario', 'vendedor']],
            ['label' => 'Compras', 'route_name' => 'compras.index', 'roles' => ['propietario', 'vendedor']],
            ['label' => 'Ventas', 'route_name' => 'ventas.index', 'roles' => ['propietario', 'vendedor']],
            ['label' => 'Caja', 'route_name' => 'caja.index', 'roles' => ['propietario', 'vendedor']],
            ['label' => 'Promociones', 'route_name' => 'promociones.index', 'roles' => ['propietario', 'vendedor']],
            ['label' => 'Inventario', 'route_name' => 'inventario.index', 'roles' => ['propietario']],
            ['label' => 'Usuarios', 'route_name' => 'usuarios.index', 'roles' => ['propietario']],
            ['label' => 'Seguridad', 'route_name' => 'security.index', 'roles' => ['propietario']],
            ['label' => 'Catalogo', 'route_name' => 'cliente.productos', 'roles' => ['cliente']],
        ];

        foreach ($menus as $menu) {
            MenuItem::updateOrCreate([
                'route_name' => $menu['route_name'],
            ], [
                'label' => $menu['label'],
                'roles' => $menu['roles'],
            ]);
        }

        $this->call([
            TipoSalidaSeeder::class,
        ]);
    }
}
