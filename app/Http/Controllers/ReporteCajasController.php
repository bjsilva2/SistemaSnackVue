<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReporteCajasController extends Controller
{
    private function filtros(Request $request): array
    {
        return $request->validate([
            'sucursal' => ['nullable', 'string', 'max:150'],
            'codigo' => ['nullable', 'integer', 'min:1'],
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'estado' => ['nullable', 'in:TODOS,ABIERTO,CERRADO'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);
    }

    private function consulta(array $f)
    {
        return DB::table('cajas as c')
            ->join('users as u', 'u.id', '=', 'c.usuario_id')
            ->select('c.id', 'u.name as usuario', 'c.sucursal', 'c.fecha_apertura',
                'c.fecha_cierre', 'c.monto_inicial', 'c.total_ventas', 'c.ingresos',
                'c.egresos', 'c.saldo', 'c.estado')
            ->when(!empty($f['sucursal']) && $f['sucursal'] !== 'TODAS',
                fn ($q) => $q->where('c.sucursal', $f['sucursal']))
            ->when(!empty($f['codigo']), fn ($q) => $q->where('c.id', $f['codigo']))
            ->when(!empty($f['usuario_id']), fn ($q) => $q->where('c.usuario_id', $f['usuario_id']))
            ->when(!empty($f['estado']) && $f['estado'] !== 'TODOS',
                fn ($q) => $q->where('c.estado', $f['estado']))
            ->when(!empty($f['desde']), fn ($q) => $q->whereDate('c.fecha_apertura', '>=', $f['desde']))
            ->when(!empty($f['hasta']), fn ($q) => $q->whereDate('c.fecha_apertura', '<=', $f['hasta']));
    }

    public function index(Request $request)
    {
        $f = $this->filtros($request);
        return Inertia::render('Reportes/Cajas', [
            'cajas' => $this->consulta($f)->orderByDesc('c.id')->paginate(20)->withQueryString(),
            'filtros' => array_merge([
                'sucursal' => 'TODAS', 'codigo' => '', 'usuario_id' => '',
                'estado' => 'TODOS', 'desde' => '', 'hasta' => '',
            ], $f),
            'usuarios' => DB::table('users')->orderBy('name')->get(['id', 'name']),
            'sucursales' => DB::table('cajas')->distinct()->orderBy('sucursal')->pluck('sucursal'),
        ]);
    }

    public function exportar(Request $request)
    {
        $f = $this->filtros($request);
        $registros = $this->consulta($f)->orderByDesc('c.id')->cursor();
        return response()->streamDownload(function () use ($registros) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Codigo', 'Usuario', 'Sucursal', 'Apertura', 'Cierre', 'Monto inicial Bs',
                'Ventas Bs', 'Ingresos Bs', 'Egresos Bs', 'Saldo Bs', 'Estado'], ';');
            foreach ($registros as $r) {
                fputcsv($out, [$r->id, $r->usuario, $r->sucursal, $r->fecha_apertura,
                    $r->fecha_cierre, $r->monto_inicial, $r->total_ventas,
                    $r->ingresos, $r->egresos, $r->saldo, $r->estado], ';');
            }
            fclose($out);
        }, 'reporte-cajas-'.now()->format('Ymd-His').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
