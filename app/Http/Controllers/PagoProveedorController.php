<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PagoProveedorController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->only(['sucursal','nro_orden','nro_factura','proveedor_id','tipo_pago','estado_pago','fecha_inicio','fecha_fin','buscar']);
        $abonos = DB::table('pagos_proveedores')
            ->select('recepcion_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN metodo_pago = 'EFECTIVO' THEN monto ELSE 0 END), 0) AS abono_efectivo")
            ->selectRaw("COALESCE(SUM(CASE WHEN metodo_pago <> 'EFECTIVO' THEN monto ELSE 0 END), 0) AS abono_transferencia")
            ->selectRaw("MAX(fecha_pago) AS ultima_fecha_pago")
            ->groupBy('recepcion_id');
        $query = DB::table('compra_recepciones as c')
            ->leftJoinSub($abonos, 'a', fn ($join) => $join->on('a.recepcion_id', '=', 'c.id'))
            ->join('proveedores as p', 'p.id', '=', 'c.proveedor_id')
            ->select('c.id','c.codigo','c.recibo_factura','c.fecha','c.sucursal','c.tipo_pago',
                'c.metodo_pago','c.total','c.a_cuenta','c.saldo','c.estado','p.nombre as proveedor')
            ->selectRaw("CASE WHEN c.metodo_pago = 'EFECTIVO' THEN (c.a_cuenta - (c.total - c.saldo - COALESCE(a.abono_efectivo, 0) - COALESCE(a.abono_transferencia, 0))) ELSE 0 END + COALESCE(a.abono_efectivo, 0) AS efectivo_total")
            ->selectRaw("CASE WHEN c.metodo_pago <> 'EFECTIVO' THEN (c.a_cuenta - (c.total - c.saldo - COALESCE(a.abono_efectivo, 0) - COALESCE(a.abono_transferencia, 0))) ELSE 0 END + COALESCE(a.abono_transferencia, 0) AS transferencia_total")
            ->selectRaw("COALESCE(a.ultima_fecha_pago, c.created_at) AS fecha_pago")
            ->when($request->filled('sucursal'), fn ($q) => $q->where('c.sucursal', $request->sucursal))
            ->when($request->filled('nro_orden'), fn ($q) => $q->where('c.codigo','ilike','%'.$request->nro_orden.'%'))
            ->when($request->filled('nro_factura'), fn ($q) => $q->where('c.recibo_factura','ilike','%'.$request->nro_factura.'%'))
            ->when($request->filled('proveedor_id'), fn ($q) => $q->where('c.proveedor_id', $request->proveedor_id))
            ->when($request->filled('tipo_pago'), fn ($q) => $q->where('c.tipo_pago', $request->tipo_pago))
            ->when($request->filled('estado_pago'), function ($q) use ($request) {
                return $request->estado_pago === 'PAGADO' ? $q->where('c.saldo', '<=', 0) : $q->where('c.saldo', '>', 0);
            })
            ->when($request->filled('fecha_inicio'), fn ($q) => $q->whereDate('c.fecha','>=',$request->fecha_inicio))
            ->when($request->filled('fecha_fin'), fn ($q) => $q->whereDate('c.fecha','<=',$request->fecha_fin))
            ->when($request->filled('buscar'), fn ($q) => $q->where(function ($s) use ($request) {
                $b = '%'.$request->buscar.'%';
                $s->where('c.codigo','ilike',$b)->orWhere('c.recibo_factura','ilike',$b)->orWhere('p.nombre','ilike',$b);
            }))
            ->orderByDesc('c.id');
        return Inertia::render('Compras/PagosProveedores', [
            'compras' => $query->paginate(10)->withQueryString(),
            'proveedores' => DB::table('proveedores')->orderBy('nombre')->get(['id','nombre']),
            'sucursales' => DB::table('compra_recepciones')->distinct()->orderBy('sucursal')->pluck('sucursal'),
            'filtros' => $filtros,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'recepcion_id' => ['required', 'integer', Rule::exists('compra_recepciones','id')],
            'monto' => ['required','numeric','gt:0','decimal:0,2'],
            'metodo_pago' => ['required', Rule::in(['EFECTIVO','QR','TRANSFERENCIA'])],
            'observaciones' => ['nullable','string','max:1000'],
        ]);
        DB::transaction(function () use ($datos, $request) {
            $compra = DB::table('compra_recepciones')->where('id', $datos['recepcion_id'])
                ->lockForUpdate()->first();
            if (!$compra) {
                throw ValidationException::withMessages(['recepcion_id' => 'Compra no encontrada.']);
            }
            $centavos = (int) round($datos['monto'] * 100);
            $saldo = (int) round($compra->saldo * 100);
            if ($centavos > $saldo) {
                throw ValidationException::withMessages(['monto' => 'El pago supera el saldo pendiente.']);
            }
            DB::table('pagos_proveedores')->insert([
                'recepcion_id' => $compra->id,
                'fecha_pago' => now(),
                'monto' => number_format($centavos / 100, 2, '.', ''),
                'metodo_pago' => $datos['metodo_pago'],
                'observaciones' => $datos['observaciones'] ?? null,
                'usuario_id' => $request->user()?->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $saldoNuevo = $saldo - $centavos;
            DB::table('compra_recepciones')->where('id', $compra->id)->update([
                'a_cuenta' => number_format(((int) round($compra->a_cuenta * 100) + $centavos) / 100, 2, '.', ''),
                'saldo' => number_format($saldoNuevo / 100, 2, '.', ''),
                'estado' => $saldoNuevo === 0 ? 'REC-PAGADO' : 'REC-PENDIENTE',
                'updated_at' => now(),
            ]);
        });
        return redirect()->back()->with('success', 'Pago registrado correctamente.');
    }
}
