<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ReporteStockController extends Controller
{
    private function filtros(Request $request): array
    {
        return $request->validate([
            'sucursal' => ['nullable','string','max:150'],
            'buscar' => ['nullable','string','max:150'],
            'marca_id' => ['nullable','integer','exists:marcas,id'],
            'proveedor_id' => ['nullable','integer','exists:proveedores,id'],
            'stock_min' => ['nullable','numeric','min:0'],
            'stock_max' => ['nullable','numeric','min:0','gte:stock_min'],
            'sin_stock' => ['nullable','boolean'],
        ]);
    }

    private function consulta(array $f)
    {
        return DB::table('productos as p')
            ->leftJoin('marcas as m','m.id','=','p.marca_id')
            ->where('p.tipo','PRODUCTO')
            ->select('p.id','p.codigo','p.nombre','p.sucursal','p.precio_compra',
                'p.precio_venta','p.stock_actual','p.stock_minimo','p.estado',
                'm.nombre as marca')
            ->selectSub(function ($q) {
                $q->from('compra_recepcion_items as i')
                    ->join('compra_recepciones as r','r.id','=','i.recepcion_id')
                    ->join('proveedores as pv','pv.id','=','r.proveedor_id')
                    ->select('pv.nombre')->whereColumn('i.producto_id','p.id')
                    ->orderByDesc('r.id')->limit(1);
            },'proveedor')
            ->when(!empty($f['sucursal']) && $f['sucursal'] !== 'TODAS',
                fn($q)=>$q->where('p.sucursal',$f['sucursal']))
            ->when(!empty($f['buscar']),function($q)use($f){
                $patron='%'.$f['buscar'].'%';
                $q->where(fn($sub)=>$sub->where('p.nombre','ilike',$patron)
                    ->orWhere('p.codigo','ilike',$patron));
            })
            ->when(!empty($f['marca_id']),fn($q)=>$q->where('p.marca_id',$f['marca_id']))
            ->when(!empty($f['proveedor_id']),function($q)use($f){
                $q->whereExists(function($sub)use($f){
                    $sub->selectRaw('1')->from('compra_recepcion_items as i')
                        ->join('compra_recepciones as r','r.id','=','i.recepcion_id')
                        ->whereColumn('i.producto_id','p.id')
                        ->where('r.proveedor_id',$f['proveedor_id']);
                });
            })
            ->when(isset($f['stock_min']),fn($q)=>$q->where('p.stock_actual','>=',$f['stock_min']))
            ->when(isset($f['stock_max']),fn($q)=>$q->where('p.stock_actual','<=',$f['stock_max']))
            ->when(!empty($f['sin_stock']),fn($q)=>$q->where('p.stock_actual','<=',0));
    }

    public function index(Request $request)
    {
        $f=$this->filtros($request);
        $totales=(clone $this->consulta($f))->select([])
            ->selectRaw('COUNT(*) AS productos, COALESCE(SUM(p.stock_actual * p.precio_compra),0) AS inversion,
                 COALESCE(SUM(p.stock_actual * p.precio_venta),0) AS valor_venta,
                 COALESCE(SUM(p.stock_actual * (p.precio_venta-p.precio_compra)),0) AS utilidad')
            ->first();

        return Inertia::render('Reportes/Stock',[
            'productos'=>$this->consulta($f)->orderBy('p.nombre')->orderBy('p.id')
                ->paginate(25)->withQueryString(),
            'totales'=>$totales,
            'filtros'=>array_merge([
                'sucursal'=>'TODAS','buscar'=>'','marca_id'=>'','proveedor_id'=>'',
                'stock_min'=>'','stock_max'=>'','sin_stock'=>false
            ],$f),
            'marcas'=>DB::table('marcas')->orderBy('nombre')->get(['id','nombre']),
            'proveedores'=>DB::table('proveedores')->orderBy('nombre')->get(['id','nombre']),
            'sucursales'=>DB::table('productos')->where('tipo','PRODUCTO')
                ->whereNotNull('sucursal')->distinct()->orderBy('sucursal')->pluck('sucursal'),
        ]);
    }

    public function exportar(Request $request)
    {
        $f=$this->filtros($request);
        $registros=$this->consulta($f)->orderBy('p.nombre')->orderBy('p.id')->cursor();
        return response()->streamDownload(function()use($registros){
            $out=fopen('php://output','w');
            fwrite($out,"\xEF\xBB\xBF");
            fputcsv($out,['Codigo','Marca','Proveedor ultima compra','Producto','Stock',
                'Precio compra Bs','Precio venta Bs','Inversion Bs','Valor venta Bs','Utilidad estimada Bs'],';');
            foreach($registros as $p){
                $stock=(float)$p->stock_actual;
                $pc=(float)$p->precio_compra;
                $pv=(float)$p->precio_venta;
                fputcsv($out,[$p->codigo,$p->marca??'NO DEFINIDO',
                    $p->proveedor??'SIN PROVEEDOR',$p->nombre,$p->stock_actual,
                    $p->precio_compra,$p->precio_venta,
                    number_format($stock*$pc,2,'.',''),
                    number_format($stock*$pv,2,'.',''),
                    number_format($stock*($pv-$pc),2,'.','')],';');
            }
            fclose($out);
        },'reporte-stock-'.now()->format('Ymd-His').'.csv',
        ['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
