<?php

namespace Database\Seeders;

use App\Models\GrupoProducto;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductosDemoSeeder extends Seeder
{
    /**
     * Ejecutar solo manualmente:
     * php artisan db:seed --class=ProductosDemoSeeder
     *
     * Seguro para repetir: nunca sobrescribe productos existentes.
     */
    public function run(): void
    {
        // Nombre, grupo, precio compra, precio venta, unidades.
        $datos = [
            ['Papas fritas clásicas 45 g (Demo)', 'Snacks', 3.50, 5.00, 35],
            ['Papas fritas picantes 45 g (Demo)', 'Snacks', 3.80, 5.50, 25],
            ['Chizitos de queso 70 g (Demo)', 'Snacks', 4.00, 6.00, 22],
            ['Maní salado 100 g (Demo)', 'Snacks', 4.50, 7.00, 40],
            ['Palomitas de maíz 80 g (Demo)', 'Snacks', 3.50, 6.00, 24],
            ['Galletas de chocolate 90 g (Demo)', 'Galletas', 4.00, 6.50, 32],
            ['Galletas de vainilla 100 g (Demo)', 'Galletas', 3.00, 5.00, 27],
            ['Galletas integrales 120 g (Demo)', 'Galletas', 5.00, 8.00, 15],
            ['Wafer de frutilla 40 g (Demo)', 'Galletas', 2.50, 4.00, 36],
            ['Barra de cereal 30 g (Demo)', 'Snacks', 3.50, 5.50, 20],
            ['Gaseosa cola 500 ml (Demo)', 'Bebidas', 4.50, 7.00, 48],
            ['Gaseosa naranja 500 ml (Demo)', 'Bebidas', 4.50, 7.00, 30],
            ['Agua mineral 600 ml (Demo)', 'Bebidas', 2.50, 4.00, 60],
            ['Jugo de durazno 200 ml (Demo)', 'Bebidas', 3.50, 5.00, 45],
            ['Bebida energética 250 ml (Demo)', 'Bebidas', 6.50, 10.00, 18],
            ['Té helado de limón 500 ml (Demo)', 'Bebidas', 4.00, 6.50, 20],
            ['Chocolate en barra 45 g (Demo)', 'Dulces', 3.50, 6.00, 29],
            ['Caramelos surtidos 100 g (Demo)', 'Dulces', 4.00, 6.50, 38],
            ['Gomitas frutales 80 g (Demo)', 'Dulces', 4.50, 7.00, 26],
            ['Chicle de menta 10 unidades (Demo)', 'Dulces', 2.00, 3.50, 50],
        ];

        DB::transaction(function () use ($datos) {
            // Mismo bloqueo que ProductoController: evita códigos duplicados.
            DB::select('SELECT pg_advisory_xact_lock(894162)');

            $ultimo = DB::selectOne(
                "SELECT COALESCE(MAX(CAST(SUBSTRING(codigo FROM 4) AS BIGINT)), 0) AS numero
                 FROM productos WHERE codigo ~ '^PRD[0-9]+$'"
            );
            $numero = (int) $ultimo->numero;
            $creados = 0;

            foreach ($datos as [$nombre, $grupo, $compra, $venta, $stock]) {
                // No duplica ni actualiza los productos demo ya registrados.
                if (Producto::whereRaw('LOWER(nombre) = LOWER(?)', [$nombre])->exists()) {
                    continue;
                }

                $grupoId = GrupoProducto::whereRaw('LOWER(nombre) = LOWER(?)', [$grupo])->value('id');
                if (!$grupoId) {
                    $grupoId = GrupoProducto::create(['nombre' => $grupo, 'estado' => true])->id;
                }

                $numero++;
                Producto::create([
                    'codigo' => 'PRD'.str_pad((string) $numero, 6, '0', STR_PAD_LEFT),
                    'nombre' => $nombre,
                    'tipo' => 'PRODUCTO',
                    'grupo_id' => $grupoId,
                    'marca_id' => null,
                    'unidad_medida_id' => null,
                    'procedencia' => 'Producto de prueba',
                    'sucursal' => 'Sistema Snack',
                    'orden_menu' => 999,
                    'precio_compra' => $compra,
                    'precio_venta' => $venta,
                    'stock_actual' => $stock,
                    'stock_minimo' => 5,
                    'estado' => true,
                    'observaciones' => 'Registro de demostración. Precios y existencias ficticios.',
                    'imagen' => null,
                ]);
                $creados++;
            }
            $this->command?->info("Productos nuevos agregados: {$creados}. Los anteriores no se modificaron.");
        });
    }
}
