<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. CREAR ROLES Y PERMISOS SI NO EXISTEN
        $rolePropietario = Role::firstOrCreate(['name' => 'propietario']);
        $roleVendedor = Role::firstOrCreate(['name' => 'vendedor']);
        $roleCliente = Role::firstOrCreate(['name' => 'cliente']);

        // 2. CREAR USUARIO PROPIETARIO (ADMIN)
        $admin = User::firstOrCreate(
            ['email' => 'admin@licorvintage.com'],
            [
                'name' => 'Propietario',
                'password' => Hash::make('password'),
                'ci' => '1234567',
                'phone' => '71020304',
            ]
        );
        $admin->assignRole($rolePropietario);

        // 3. CREAR VENDEDORES (EMPLEADOS)
        $vendedoresData = [
            [
                'name' => 'Javier Flores',
                'email' => 'javier@licorvintage.com',
                'password' => Hash::make('password'),
                'ci' => '7654321',
                'phone' => '72030405',
            ],
            [
                'name' => 'Sofía Rojas',
                'email' => 'sofia@licorvintage.com',
                'password' => Hash::make('password'),
                'ci' => '8765432',
                'phone' => '73040506',
            ],
            [
                'name' => 'Laura Torres',
                'email' => 'laura@licorvintage.com',
                'password' => Hash::make('password'),
                'ci' => '9876543',
                'phone' => '74050607',
            ],
            [
                'name' => 'Carlos Mendoza',
                'email' => 'carlos@licorvintage.com',
                'password' => Hash::make('password'),
                'ci' => '3456789',
                'phone' => '75060708',
            ],
        ];

        foreach ($vendedoresData as $vend) {
            $userVend = User::firstOrCreate(['email' => $vend['email']], $vend);
            $userVend->assignRole($roleVendedor);
        }

        // 4. CREAR CLIENTES DE PRUEBA
        $nombresClientes = [
            'Alejandro', 'Andrés', 'Beatriz', 'Camila', 'Cristian', 'Daniela', 'Diego', 'Eduardo', 
            'Esteban', 'Fernanda', 'Gabriel', 'Gabriela', 'Hugo', 'Isabel', 'Jorge', 'José', 
            'Juan', 'Juliana', 'Luis', 'María', 'Mariana', 'Mateo', 'Natalia', 'Oscar', 
            'Patricia', 'Pedro', 'Ricardo', 'Roberto', 'Santiago', 'Sebastian', 'Sofía', 'Valeria'
        ];
        $apellidosClientes = [
            'Gomez', 'Rodriguez', 'Gonzalez', 'Fernandez', 'Lopez', 'Diaz', 'Martinez', 'Perez', 
            'García', 'Sánchez', 'Romero', 'Sosa', 'Torres', 'Ruiz', 'Alvarez', 'Suarez', 
            'Castillo', 'Flores', 'Vargas', 'Ramos', 'Castro', 'Ortiz', 'Silva', 'Mendoza'
        ];

        for ($i = 1; $i <= 50; $i++) {
            $nombre = $nombresClientes[array_rand($nombresClientes)];
            $apellido = $apellidosClientes[array_rand($apellidosClientes)];
            $fullName = $nombre . ' ' . $apellido . ' ' . ($i);
            
            $clienteUser = User::firstOrCreate(
                ['email' => 'cliente' . $i . '@gmail.com'],
                [
                    'name' => $fullName,
                    'password' => Hash::make('password'),
                    'ci' => (string) rand(4000000, 9000000),
                    'phone' => (string) rand(70000000, 79999999),
                ]
            );
            $clienteUser->assignRole($roleCliente);
        }

        // 5. LLAMAR A LOS DEMÁS SEEDERS EN ORDEN
        $this->call([
            ProveedorSeeder::class,
            ProductoSeeder::class,
            PromocionSeeder::class,
            DetallePromoSeeder::class,
            CompraSeeder::class,
            AperturaCajaSeeder::class,
            VentaSeeder::class,
            TipoSalidaSeeder::class,
        ]);

        // 6. CREAR MENUS PARA LA BARRA DE NAVEGACIÓN
        $menus = [
            ['label' => 'Dashboard', 'route_name' => 'dashboard', 'roles' => ['propietario', 'vendedor']],
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
            \App\Models\MenuItem::updateOrCreate(
                ['route_name' => $menu['route_name']],
                ['label' => $menu['label'], 'roles' => $menu['roles']]
            );
        }
    }
}

