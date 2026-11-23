<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReporteComprasController extends Controller
{
    private function filtros(Request $request): array
    {
        return $request->validate([
            'sucursal' => ['nullable', 'string', 'max:150'],
            'factura' => ['nullable', 'string', 'max:80'],
            'producto_id' => ['nullable', 'integer', 'exists:productos,id'],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);
    }

    private function compras(array $f)
    {
        return DB::table('compra_recepciones as r')
            ->when(!empty($f['sucursal']) && $f['sucursal'] !== 'TODAS',
                fn ($q) => $q->where('r.sucursal', $f['sucursal']))
            ->when(!empty($f['factura']),
                fn ($q) => $q->where('r.recibo_factura', 'ilike', '%'.$f['factura'].'%'))
            ->when(!empty($f['proveedor_id']),
                fn ($q) => $q->where('r.proveedor_id', $f['proveedor_id']))
            ->when(!empty($f['usuario_id']),
                fn ($q) => $q->where('r.solicitado_por', $f['usuario_id']))
            ->when(!empty($f['desde']),
                fn ($q) => $q->whereDate('r.fecha', '>=', $f['desde']))
            ->when(!empty($f['hasta']),
                fn ($q) => $q->whereDate('r.fecha', '<=', $f['hasta']))
            ->when(!empty($f['producto_id']), function ($q) use ($f) {
                $q->whereExists(function ($sub) use ($f) {
                    $sub->selectRaw('1')
                        ->from('compra_recepcion_items as ri')
                        ->whereColumn('ri.recepcion_id', 'r.id')
                        ->where('ri.producto_id', $f['producto_id']);
                });
            });
    }

    private function detalles(array $f)
    {
        $consulta = DB::table('compra_recepcion_items as i')
            ->join('compra_recepciones as r', 'r.id', '=', 'i.recepcion_id')
            ->join('proveedores as prov', 'prov.id', '=', 'r.proveedor_id')
            ->join('productos as prod', 'prod.id', '=', 'i.producto_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.solicitado_por')
            ->select([
                'i.id', 'r.id as compra_id', 'r.codigo as compra_codigo',
                'r.recibo_factura', 'r.fecha', 'r.created_at as fecha_recepcion',
                'prov.nombre as proveedor', 'u.name as usuario',
                'prod.nombre as producto', 'prod.codigo as producto_codigo',
                'i.cantidad', 'i.precio_compra', 'i.descuento',
                'i.subtotal', 'r.sucursal',
            ])
            ->when(!empty($f['sucursal']) && $f['sucursal'] !== 'TODAS',
                fn ($q) => $q->where('r.sucursal', $f['sucursal']))
            ->when(!empty($f['factura']),
                fn ($q) => $q->where('r.recibo_factura', 'ilike', '%'.$f['factura'].'%'))
            ->when(!empty($f['proveedor_id']),
                fn ($q) => $q->where('r.proveedor_id', $f['proveedor_id']))
            ->when(!empty($f['usuario_id']),
                fn ($q) => $q->where('r.solicitado_por', $f['usuario_id']))
            ->when(!empty($f['desde']),
                fn ($q) => $q->whereDate('r.fecha', '>=', $f['desde']))
            ->when(!empty($f['hasta']),
                fn ($q) => $q->whereDate('r.fecha', '<=', $f['hasta']))
            ->when(!empty($f['producto_id']),
                fn ($q) => $q->where('i.producto_id', $f['producto_id']));

        return $consulta;
    }

    public function index(Request $request)
    {
        $f = $this->filtros($request);
        $resumen = $this->compras($f)->selectRaw(
            'COUNT(*) AS cantidad_compras,
             COALESCE(SUM(r.total), 0) AS total_compras,
             COALESCE(SUM(r.descuento), 0) AS descuentos_generales'
        )->first();

        // Los descuentos por línea pertenecen a las mismas compras filtradas.
        $descuentosLineas = DB::table('compra_recepcion_items as i')
            ->whereIn('i.recepcion_id', $this->compras($f)->select('r.id'))
            ->when(!empty($f['producto_id']),
                fn ($q) => $q->where('i.producto_id', $f['producto_id']))
            ->sum('i.descuento');

        return Inertia::render('Reportes/Compras', [
            'compras' => $this->detalles($f)->orderByDesc('r.fecha')
                ->orderByDesc('r.id')->orderBy('i.id')
                ->paginate(20)->withQueryString(),
            'resumen' => [
                'cantidad_compras' => (int) $resumen->cantidad_compras,
                'total_compras' => $resumen->total_compras,
                'total_descuentos' => round((float) $resumen->descuentos_generales + (float) $descuentosLineas, 2),
            ],
            'filtros' => array_merge([
                'sucursal' => 'TODAS', 'factura' => '', 'producto_id' => '',
                'proveedor_id' => '', 'usuario_id' => '', 'desde' => '', 'hasta' => '',
            ], $f),
            'sucursales' => DB::table('compra_recepciones')->distinct()
                ->orderBy('sucursal')->pluck('sucursal'),
            'productos' => DB::table('productos')->orderBy('nombre')->get(['id', 'nombre']),
            'proveedores' => DB::table('proveedores')->orderBy('nombre')->get(['id', 'nombre']),
            'usuarios' => DB::table('users')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function exportar(Request $request)
    {
        $f = $this->filtros($request);
        $registros = $this->detalles($f)->orderByDesc('r.fecha')
            ->orderByDesc('r.id')->orderBy('i.id')->cursor();

        return response()->streamDownload(function () use ($registros) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Recibo/Factura', 'Usuario', 'Proveedor', 'Fecha compra',
                'Recepcion', 'Producto', 'Cantidad', 'Precio unitario Bs',
                'Descuento linea Bs', 'Total linea Bs',
            ], ';');
            foreach ($registros as $r) {
                fputcsv($out, [
                    $r->recibo_factura ?? $r->compra_codigo,
                    $r->usuario ?? 'Sin usuario', $r->proveedor,
                    $r->fecha, $r->fecha_recepcion, $r->producto,
                    $r->cantidad, $r->precio_compra, $r->descuento, $r->subtotal,
                ], ';');
            }
            fclose($out);
        }, 'reporte-compras-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
