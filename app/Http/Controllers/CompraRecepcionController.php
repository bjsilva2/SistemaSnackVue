<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CompraRecepcionController extends Controller
{
    public function index()
    {
        return Inertia::render('Compras/Recepciones', [
            'recepciones' => DB::table('compra_recepciones as r')
                ->join('proveedores as p', 'p.id', '=', 'r.proveedor_id')
                ->select('r.id', 'r.codigo', 'r.fecha', 'r.total', 'p.nombre as proveedor')
                ->orderByDesc('r.id')->paginate(10),
            'proveedores' => DB::table('proveedores')->where('estado', true)
                ->orderBy('nombre')->get(['id', 'nombre']),
            'productos' => Producto::where('estado', true)->where('tipo', 'PRODUCTO')
                ->orderBy('nombre')->get(['id', 'nombre', 'codigo', 'stock_actual', 'precio_compra']),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'proveedor_id' => ['required', Rule::exists('proveedores', 'id')->where('estado', true)],
            'fecha' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'distinct', Rule::exists('productos', 'id')->where('estado', true)->where('tipo', 'PRODUCTO')],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'items.*.precio_compra' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        DB::transaction(function () use ($datos) {
            // Bloquea productos en orden fijo para evitar carreras y deadlocks.
            $ids = collect($datos['items'])->pluck('producto_id')->sort()->values()->all();
            $productos = Producto::whereIn('id', $ids)->orderBy('id')
                ->lockForUpdate()->get()->keyBy('id');

            $totalCentavos = 0;
            foreach ($datos['items'] as $item) {
                $centavos = (int) round((float) $item['precio_compra'] * 100);
                $cantidadCentesimas = (int) round((float) $item['cantidad'] * 100);
                $totalCentavos += (int) round($centavos * $cantidadCentesimas / 100);
            }

            $id = (int) DB::selectOne(
                "SELECT nextval(pg_get_serial_sequence('compra_recepciones', 'id')) AS numero"
            )->numero;

            DB::table('compra_recepciones')->insert([
                'id' => $id,
                'codigo' => 'COM'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
                'proveedor_id' => $datos['proveedor_id'],
                'fecha' => $datos['fecha'],
                'total' => number_format($totalCentavos / 100, 2, '.', ''),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($datos['items'] as $item) {
                $producto = $productos->get($item['producto_id']);
                if (!$producto || !$producto->estado || $producto->tipo !== 'PRODUCTO') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => 'Uno de los productos ya no está disponible.',
                    ]);
                }
                $cantidad = (float) $item['cantidad'];
                $precio = (float) $item['precio_compra'];
                DB::table('compra_recepcion_items')->insert([
                    'recepcion_id' => $id, 'producto_id' => $producto->id,
                    'cantidad' => $cantidad, 'precio_compra' => $precio,
                    'subtotal' => number_format(round($cantidad * $precio, 2), 2, '.', ''),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $producto->stock_actual = round((float) $producto->stock_actual + $cantidad, 2);
                $producto->precio_compra = $precio;
                $producto->save();
            }
        });

        return redirect()->back()->with('success', 'Recepción registrada y stock actualizado.');
    }
}
