<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Producto;
use App\Models\User;
use App\Models\Proveedor;
use App\Models\Compra;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Promocion;
use App\Models\DetallePromo;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\MetodoPago;
use App\Models\VentaCuotas;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. ROLES
        $roles = ['propietario', 'vendedor', 'cliente'];

        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }

        // 2. CORE USERS
        $admin = User::updateOrCreate([
            'email' => 'admin@gmail.com',
        ], [
            'name' => 'Propietario',
            'ci' => '12345678',
            'phone' => '70000001',
            'password' => Hash::make('123456789'),
        ]);
        $admin->syncRoles(['propietario']);

        $clienteDefault = User::updateOrCreate([
            'email' => 'cliente@gmail.com',
        ], [
            'name' => 'UsuarioCliente',
            'ci' => '87654321',
            'phone' => '70000002',
            'password' => Hash::make('123456789'),
        ]);
        $clienteDefault->syncRoles(['cliente']);

        $vendedor = User::updateOrCreate([
            'email' => 'vendedor@gmail.com',
        ], [
            'name' => 'UsuarioVendedor',
            'password' => Hash::make('123456789'),
        ]);
        $vendedor->syncRoles(['vendedor']);

        // 3. 50 CLIENTS
        $nombres = ['Juan', 'Pedro', 'María', 'Ana', 'Carlos', 'Luis', 'Sofía', 'Lucía', 'Diego', 'Andrés', 'Laura', 'Camila', 'Javier', 'Alejandro', 'Gabriela', 'Fernanda', 'Ricardo', 'Roberto', 'Daniel', 'Patricia'];
        $apellidos = ['Gómez', 'Rodríguez', 'González', 'Fernández', 'López', 'Martínez', 'Pérez', 'Sánchez', 'Romero', 'Torres', 'Ruiz', 'Díaz', 'Vargas', 'Mendoza', 'Flores', 'Silva', 'Castro', 'Ortiz', 'Morales', 'Rojas'];

        for ($i = 1; $i <= 50; $i++) {
            $nombreCompleto = $nombres[array_rand($nombres)] . ' ' . $apellidos[array_rand($apellidos)];
            $email = strtolower(str_replace(' ', '.', $nombreCompleto)) . $i . '@gmail.com';
            
            $u = User::create([
                'name' => $nombreCompleto,
                'email' => $email,
                'ci' => (string) rand(4000000, 9999999),
                'phone' => (string) rand(60000000, 79999999),
                'password' => Hash::make('123456789'),
            ]);
            $u->syncRoles(['cliente']);
        }

        // 4. 10 SUPPLIERS
        $proveedoresData = [
            ['nombre' => 'Importadora D&M S.A.', 'telefono' => '3345678', 'descripcion' => 'Distribuidor oficial de licores premium en Bolivia.'],
            ['nombre' => 'Distribuidora Licores del Sur', 'telefono' => '4456789', 'descripcion' => 'Especialistas en vinos y singanis nacionales.'],
            ['nombre' => 'Bodegas Casa Real', 'telefono' => '6621345', 'descripcion' => 'Productor y distribuidor de Singani Casa Real.'],
            ['nombre' => 'Bodegas Aranjuez S.A.', 'telefono' => '6643210', 'descripcion' => 'Productores de Vinos Aranjuez, Tarija.'],
            ['nombre' => 'Cervecería Boliviana Nacional (CBN)', 'telefono' => '3123456', 'descripcion' => 'Distribuidor de Paceña, Huari, Corona y Stella Artois.'],
            ['nombre' => 'Distribuidora El Pairumani', 'telefono' => '4412345', 'descripcion' => 'Importador de marcas internacionales de whisky y ron.'],
            ['nombre' => 'Licores Importados Global S.R.L.', 'telefono' => '3398765', 'descripcion' => 'Importaciones directas de tequila, vodka y gin.'],
            ['nombre' => 'Bebidas del Oriente S.R.L.', 'telefono' => '3387654', 'descripcion' => 'Distribución masiva de cervezas y licores populares.'],
            ['nombre' => 'Bodega Campos de Solana', 'telefono' => '6645678', 'descripcion' => 'Vinos finos de altura, Tarija.'],
            ['nombre' => 'Bebidas Premium Bolivia', 'telefono' => '71020304', 'descripcion' => 'Importadora boutique de whiskies single malt y licores raros.'],
        ];

        $proveedores = [];
        foreach ($proveedoresData as $prov) {
            $proveedores[] = Proveedor::create($prov);
        }

        // 5. 100 PRODUCTS
        $productosData = [
            // Whisky (15)
            ['nombre' => 'Whisky Johnnie Walker Red Label', 'mililitros' => 750, 'costo' => 90.00, 'precio_venta' => 130.00, 'descripcion' => 'Whisky escocés mezclado, clásico y versátil.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5000267014205'],
            ['nombre' => 'Whisky Johnnie Walker Black Label', 'mililitros' => 750, 'costo' => 180.00, 'precio_venta' => 250.00, 'descripcion' => 'Whisky escocés de 12 años, notas ahumadas y de frutos secos.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5000267113113'],
            ['nombre' => 'Whisky Johnnie Walker Double Black', 'mililitros' => 750, 'costo' => 220.00, 'precio_venta' => 300.00, 'descripcion' => 'Una versión más intensa y ahumada del clásico Black Label.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5000267123303'],
            ['nombre' => 'Whisky Johnnie Walker Gold Label', 'mililitros' => 750, 'costo' => 350.00, 'precio_venta' => 480.00, 'descripcion' => 'Mezcla premium cremosa con notas de miel y madera.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5000267154565'],
            ['nombre' => 'Whisky Johnnie Walker Blue Label', 'mililitros' => 750, 'costo' => 1200.00, 'precio_venta' => 1650.00, 'descripcion' => 'La obra maestra de Johnnie Walker, mezcla de los whiskies más raros y excepcionales.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5000267223344'],
            ['nombre' => 'Whisky Chivas Regal 12 Años', 'mililitros' => 750, 'costo' => 160.00, 'precio_venta' => 220.00, 'descripcion' => 'Whisky escocés suave, madurado en barricas de roble.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5000299223341'],
            ['nombre' => 'Whisky Chivas Regal 18 Años', 'mililitros' => 750, 'costo' => 400.00, 'precio_venta' => 560.00, 'descripcion' => 'Mezcla rica y compleja con hasta 85 notas de sabor.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5000299654128'],
            ['nombre' => 'Whisky Jack Daniel\'s Old No. 7', 'mililitros' => 750, 'costo' => 150.00, 'precio_venta' => 210.00, 'descripcion' => 'Whiskey de Tennessee filtrado en carbón de arce.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '082184090446'],
            ['nombre' => 'Whisky Jack Daniel\'s Honey', 'mililitros' => 750, 'costo' => 160.00, 'precio_venta' => 220.00, 'descripcion' => 'Licor de whiskey con un toque suave de miel natural.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '082184000322'],
            ['nombre' => 'Whisky Jack Daniel\'s Apple', 'mililitros' => 750, 'costo' => 160.00, 'precio_venta' => 220.00, 'descripcion' => 'Mezcla de Jack Daniel\'s con licor de manzana verde.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '082184005129'],
            ['nombre' => 'Whisky Jameson Irish Whiskey', 'mililitros' => 750, 'costo' => 110.00, 'precio_venta' => 150.00, 'descripcion' => 'Whiskey irlandés de triple destilación, extremadamente suave.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5011007003009'],
            ['nombre' => 'Whisky Macallan 12 Años Double Cask', 'mililitros' => 700, 'costo' => 550.00, 'precio_venta' => 750.00, 'descripcion' => 'Whisky single malt envejecido en barricas de roble americano y europeo.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5010314301551'],
            ['nombre' => 'Whisky Glenfiddich 12 Años', 'mililitros' => 750, 'costo' => 320.00, 'precio_venta' => 440.00, 'descripcion' => 'El whisky single malt más galardonado del mundo, notas de pera fresca.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5010326115122'],
            ['nombre' => 'Whisky Ballantine\'s Finest', 'mililitros' => 750, 'costo' => 80.00, 'precio_venta' => 115.00, 'descripcion' => 'Whisky escocés sutil y dulce con toques de vainilla.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5010106113127'],
            ['nombre' => 'Whisky Singleton 12 Años', 'mililitros' => 700, 'costo' => 260.00, 'precio_venta' => 360.00, 'descripcion' => 'Whisky single malt suave con notas frutales y de miel.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500', 'codigo_barra' => '5000267124317'],

            // Tequila (15)
            ['nombre' => 'Tequila José Cuervo Especial Gold', 'mililitros' => 750, 'costo' => 95.00, 'precio_venta' => 135.00, 'descripcion' => 'Tequila joven reposado, ideal para margaritas.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501035010188'],
            ['nombre' => 'Tequila José Cuervo Tradicional Reposado', 'mililitros' => 950, 'costo' => 140.00, 'precio_venta' => 195.00, 'descripcion' => '100% de agave azul, reposado en barricas de roble.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501035010041'],
            ['nombre' => 'Tequila Patrón Silver', 'mililitros' => 750, 'costo' => 280.00, 'precio_venta' => 380.00, 'descripcion' => 'Tequila ultra premium transparente, suave y ligero.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '072100350027'],
            ['nombre' => 'Tequila Patrón Añejo', 'mililitros' => 750, 'costo' => 320.00, 'precio_venta' => 450.00, 'descripcion' => 'Tequila envejecido en barricas de roble durante más de 12 meses.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '072100350041'],
            ['nombre' => 'Tequila Don Julio Blanco', 'mililitros' => 750, 'costo' => 240.00, 'precio_venta' => 330.00, 'descripcion' => 'Tequila base para todas las variantes de Don Julio, notas cítricas.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501001110027'],
            ['nombre' => 'Tequila Don Julio Reposado', 'mililitros' => 750, 'costo' => 270.00, 'precio_venta' => 370.00, 'descripcion' => 'Envejecido por 8 meses, notas de chocolate y vainilla.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501001110034'],
            ['nombre' => 'Tequila Don Julio 70 Añejo Cristalino', 'mililitros' => 700, 'costo' => 450.00, 'precio_venta' => 610.00, 'descripcion' => 'Tequila añejo filtrado para recuperar la transparencia y frescura.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501001112083'],
            ['nombre' => 'Tequila 1800 Añejo', 'mililitros' => 750, 'costo' => 290.00, 'precio_venta' => 400.00, 'descripcion' => 'Tequila 100% agave madurado en roble francés.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501035022020'],
            ['nombre' => 'Tequila 1800 Cristalino Añejo', 'mililitros' => 700, 'costo' => 380.00, 'precio_venta' => 520.00, 'descripcion' => 'Añejo cristalino premium en botella icónica.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501035041076'],
            ['nombre' => 'Tequila Herradura Reposado', 'mililitros' => 700, 'costo' => 260.00, 'precio_venta' => 360.00, 'descripcion' => 'Tequila reposado por 11 meses con un color cobre profundo.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '74422020054'],
            ['nombre' => 'Tequila El Jimador Blanco', 'mililitros' => 750, 'costo' => 80.00, 'precio_venta' => 115.00, 'descripcion' => 'Tequila joven cristalino de sabor fresco y natural.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501101610014'],
            ['nombre' => 'Tequila El Jimador Reposado', 'mililitros' => 750, 'costo' => 85.00, 'precio_venta' => 120.00, 'descripcion' => 'Tequila reposado por 2 meses en barricas de roble blanco.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501101610021'],
            ['nombre' => 'Tequila Sauza Hacienda Gold', 'mililitros' => 1000, 'costo' => 90.00, 'precio_venta' => 125.00, 'descripcion' => 'Tequila mixto reposado de cuerpo medio y notas herbales.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501005010620'],
            ['nombre' => 'Tequila Corralejo Reposado', 'mililitros' => 1000, 'costo' => 210.00, 'precio_venta' => 295.00, 'descripcion' => 'Botella azul característica de Guanajuato, sabor suave y amaderado.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '7501140400034'],
            ['nombre' => 'Tequila Olmeca Blanco', 'mililitros' => 750, 'costo' => 85.00, 'precio_venta' => 120.00, 'descripcion' => 'Tequila ideal para mezclas de cócteles, sabor herbal.', 'imagen' => 'https://images.unsplash.com/photo-1516535794938-6063878f28cc?w=500', 'codigo_barra' => '5011007015439'],

            // Ron (15)
            ['nombre' => 'Ron Flor de Caña 4 Años Extra Seco', 'mililitros' => 750, 'costo' => 60.00, 'precio_venta' => 85.00, 'descripcion' => 'Ron blanco ligero y seco, ideal para mojitos.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500', 'codigo_barra' => '093952002132'],
            ['nombre' => 'Ron Flor de Caña 7 Años Gran Reserva', 'mililitros' => 750, 'costo' => 75.00, 'precio_venta' => 110.00, 'descripcion' => 'Ron gran reserva premium de Nicaragua de 7 años.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500', 'codigo_barra' => '093952002347'],
            ['nombre' => 'Ron Flor de Caña 12 Años Centenario', 'mililitros' => 750, 'costo' => 160.00, 'precio_venta' => 220.00, 'descripcion' => 'Ron ultra premium envejecido por 12 años en barricas de bourbon.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500', 'codigo_barra' => '093952002187'],
            ['nombre' => 'Ron Havana Club 3 Años', 'mililitros' => 750, 'costo' => 65.00, 'precio_venta' => 90.00, 'descripcion' => 'El ron cubano por excelencia para cócteles cubanos clásicos.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '8500001850123'],
            ['nombre' => 'Ron Havana Club 7 Años', 'mililitros' => 750, 'costo' => 110.00, 'precio_venta' => 150.00, 'descripcion' => 'Ron cubano añejo con notas intensas de cacao y tabaco.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '8500001850253'],
            ['nombre' => 'Ron Bacardi Carta Blanca', 'mililitros' => 750, 'costo' => 55.00, 'precio_venta' => 80.00, 'descripcion' => 'Ron blanco suave y equilibrado, marca icónica global.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '076111110757'],
            ['nombre' => 'Ron Bacardi Carta Oro', 'mililitros' => 750, 'costo' => 60.00, 'precio_venta' => 85.00, 'descripcion' => 'Ron dorado con ricas notas de vainilla y caramelo.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '076111110764'],
            ['nombre' => 'Ron Barceló Imperial', 'mililitros' => 700, 'costo' => 170.00, 'precio_venta' => 240.00, 'descripcion' => 'Ron premium de República Dominicana envejecido hasta 10 años.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '7460855092025'],
            ['nombre' => 'Ron Barceló Añejo', 'mililitros' => 750, 'costo' => 65.00, 'precio_venta' => 95.00, 'descripcion' => 'Ron añejo destilado a partir de melaza dominicana.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '7460855092001'],
            ['nombre' => 'Ron Zacapa 23 Sistema Solera', 'mililitros' => 750, 'costo' => 380.00, 'precio_venta' => 520.00, 'descripcion' => 'Ron premium de Guatemala envejecido por sistema solera en las nubes.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '7401005005822'],
            ['nombre' => 'Ron Abuelo 7 Años Reserva Superior', 'mililitros' => 750, 'costo' => 95.00, 'precio_venta' => 135.00, 'descripcion' => 'Ron panameño envejecido en barricas de roble blanco americano.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '7840003001258'],
            ['nombre' => 'Ron Matusalem Gran Reserva 15 Años', 'mililitros' => 700, 'costo' => 180.00, 'precio_venta' => 250.00, 'descripcion' => 'Ron super premium con notas de ciruela y frutos secos.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '7501035025403'],
            ['nombre' => 'Ron Captain Morgan Spiced Gold', 'mililitros' => 750, 'costo' => 65.00, 'precio_venta' => 95.00, 'descripcion' => 'Bebida espirituosa a base de ron del Caribe con especias naturales.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '5000267024204'],
            ['nombre' => 'Ron Malibu Coconut', 'mililitros' => 750, 'costo' => 75.00, 'precio_venta' => 110.00, 'descripcion' => 'Licor a base de ron caribeño con extracto de coco natural.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '5011007015491'],
            ['nombre' => 'Ron Cacique Añejo', 'mililitros' => 750, 'costo' => 70.00, 'precio_venta' => 100.00, 'descripcion' => 'Ron venezolano clásico de sabor robusto y notas dulces.', 'imagen' => 'https://images.unsplash.com/photo-1614313511387-1436a4480edd?w=500&q=60', 'codigo_barra' => '7591012010013'],

            // Vodka (15)
            ['nombre' => 'Vodka Absolut Blue', 'mililitros' => 750, 'costo' => 70.00, 'precio_venta' => 100.00, 'descripcion' => 'Vodka sueco clásico elaborado únicamente con ingredientes naturales.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '7312040017072'],
            ['nombre' => 'Vodka Absolut Citron', 'mililitros' => 750, 'costo' => 75.00, 'precio_venta' => 110.00, 'descripcion' => 'Vodka con sabor a limón y toques de lima.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '7312040017089'],
            ['nombre' => 'Vodka Absolut Mandarin', 'mililitros' => 750, 'costo' => 75.00, 'precio_venta' => 110.00, 'descripcion' => 'Vodka con sabor a mandarina y notas de naranja.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '7312040017096'],
            ['nombre' => 'Vodka Absolut Raspberri', 'mililitros' => 750, 'costo' => 75.00, 'precio_venta' => 110.00, 'descripcion' => 'Vodka con un carácter intenso de frambuesas maduras.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '7312040017102'],
            ['nombre' => 'Vodka Smirnoff Red', 'mililitros' => 750, 'costo' => 50.00, 'precio_venta' => 75.00, 'descripcion' => 'Vodka de origen ruso de triple destilación y diez filtraciones.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '5410316930018'],
            ['nombre' => 'Vodka Grey Goose', 'mililitros' => 750, 'costo' => 220.00, 'precio_venta' => 310.00, 'descripcion' => 'Vodka ultra premium francés de trigo de invierno.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '080480280024'],
            ['nombre' => 'Vodka Cîroc Blue', 'mililitros' => 750, 'costo' => 210.00, 'precio_venta' => 295.00, 'descripcion' => 'Vodka francés ultra premium elaborado a partir de uvas finas.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '636830501899'],
            ['nombre' => 'Vodka Stolichnaya Premium', 'mililitros' => 750, 'costo' => 65.00, 'precio_venta' => 95.00, 'descripcion' => 'Vodka letón de grano clásico, suave e ideal para martinis.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '4750021000109'],
            ['nombre' => 'Vodka Wyborowa', 'mililitros' => 750, 'costo' => 55.00, 'precio_venta' => 80.00, 'descripcion' => 'Vodka de centeno polaco de sabor ligeramente dulce.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '5900106135003'],
            ['nombre' => 'Vodka Skyy', 'mililitros' => 750, 'costo' => 60.00, 'precio_venta' => 85.00, 'descripcion' => 'Vodka norteamericano destilado cuatro veces y filtrado tres veces.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '721001150759'],
            ['nombre' => 'Vodka Finlandia', 'mililitros' => 750, 'costo' => 65.00, 'precio_venta' => 95.00, 'descripcion' => 'Elaborado con agua pura de manantial glaciar finlandés.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '5099873001402'],
            ['nombre' => 'Vodka Belvedere', 'mililitros' => 700, 'costo' => 230.00, 'precio_venta' => 320.00, 'descripcion' => 'Vodka polaco súper premium elaborado con centeno de oro.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '5901041002018'],
            ['nombre' => 'Vodka Ketel One', 'mililitros' => 750, 'costo' => 130.00, 'precio_venta' => 180.00, 'descripcion' => 'Vodka holandés destilado en alambiques de cobre tradicionales.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '8500002130026'],
            ['nombre' => 'Vodka Sobieski', 'mililitros' => 1000, 'costo' => 70.00, 'precio_venta' => 100.00, 'descripcion' => 'Vodka polaco premium elaborado con centeno Dankowski.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '5900535002008'],
            ['nombre' => 'Vodka Eristoff', 'mililitros' => 700, 'costo' => 55.00, 'precio_venta' => 80.00, 'descripcion' => 'Vodka de triple destilación basado en una receta georgiana.', 'imagen' => 'https://images.unsplash.com/photo-1608885898957-a599fb1698d6?w=500', 'codigo_barra' => '3093220021321'],

            // Gin (10)
            ['nombre' => 'Gin Beefeater London Dry', 'mililitros' => 750, 'costo' => 110.00, 'precio_venta' => 155.00, 'descripcion' => 'Ginebra clásica de Londres con enebro y cítricos.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&q=60', 'codigo_barra' => '5000329002100'],
            ['nombre' => 'Gin Beefeater Pink Strawberry', 'mililitros' => 700, 'costo' => 120.00, 'precio_venta' => 165.00, 'descripcion' => 'Ginebra con sabor natural a fresas maduras.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&q=60', 'codigo_barra' => '5000329002346'],
            ['nombre' => 'Gin Tanqueray London Dry', 'mililitros' => 750, 'costo' => 125.00, 'precio_venta' => 175.00, 'descripcion' => 'Una de las ginebras más premiadas del mundo por su equilibrio.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&q=60', 'codigo_barra' => '5000267024006'],
            ['nombre' => 'Gin Tanqueray Flor de Sevilla', 'mililitros' => 750, 'costo' => 135.00, 'precio_venta' => 190.00, 'descripcion' => 'Ginebra destilada con naranjas de Sevilla y aromas florales.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&q=60', 'codigo_barra' => '5000267180436'],
            ['nombre' => 'Gin Bombay Sapphire', 'mililitros' => 750, 'costo' => 130.00, 'precio_venta' => 180.00, 'descripcion' => 'Famosa ginebra azul con 10 botánicos infundidos al vapor.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&q=60', 'codigo_barra' => '076111000799'],
            ['nombre' => 'Gin Hendricks', 'mililitros' => 750, 'costo' => 240.00, 'precio_venta' => 330.00, 'descripcion' => 'Ginebra escocesa infundida con pepino y pétalos de rosa.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&q=60', 'codigo_barra' => '5010326000237'],
            ['nombre' => 'Gin Bulldog London Dry', 'mililitros' => 750, 'costo' => 140.00, 'precio_venta' => 195.00, 'descripcion' => 'Ginebra moderna con ojo de dragón, hojas de loto y lavanda.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&q=60', 'codigo_barra' => '721001155075'],
            ['nombre' => 'Gin Larios Dry', 'mililitros' => 700, 'costo' => 70.00, 'precio_venta' => 100.00, 'descripcion' => 'Ginebra mediterránea de doble destilación con naranja.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&q=60', 'codigo_barra' => '8410221310022'],
            ['nombre' => 'Gin Gordon\'s London Dry', 'mililitros' => 750, 'costo' => 75.00, 'precio_venta' => 110.00, 'descripcion' => 'Ginebra clásica de receta centenaria con fuerte enebro.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&q=60', 'codigo_barra' => '5000267024303'],
            ['nombre' => 'Gin La República Andina', 'mililitros' => 700, 'costo' => 110.00, 'precio_venta' => 160.00, 'descripcion' => 'Ginebra boliviana premium destilada a base de botánicos andinos.', 'imagen' => 'https://images.unsplash.com/photo-1527281473220-7ab88f596350?w=500&q=60', 'codigo_barra' => '7401201010041'],

            // Licores y Aperitivos (15)
            ['nombre' => 'Jägermeister', 'mililitros' => 700, 'costo' => 120.00, 'precio_venta' => 165.00, 'descripcion' => 'Licor de hierbas alemán elaborado con una receta secreta de 56 botánicos.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '4067700014023'],
            ['nombre' => 'Campari', 'mililitros' => 750, 'costo' => 90.00, 'precio_venta' => 125.00, 'descripcion' => 'Clásico aperitivo amargo de color rojo vibrante.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '8002270101429'],
            ['nombre' => 'Aperol', 'mililitros' => 750, 'costo' => 85.00, 'precio_venta' => 120.00, 'descripcion' => 'Aperitivo italiano de naranja amarga y ruibarbo para el Aperol Spritz.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '8002270014903'],
            ['nombre' => 'Licor Baileys Original Irish Cream', 'mililitros' => 750, 'costo' => 100.00, 'precio_venta' => 140.00, 'descripcion' => 'Licor de crema de whisky irlandés suave y dulce.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '5011013100150'],
            ['nombre' => 'Licor Kahlúa de Café', 'mililitros' => 750, 'costo' => 85.00, 'precio_venta' => 120.00, 'descripcion' => 'Licor de café mexicano clásico, ideal para Black Russian y espresso martini.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '761100050756'],
            ['nombre' => 'Licor 43', 'mililitros' => 700, 'costo' => 130.00, 'precio_venta' => 180.00, 'descripcion' => 'Licor español dulce con sabor a vainilla y 43 botánicos.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '8410222043004'],
            ['nombre' => 'Amaretto Disaronno', 'mililitros' => 700, 'costo' => 140.00, 'precio_venta' => 195.00, 'descripcion' => 'Licor italiano de almendras con aroma y sabor característicos.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '8001110016202'],
            ['nombre' => 'Licor Cointreau Triple Sec', 'mililitros' => 700, 'costo' => 160.00, 'precio_venta' => 220.00, 'descripcion' => 'Licor de naranja cristalino, ingrediente clave del margarita.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '3028130007503'],
            ['nombre' => 'Fernet Branca', 'mililitros' => 750, 'costo' => 80.00, 'precio_venta' => 120.00, 'descripcion' => 'Fernet Branca de 750ml, el clásico sabor italiano.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '7790895000431'],
            ['nombre' => 'Fernet Branca 1L', 'mililitros' => 1000, 'costo' => 100.00, 'precio_venta' => 150.00, 'descripcion' => 'El clásico Fernet italiano en presentación de 1 litro.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '7790895000424'],
            ['nombre' => 'Fernet Buhero Negro', 'mililitros' => 750, 'costo' => 70.00, 'precio_venta' => 100.00, 'descripcion' => 'Fernet artesanal argentino con notas herbales intensas.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '7798081390458'],
            ['nombre' => 'Pisco Capel Especial Reposado', 'mililitros' => 750, 'costo' => 70.00, 'precio_venta' => 95.00, 'descripcion' => 'Pisco chileno reposado en barricas de roble americano.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '7802100000213'],
            ['nombre' => 'Pisco Alto del Carmen 35°', 'mililitros' => 750, 'costo' => 75.00, 'precio_venta' => 105.00, 'descripcion' => 'Pisco premium elaborado con uva moscatel en el valle de Huasco.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '7802110002341'],
            ['nombre' => 'Mezcal 400 Conejos Espadín', 'mililitros' => 750, 'costo' => 210.00, 'precio_venta' => 295.00, 'descripcion' => 'Mezcal artesanal oaxaqueño con notas ahumadas y maderas suaves.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '7501035048129'],
            ['nombre' => 'Mezcal Mitre Origen', 'mililitros' => 700, 'costo' => 220.00, 'precio_venta' => 310.00, 'descripcion' => 'Mezcal joven de agave Espadín, sabor herbal y ahumado balanceado.', 'imagen' => 'https://images.unsplash.com/photo-1569529465841-dfedd87500f7?w=500', 'codigo_barra' => '7503023023055'],

            // Cerveza (8)
            ['nombre' => 'Cerveza Paceña Pilsener 620ml', 'mililitros' => 620, 'costo' => 10.00, 'precio_venta' => 14.00, 'descripcion' => 'La cerveza tradicional de los bolivianos.', 'imagen' => 'https://images.unsplash.com/photo-1608270176050-12ec05721178?w=500', 'codigo_barra' => '7771010000010'],
            ['nombre' => 'Cerveza Paceña Centenario 710ml', 'mililitros' => 710, 'costo' => 11.00, 'precio_venta' => 15.00, 'descripcion' => 'Edición especial Paceña en lata de gran tamaño.', 'imagen' => 'https://images.unsplash.com/photo-1608270176050-12ec05721178?w=500', 'codigo_barra' => '7771010000218'],
            ['nombre' => 'Cerveza Paceña Negra 330ml', 'mililitros' => 330, 'costo' => 7.00, 'precio_venta' => 10.00, 'descripcion' => 'Cerveza negra de malta con toques dulces tostados.', 'imagen' => 'https://images.unsplash.com/photo-1608270176050-12ec05721178?w=500', 'codigo_barra' => '7771010000041'],
            ['nombre' => 'Cerveza Huari 620ml', 'mililitros' => 620, 'costo' => 11.50, 'precio_venta' => 16.00, 'descripcion' => 'Cerveza premium hecha con agua de vertiente andina.', 'imagen' => 'https://images.unsplash.com/photo-1608270176050-12ec05721178?w=500', 'codigo_barra' => '7771010000126'],
            ['nombre' => 'Cerveza Corona Extra 355ml', 'mililitros' => 355, 'costo' => 8.50, 'precio_venta' => 12.00, 'descripcion' => 'Cerveza mexicana refrescante tipo lager, ideal con limón.', 'imagen' => 'https://images.unsplash.com/photo-1608270176050-12ec05721178?w=500', 'codigo_barra' => '7501064193012'],
            ['nombre' => 'Cerveza Heineken 330ml', 'mililitros' => 330, 'costo' => 9.00, 'precio_venta' => 13.00, 'descripcion' => 'Cerveza holandesa internacional tipo lager premium.', 'imagen' => 'https://images.unsplash.com/photo-1608270176050-12ec05721178?w=500', 'codigo_barra' => '8712000900012'],
            ['nombre' => 'Cerveza Stella Artois 330ml', 'mililitros' => 330, 'costo' => 9.00, 'precio_venta' => 13.00, 'descripcion' => 'Cerveza lager belga de sabor nítido y aroma a lúpulo.', 'imagen' => 'https://images.unsplash.com/photo-1608270176050-12ec05721178?w=500', 'codigo_barra' => '5410228141443'],
            ['nombre' => 'Cerveza Quilmes 970ml', 'mililitros' => 970, 'costo' => 12.00, 'precio_venta' => 17.00, 'descripcion' => 'El sabor del encuentro, cerveza argentina en botella grande.', 'imagen' => 'https://images.unsplash.com/photo-1608270176050-12ec05721178?w=500', 'codigo_barra' => '7790060000011'],

            // Vino (7)
            ['nombre' => 'Vino Casillero del Diablo Cabernet Sauvignon', 'mililitros' => 750, 'costo' => 45.00, 'precio_venta' => 65.00, 'descripcion' => 'Vino tinto chileno de gran cuerpo, ideal para carnes rojas.', 'imagen' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=500', 'codigo_barra' => '7804320753081'],
            ['nombre' => 'Vino Casillero del Diablo Merlot', 'mililitros' => 750, 'costo' => 45.00, 'precio_venta' => 65.00, 'descripcion' => 'Vino tinto suave, con notas de ciruela y especias.', 'imagen' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=500', 'codigo_barra' => '7804320753067'],
            ['nombre' => 'Vino Alamos Malbec', 'mililitros' => 750, 'costo' => 50.00, 'precio_venta' => 75.00, 'descripcion' => 'Vino tinto argentino de Mendoza con aromas frutados.', 'imagen' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=500', 'codigo_barra' => '7790790000031'],
            ['nombre' => 'Vino Tinto 1887', 'mililitros' => 750, 'costo' => 30.00, 'precio_venta' => 45.00, 'descripcion' => 'Vino chileno clásico y accesible, de mesa.', 'imagen' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=500', 'codigo_barra' => '7804310001871'],
            ['nombre' => 'Vino Tinto Aranjuez Tannat', 'mililitros' => 750, 'costo' => 40.00, 'precio_venta' => 60.00, 'descripcion' => 'Vino tarijeño galardonado, representativo del Tannat boliviano.', 'imagen' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=500', 'codigo_barra' => '7772002000049'],
            ['nombre' => 'Vino Blanco Campos de Solana Riesling', 'mililitros' => 750, 'costo' => 42.00, 'precio_venta' => 60.00, 'descripcion' => 'Vino blanco de altura fresco, aromático y equilibrado.', 'imagen' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=500', 'codigo_barra' => '7772004000030'],
            ['nombre' => 'Vino Blanco Campos de Solana Chardonnay', 'mililitros' => 750, 'costo' => 42.00, 'precio_venta' => 60.00, 'descripcion' => 'Vino blanco con paso por barrica, notas de vainilla.', 'imagen' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=500', 'codigo_barra' => '7772004000023'],
        ];

        // We will create the 100 products and store their instances.
        $productos = [];
        foreach ($productosData as $prod) {
            $productos[] = Producto::create([
                'nombre' => $prod['nombre'],
                'mililitros' => $prod['mililitros'],
                'costo' => $prod['costo'],
                'costo_promedio' => $prod['costo'], // initial cost average is same as cost
                'precio_venta' => $prod['precio_venta'],
                'descripcion' => $prod['descripcion'],
                'imagen' => $prod['imagen'],
                'codigo_barra' => $prod['codigo_barra'],
                'publicado' => true,
            ]);
        }

        // 6. 10 PURCHASES (distributed among the 100 products, 10 products per purchase)
        $chunkSize = 10;
        $productChunks = array_chunk($productos, $chunkSize);

        foreach ($productChunks as $index => $chunk) {
            // Pick a random supplier
            $supplier = $proveedores[$index % count($proveedores)];
            
            // Create a purchase record
            $compra = Compra::create([
                'costo' => 0, // Will calculate below
                'proveedor_id' => $supplier->id,
                'user_id' => $admin->id,
            ]);

            $totalCompra = 0;

            foreach ($chunk as $prodIndex => $product) {
                // Determine quantity (between 100 and 200)
                $cantidad = rand(100, 200);
                $subCosto = $product->costo * $cantidad;
                $totalCompra += $subCosto;

                // Set expiration date
                // 5% of products (indices e.g. 0, 1, 2, 3, 4) will be expired
                // 5% of products (indices e.g. 5, 6, 7, 8, 9) will be expiring soon
                // 90% will be long term
                $totalProductIndex = ($index * $chunkSize) + $prodIndex;
                if ($totalProductIndex < 5) {
                    // Expired (between 30 and 90 days ago)
                    $fechaExpiracion = Carbon::now()->subDays(rand(30, 90));
                    $estadoLote = 'activo';
                } elseif ($totalProductIndex >= 5 && $totalProductIndex < 10) {
                    // Expiring soon (between 5 and 25 days in the future)
                    $fechaExpiracion = Carbon::now()->addDays(rand(5, 25));
                    $estadoLote = 'activo';
                } else {
                    // Normal (between 1 and 3 years in the future)
                    $fechaExpiracion = Carbon::now()->addYears(rand(1, 3))->addDays(rand(1, 305));
                    $estadoLote = 'activo';
                }

                // Create Lote
                $lote = Lote::create([
                    'producto_id' => $product->id,
                    'proveedor_id' => $supplier->id,
                    'cantidad_inicial' => $cantidad,
                    'cantidad_actual' => $cantidad,
                    'fecha_ingreso' => Carbon::now()->subDays(rand(1, 10)),
                    'fecha_expiracion' => $fechaExpiracion->toDateString(),
                    'estado' => $estadoLote,
                ]);

                // Create Stock record (since it was 0, it becomes $cantidad)
                $product->stockActual()->updateOrCreate([
                    'producto_id' => $product->id,
                ], [
                    'stock' => $cantidad,
                    'min' => 5,
                    'max' => 100,
                ]);

                // Create MovimientoInventario
                MovimientoInventario::create([
                    'producto_id' => $product->id,
                    'tipo' => 'ingreso_compra',
                    'cantidad' => $cantidad,
                    'costo_unitario' => $product->costo,
                    'saldo_cantidad' => $cantidad,
                    'saldo_costo_promedio' => $product->costo,
                    'referencia_type' => Compra::class,
                    'referencia_id' => $compra->id,
                    'motivo' => "Compra #{$compra->id} (Población inicial)",
                    'lote_id' => $lote->id,
                    'user_id' => $admin->id,
                ]);

                // Create DetalleCompra
                $compra->detalleCompras()->create([
                    'producto_id' => $product->id,
                    'cantidad' => $cantidad,
                    'sub_costo' => $subCosto,
                    'lote_id' => $lote->id,
                ]);
            }

            // Update Compra total cost
            $compra->update(['costo' => $totalCompra]);
        }

        // 7. PROMOTIONS
        $promocionesData = [
            [
                'nombre_promo' => 'Black Friday Licores',
                'codigo_promo' => 'BLACKFRIDAY',
                'descuento' => 15.00,
                'tipo_descuento' => 'porcentaje',
                'fecha_inicio' => Carbon::now()->subDays(2),
                'fecha_fin' => Carbon::now()->addDays(10),
            ],
            [
                'nombre_promo' => 'Combo Fin de Semana',
                'codigo_promo' => 'FINDE',
                'descuento' => 20.00,
                'tipo_descuento' => 'monto',
                'fecha_inicio' => Carbon::now()->subDays(1),
                'fecha_fin' => Carbon::now()->addDays(5),
            ],
            [
                'nombre_promo' => 'Promo de Invierno',
                'codigo_promo' => 'INVIERNO',
                'descuento' => 10.00,
                'tipo_descuento' => 'porcentaje',
                'fecha_inicio' => Carbon::now()->subDays(5),
                'fecha_fin' => Carbon::now()->addDays(30),
            ],
            [
                'nombre_promo' => 'Aniversario Vintage',
                'codigo_promo' => 'ANIVERSARIO',
                'descuento' => 20.00,
                'tipo_descuento' => 'porcentaje',
                'fecha_inicio' => Carbon::now()->subDays(3),
                'fecha_fin' => Carbon::now()->addDays(15),
            ],
            [
                'nombre_promo' => 'Descuento Express',
                'codigo_promo' => 'EXPRESS',
                'descuento' => 15.00,
                'tipo_descuento' => 'monto',
                'fecha_inicio' => Carbon::now(),
                'fecha_fin' => Carbon::now()->addDays(3),
            ],
        ];

        foreach ($promocionesData as $promoData) {
            $promo = Promocion::create($promoData);

            // Link 4 random products to this promotion
            $randomProducts = collect($productos)->random(4);
            foreach ($randomProducts as $randProd) {
                DetallePromo::create([
                    'producto_id' => $randProd->id,
                    'promocion_id' => $promo->id,
                ]);
            }
        }

        // 8. SELLERS (VENDEDORES)
        $vendedoresData = [
            ['name' => 'Carlos Mendoza', 'email' => 'carlos@gmail.com', 'ci' => '6543210', 'phone' => '71234567', 'password' => Hash::make('123456789')],
            ['name' => 'Sofía Rojas', 'email' => 'sofia@gmail.com', 'ci' => '6543211', 'phone' => '71234568', 'password' => Hash::make('123456789')],
            ['name' => 'Javier Flores', 'email' => 'javier@gmail.com', 'ci' => '6543212', 'phone' => '71234569', 'password' => Hash::make('123456789')],
            ['name' => 'Laura Torres', 'email' => 'laura@gmail.com', 'ci' => '6543213', 'phone' => '71234570', 'password' => Hash::make('123456789')],
        ];
        $vendedores = [$vendedor, $admin]; // Default vendedor and propietario also included
        foreach ($vendedoresData as $vData) {
            $u = User::updateOrCreate(['email' => $vData['email']], $vData);
            $u->syncRoles(['vendedor']);
            $vendedores[] = $u;
        }

        // 9. HISTORICAL SALES (VENTAS 2025 - HASTA LA FECHA JUNIO 2026)
        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::create(2026, 6, 29);
        $clients = User::role('cliente')->get();

        $currentDate = clone $startDate;
        while ($currentDate->lte($endDate)) {
            $year = $currentDate->year;
            $month = $currentDate->month;
            
            // Generate 40 to 60 sales in this month
            $salesCount = rand(40, 60);
            for ($s = 0; $s < $salesCount; $s++) {
                // Random day in the month
                $day = rand(1, $currentDate->daysInMonth);
                if ($year == 2026 && $month == 6 && $day > 29) {
                    $day = rand(1, 29);
                }
                
                $saleDate = Carbon::create($year, $month, $day, rand(8, 22), rand(0, 59), rand(0, 59));
                
                // Pick random seller and client
                $seller = $vendedores[array_rand($vendedores)];
                $client = $clients->random();
                
                // Choose 1 to 3 random products
                $saleProducts = collect($productos)->random(rand(1, 3));
                
                $detallesVenta = [];
                $totalOriginal = 0;
                
                foreach ($saleProducts as $product) {
                    $disponible = Lote::where('producto_id', $product->id)
                        ->where('estado', 'activo')
                        ->where('cantidad_actual', '>', 0)
                        ->sum('cantidad_actual');
                    
                    if ($disponible <= 0) {
                        continue;
                    }
                    
                    $cantidad = min(rand(1, 3), $disponible);
                    $subtotal = $product->precio_venta * $cantidad;
                    
                    $detallesVenta[] = [
                        'product' => $product,
                        'cantidad' => $cantidad,
                        'precio' => $product->precio_venta,
                        'subtotal' => $subtotal,
                    ];
                    
                    $totalOriginal += $subtotal;
                }
                
                if (empty($detallesVenta)) {
                    continue; // Skip if no products have stock
                }
                
                // Apply promo with 20% probability
                $promoApplied = null;
                $totalFinal = $totalOriginal;
                if (rand(1, 100) <= 20) {
                    $promo = Promocion::inRandomOrder()->first();
                    if ($promo) {
                        $promoApplied = $promo;
                        if ($promo->tipo_descuento === 'porcentaje') {
                            $descuento = $totalOriginal * ($promo->descuento / 100);
                        } else {
                            $descuento = $promo->descuento;
                        }
                        $totalFinal = max(0, $totalOriginal - $descuento);
                    }
                }
                
                // Payment method
                $r = rand(1, 100);
                if ($r <= 45) {
                    $tipoPago = 'efectivo';
                } elseif ($r <= 65) {
                    $tipoPago = 'tarjeta';
                } elseif ($r <= 85) {
                    $tipoPago = 'qr';
                } elseif ($r <= 95) {
                    $tipoPago = 'credito';
                } else {
                    $tipoPago = 'multiple';
                }
                
                $nroCuotas = ($tipoPago === 'credito') ? rand(2, 6) : 1;
                
                // Create Venta
                $venta = Venta::create([
                    'monto_pagado' => 0, // Will update below
                    'cod_descuento' => $promoApplied ? $promoApplied->codigo_promo : null,
                    'monto_original' => $totalOriginal,
                    'monto_final' => $totalFinal,
                    'nro_cuotas' => $nroCuotas,
                    'tipo_pago' => $tipoPago,
                    'cliente_id' => $client->id,
                    'promocion_id' => $promoApplied ? $promoApplied->id : null,
                    'user_id' => $seller->id,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
                
                // Create Details and deduct stock
                foreach ($detallesVenta as $det) {
                    $product = $det['product'];
                    $cantidad = $det['cantidad'];
                    
                    $venta->detalleVentas()->create([
                        'cantidad' => $cantidad,
                        'precio_original' => $det['precio'],
                        'descuento' => 0,
                        'precio_u_final' => $det['precio'],
                        'subtotal' => $det['subtotal'],
                        'producto_id' => $product->id,
                        'created_at' => $saleDate,
                        'updated_at' => $saleDate,
                    ]);
                    
                    // Deduct stock from lots (FIFO)
                    $restante = $cantidad;
                    $lotes = Lote::where('producto_id', $product->id)
                        ->where('estado', 'activo')
                        ->where('cantidad_actual', '>', 0)
                        ->orderBy('fecha_expiracion')
                        ->get();

                    foreach ($lotes as $lote) {
                        if ($restante <= 0) break;
                        
                        $descontar = min($lote->cantidad_actual, $restante);
                        $lote->decrement('cantidad_actual', $descontar);
                        $restante -= $descontar;

                        // Create MovimientoInventario
                        MovimientoInventario::create([
                            'producto_id' => $product->id,
                            'tipo' => 'salida_venta',
                            'cantidad' => $descontar,
                            'costo_unitario' => $product->costo,
                            'saldo_cantidad' => Lote::where('producto_id', $product->id)->sum('cantidad_actual'),
                            'saldo_costo_promedio' => $product->costo,
                            'referencia_type' => Venta::class,
                            'referencia_id' => $venta->id,
                            'motivo' => "Venta #{$venta->id} (Población histórica)",
                            'lote_id' => $lote->id,
                            'user_id' => $seller->id,
                            'created_at' => $saleDate,
                            'updated_at' => $saleDate,
                        ]);
                    }

                    // Update Stock table
                    $product->stockActual()->decrement('stock', $cantidad);
                }
                
                // Set payment methods and cuotas
                if ($tipoPago === 'credito') {
                    // Credit installments
                    $subMontoCuota = round($totalFinal / $nroCuotas, 2);
                    $montoPagadoAcumulado = 0;
                    
                    for ($i = 1; $i <= $nroCuotas; $i++) {
                        $montoCuota = ($i === $nroCuotas) ? ($totalFinal - ($subMontoCuota * ($nroCuotas - 1))) : $subMontoCuota;
                        $fechaVencimientoCuota = (clone $saleDate)->addMonths($i);
                        $esPagada = $fechaVencimientoCuota->lt($endDate);
                        
                        $venta->ventaCuotas()->create([
                            'sub_monto' => $montoCuota,
                            'nro_cuota' => $i,
                            'estado' => $esPagada ? 'pagado' : 'pendiente',
                            'fecha_pago' => $esPagada ? $fechaVencimientoCuota : null,
                            'created_at' => $saleDate,
                            'updated_at' => $saleDate,
                        ]);
                        
                        if ($esPagada) {
                            $montoPagadoAcumulado += $montoCuota;
                        }
                    }
                    
                    $venta->update(['monto_pagado' => $montoPagadoAcumulado]);
                    
                    $venta->metodoPagos()->create([
                        'tipo_pago' => 'credito',
                        'monto' => $totalFinal,
                        'created_at' => $saleDate,
                        'updated_at' => $saleDate,
                    ]);
                    
                } elseif ($tipoPago === 'multiple') {
                    $monto1 = round($totalFinal / 2, 2);
                    $monto2 = $totalFinal - $monto1;
                    $metodo2 = (rand(1, 2) === 1) ? 'tarjeta' : 'qr';
                    
                    $venta->metodoPagos()->create([
                        'tipo_pago' => 'efectivo',
                        'monto' => $monto1,
                        'created_at' => $saleDate,
                        'updated_at' => $saleDate,
                    ]);
                    
                    $venta->metodoPagos()->create([
                        'tipo_pago' => $metodo2,
                        'monto' => $monto2,
                        'created_at' => $saleDate,
                        'updated_at' => $saleDate,
                    ]);
                    
                    $venta->update(['monto_pagado' => $totalFinal]);
                    
                } else {
                    $venta->metodoPagos()->create([
                        'tipo_pago' => $tipoPago,
                        'monto' => $totalFinal,
                        'created_at' => $saleDate,
                        'updated_at' => $saleDate,
                    ]);
                    
                    $venta->update(['monto_pagado' => $totalFinal]);
                }
            }
            
            $currentDate->addMonth();
        }

        // 10. MENU ITEMS & OTHER SEEDERS
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

