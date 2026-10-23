<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CompraRecepcionController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->only(['recibo_factura', 'proveedor_id', 'fecha_inicio', 'fecha_fin', 'estado']);
        $query = DB::table('compra_recepciones as r')
            ->join('proveedores as p', 'p.id', '=', 'r.proveedor_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.solicitado_por')
            ->select('r.id', 'r.codigo', 'r.recibo_factura', 'r.fecha', 'r.total', 'r.estado',
                'r.sucursal', 'p.nombre as proveedor', 'u.name as solicitado_por')
            ->when($request->filled('recibo_factura'), fn ($q) =>
                $q->where('r.recibo_factura', 'ilike', '%'.$request->recibo_factura.'%'))
            ->when($request->filled('proveedor_id'), fn ($q) =>
                $q->where('r.proveedor_id', $request->proveedor_id))
            ->when($request->filled('fecha_inicio'), fn ($q) =>
                $q->whereDate('r.fecha', '>=', $request->fecha_inicio))
            ->when($request->filled('fecha_fin'), fn ($q) =>
                $q->whereDate('r.fecha', '<=', $request->fecha_fin))
            ->when($request->filled('estado'), fn ($q) =>
                $q->where('r.estado', $request->estado))
            ->orderByDesc('r.id');

        return Inertia::render('Compras/Recepciones', [
            'recepciones' => $query->paginate(10)->withQueryString(),
            'proveedores' => DB::table('proveedores')->where('estado', true)
                ->orderBy('nombre')->get(['id', 'nombre']),
            'productos' => Producto::where('estado', true)->where('tipo', 'PRODUCTO')
                ->orderBy('nombre')->get(['id', 'nombre', 'codigo', 'stock_actual', 'precio_compra', 'precio_venta']),
            'filtros' => $filtros,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'proveedor_id' => ['required', Rule::exists('proveedores', 'id')->where('estado', true)],
            'fecha' => ['required', 'date'],
            'sucursal' => ['required', 'string', 'max:150'],
            'recibo_factura' => ['nullable', 'string', 'max:80'],
            'descuento' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'tipo_pago' => ['required', Rule::in(['CONTADO', 'CREDITO'])],
            'dias_credito' => ['required', 'integer', 'min:0', 'max:3650'],
            'metodo_pago' => ['required', Rule::in(['EFECTIVO', 'QR', 'TRANSFERENCIA'])],
            'a_cuenta' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'efectivo' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'enviar_a_banco' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'distinct',
                Rule::exists('productos', 'id')->where('tipo', 'PRODUCTO')->where('estado', true)],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'items.*.precio_compra' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.descuento' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.pv' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'items.*.pv2' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);

        // Toda la operación es atómica: recepción, detalles y existencias.
        DB::transaction(function () use ($datos, $request) {
            $productos = Producto::whereIn('id', collect($datos['items'])->pluck('producto_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $lineas = [];
            $subtotalCentavos = 0;
            foreach ($datos['items'] as $item) {
                $producto = $productos->get($item['producto_id']);
                if (!$producto || !$producto->estado || $producto->tipo !== 'PRODUCTO') {
                    throw ValidationException::withMessages(['items' => 'Producto no disponible.']);
                }
                $cant = (int) round($item['cantidad'] * 100);
                $pu = (int) round($item['precio_compra'] * 100);
                $descuento = (int) round($item['descuento'] * 100);
                $bruto = (int) round($cant * $pu / 100);
                if ($descuento > $bruto) {
                    throw ValidationException::withMessages(['items' => 'El descuento de una línea supera su subtotal.']);
                }
                $neto = $bruto - $descuento;
                $subtotalCentavos += $neto;
                $lineas[] = compact('producto', 'item', 'cant', 'pu', 'descuento', 'bruto', 'neto');
            }

            $descuentoGeneral = (int) round($datos['descuento'] * 100);
            $totalCentavos = $subtotalCentavos - $descuentoGeneral;
            $acuenta = (int) round($datos['a_cuenta'] * 100);
            if ($totalCentavos < 0 || $acuenta > $totalCentavos) {
                throw ValidationException::withMessages(['descuento' => 'Revisa el descuento y el pago a cuenta.']);
            }
            if ($datos['tipo_pago'] === 'CONTADO' && $acuenta !== $totalCentavos) {
                throw ValidationException::withMessages(['a_cuenta' => 'En pago al contado debes cubrir el total.']);
            }
            if ($datos['tipo_pago'] === 'CREDITO' && $datos['dias_credito'] < 1) {
                throw ValidationException::withMessages(['dias_credito' => 'Indica los días de crédito.']);
            }
            if ($datos['metodo_pago'] === 'EFECTIVO' && (int) round($datos['efectivo'] * 100) < $acuenta) {
                throw ValidationException::withMessages(['efectivo' => 'El efectivo no cubre el pago a cuenta.']);
            }

            $id = (int) DB::selectOne(
                "SELECT nextval(pg_get_serial_sequence('compra_recepciones', 'id')) AS numero"
            )->numero;
            $dinero = fn (int $centavos) => number_format($centavos / 100, 2, '.', '');
            DB::table('compra_recepciones')->insert([
                'id' => $id,
                'codigo' => 'COM'.str_pad((string) $id, 6, '0', STR_PAD_LEFT),
                'proveedor_id' => $datos['proveedor_id'],
                'fecha' => $datos['fecha'],
                'sucursal' => $datos['sucursal'],
                'recibo_factura' => $datos['recibo_factura'] ?? null,
                'subtotal' => $dinero($subtotalCentavos),
                'descuento' => $dinero($descuentoGeneral),
                'total' => $dinero($totalCentavos),
                'a_cuenta' => $dinero($acuenta),
                'saldo' => $dinero($totalCentavos - $acuenta),
                'tipo_pago' => $datos['tipo_pago'],
                'dias_credito' => $datos['tipo_pago'] === 'CREDITO' ? $datos['dias_credito'] : 0,
                'metodo_pago' => $datos['metodo_pago'],
                'efectivo' => $dinero((int) round($datos['efectivo'] * 100)),
                'enviar_a_banco' => $datos['enviar_a_banco'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
                'estado' => $totalCentavos === $acuenta ? 'REC-PAGADO' : 'REC-PENDIENTE',
                'solicitado_por' => $request->user()?->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($lineas as $linea) {
                $item = $linea['item'];
                $producto = $linea['producto'];
                $cant = $linea['cant'];
                $precio = $linea['pu'];
                $ganancia = $precio > 0 ? round(($item['pv'] * 100 / $precio - 1) * 100, 2) : 0;
                DB::table('compra_recepcion_items')->insert([
                    'recepcion_id' => $id, 'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'], 'precio_compra' => $item['precio_compra'],
                    'subtotal' => $dinero($linea['neto']),
                    'pu_desc' => $dinero((int) round($linea['neto'] * 100 / $cant)),
                    'descuento' => $dinero($linea['descuento']),
                    'sub_desc' => $dinero($linea['neto']),
                    'ganancia' => $ganancia,
                    'pv' => $item['pv'], 'pv2' => $item['pv2'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $producto->stock_actual = round((float) $producto->stock_actual + $item['cantidad'], 2);
                $producto->precio_compra = $item['precio_compra'];
                $producto->precio_venta = $item['pv'];
                $producto->save();
            }
        });

        return redirect()->back()->with('success', 'Compra recibida y stock actualizado correctamente.');
    }
}
