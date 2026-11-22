<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InformeMensualController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'mes' => ['nullable','integer','between:1,12'],
            'anio' => ['nullable','integer','between:2020,2100'],
            'sucursal' => ['nullable','string','max:150'],
        ]);
        $mes = (int)($data['mes'] ?? now()->month);
        $anio = (int)($data['anio'] ?? now()->year);
        $sucursal = $data['sucursal'] ?? 'TODAS';
        $inicio = Carbon::create($anio,$mes,1)->startOfDay();
        $fin = $inicio->copy()->endOfMonth();

        $ventas = DB::table('pedidos as p')
            ->join('cajas as c','c.id','=','p.caja_id')
            ->where('p.estado','COBRADO')
            ->whereBetween('p.cobrado_at',[$inicio,$fin])
            ->when($sucursal !== 'TODAS',fn($q)=>$q->where('c.sucursal',$sucursal))
            ->selectRaw('DATE(p.cobrado_at) as fecha, COUNT(*) as cantidad, SUM(p.total) as total')
            ->groupByRaw('DATE(p.cobrado_at)')->get()->keyBy('fecha');

        $compras = DB::table('compra_recepciones as r')
            ->whereBetween('r.fecha',[$inicio->toDateString(),$fin->toDateString()])
            ->when($sucursal !== 'TODAS',fn($q)=>$q->where('r.sucursal',$sucursal))
            ->selectRaw('r.fecha as fecha, SUM(r.total) as total')
            ->groupBy('r.fecha')->get()->keyBy('fecha');

        // Estos importes son movimientos adicionales de caja; las ventas se cuentan por separado.
        $cajas = DB::table('cajas as c')
            ->whereBetween('c.fecha_apertura',[$inicio,$fin])
            ->when($sucursal !== 'TODAS',fn($q)=>$q->where('c.sucursal',$sucursal))
            ->selectRaw('DATE(c.fecha_apertura) as fecha, SUM(c.ingresos) as ingresos, SUM(c.egresos) as egresos')
            ->groupByRaw('DATE(c.fecha_apertura)')->get()->keyBy('fecha');

        $dias = [];
        $totales = ['productos'=>0,'cantidad'=>0,'ventas'=>0.0,'ingresos'=>0.0,'egresos'=>0.0,'compras'=>0.0,'saldo'=>0.0];
        for ($i=1;$i<=$inicio->daysInMonth;$i++) {
            $fecha = $inicio->copy()->day($i)->toDateString();
            $v = $ventas->get($fecha);
            $c = $compras->get($fecha);
            $cj = $cajas->get($fecha);
            $cantidad = (int)($v->cantidad ?? 0);
            $venta = round((float)($v->total ?? 0),2);
            $ingresos = round((float)($cj->ingresos ?? 0),2);
            $egresos = round((float)($cj->egresos ?? 0),2);
            $compra = round((float)($c->total ?? 0),2);
            $saldo = round($venta + $ingresos - $egresos - $compra,2);
            $dias[] = ['fecha'=>$fecha,'dia'=>$i,'cantidad'=>$cantidad,'ventas'=>$venta,
                'promedio'=>$cantidad ? round($venta/$cantidad,2) : 0,
                'ingresos'=>$ingresos,'egresos'=>$egresos,'compras'=>$compra,'saldo'=>$saldo];
            $totales['cantidad'] += $cantidad;
            $totales['ventas'] += $venta;
            $totales['ingresos'] += $ingresos;
            $totales['egresos'] += $egresos;
            $totales['compras'] += $compra;
            $totales['saldo'] += $saldo;
        }

        // La aplicación actual vende productos físicos. No registra un canal de venta de servicios.
        $totales['productos'] = $totales['cantidad'];
        foreach (['ventas','ingresos','egresos','compras','saldo'] as $col) {
            $totales[$col] = round($totales[$col],2);
        }

        return Inertia::render('Reportes/Mensual',[
            'dias'=>$dias,
            'totales'=>$totales,
            'filtros'=>['mes'=>$mes,'anio'=>$anio,'sucursal'=>$sucursal],
            'sucursales'=>DB::table('cajas')->select('sucursal')
                ->union(DB::table('compra_recepciones')->select('sucursal'))
                ->orderBy('sucursal')->pluck('sucursal'),
        ]);
    }
}
