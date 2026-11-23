<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReporteVentasController extends Controller
{
    private function filtros(Request $request): array
    {
        return $request->validate([
            'sucursal' => ['nullable', 'in:TODAS,Sistema Snack'],
            'cliente' => ['nullable', 'string', 'max:150'],
            'cajero' => ['nullable', 'integer', 'exists:users,id'],
            'estado' => ['nullable', Rule::in(['TODOS', 'COBRADO', 'PENDIENTE'])],
            'metodo' => ['nullable', Rule::in(['TODOS', 'EFECTIVO', 'QR'])],
            'tipo' => ['nullable', Rule::in(['TODOS', 'MESA', 'LLEVAR', 'YANGO'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);
    }

    private function consulta(array $f)
    {
        return DB::table('pedidos as p')
            ->join('users as u', 'u.id', '=', 'p.usuario_id')
            ->leftJoin('clientes as cli', 'cli.id', '=', 'p.cliente_id')
            ->leftJoin('cajas as caja', 'caja.id', '=', 'p.caja_id')
            ->select([
                'p.id', 'p.codigo', 'u.name as usuario', 'p.created_at as fecha_venta',
                'p.cobrado_at', 'p.nombre_cliente', 'cli.nombre_completo as cliente_registrado',
                'cli.ci_nit', 'p.modalidad', 'p.metodo_pago', 'p.total', 'p.estado',
                'caja.sucursal',
            ])
            ->when(($f['sucursal'] ?? 'TODAS') !== 'TODAS', function ($q) use ($f) {
                $q->where(function ($q) use ($f) {
                    $q->where('caja.sucursal', $f['sucursal'])
                        ->orWhere(function ($q) {
                            $q->whereNull('p.caja_id')->where('p.estado', '!=', 'COBRADO');
                        });
                });
            })
            ->when(!empty($f['cliente']), function ($q) use ($f) {
                $q->where(function ($q) use ($f) {
                    $patron = '%'.$f['cliente'].'%';
                    $q->where('p.nombre_cliente', 'ilike', $patron)
                        ->orWhere('cli.nombre_completo', 'ilike', $patron)
                        ->orWhere('cli.ci_nit', 'ilike', $patron);
                });
            })
            ->when(!empty($f['cajero']), fn ($q) => $q->where('p.usuario_id', $f['cajero']))
            ->when(($f['estado'] ?? 'TODOS') === 'COBRADO', fn ($q) => $q->where('p.estado', 'COBRADO'))
            ->when(($f['estado'] ?? 'TODOS') === 'PENDIENTE', fn ($q) => $q->where('p.estado', '!=', 'COBRADO'))
            ->when(($f['metodo'] ?? 'TODOS') !== 'TODOS', fn ($q) => $q->where('p.metodo_pago', $f['metodo']))
            ->when(($f['tipo'] ?? 'TODOS') !== 'TODOS', fn ($q) => $q->where('p.modalidad', $f['tipo']))
            ->when(!empty($f['desde']), fn ($q) => $q->whereDate('p.created_at', '>=', $f['desde']))
            ->when(!empty($f['hasta']), fn ($q) => $q->whereDate('p.created_at', '<=', $f['hasta']));
    }

    public function index(Request $request)
    {
        $f = $this->filtros($request);
        // No se presumen ventas a crédito ni descuentos: el módulo actual solo registra efectivo o QR.
        $totales = (clone $this->consulta($f))->select([])->selectRaw(
            "COALESCE(SUM(CASE WHEN p.estado = 'COBRADO' THEN p.total ELSE 0 END), 0) as total_ventas,
             COALESCE(SUM(CASE WHEN p.estado != 'COBRADO' THEN p.total ELSE 0 END), 0) as pendiente,
             COUNT(*) as registros"
        )->first();

        $ventas = $this->consulta($f)->orderByDesc('p.created_at')->orderByDesc('p.id')
            ->paginate(20)->withQueryString();

        return Inertia::render('Reportes/Ventas', [
            'ventas' => $ventas,
            'totales' => $totales,
            'filtros' => array_merge([
                'sucursal' => 'TODAS', 'cliente' => '', 'cajero' => '',
                'estado' => 'TODOS', 'metodo' => 'TODOS', 'tipo' => 'TODOS',
                'desde' => '', 'hasta' => '',
            ], $f),
            'cajeros' => DB::table('users')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function exportar(Request $request)
    {
        $f = $this->filtros($request);
        $ventas = $this->consulta($f)->orderByDesc('p.created_at')->orderByDesc('p.id')->get();
        return response()->streamDownload(function () use ($ventas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Codigo', 'Usuario', 'Fecha venta', 'Cliente', 'CI/NIT', 'Tipo pedido', 'Metodo pago', 'Total Bs', 'Estado'], ';');
            foreach ($ventas as $v) {
                fputcsv($out, [
                    $v->codigo, $v->usuario, $v->fecha_venta,
                    $v->cliente_registrado ?: ($v->nombre_cliente ?: 'Sin nombre'), $v->ci_nit ?? '',
                    $v->modalidad, $v->metodo_pago ?? '',
                    number_format((float) $v->total, 2, '.', ''),
                    $v->estado === 'COBRADO' ? 'VALIDA' : 'PENDIENTE',
                ], ';');
            }
            fclose($out);
        }, 'reporte-ventas-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
