<?php

namespace App\Http\Controllers;

use App\Support\RolePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);
        $desde = $f['desde'] ?? now()->startOfMonth()->toDateString();
        $hasta = $f['hasta'] ?? now()->toDateString();
        $permisos = RolePermissions::forUser($request->user());
        $masVendidos = RolePermissions::allows($permisos, 'Dashboard.Productos+Vendidos');
        $stockCero = RolePermissions::allows($permisos, 'Dashboard.ProductosStockCero');
        $stockMinimo = RolePermissions::allows($permisos, 'Dashboard.ProductosStockMin');

        $vendidos = $masVendidos
            ? DB::table('pedido_items as pi')
                ->join('pedidos as pe', 'pe.id', '=', 'pi.pedido_id')
                ->join('productos as p', 'p.id', '=', 'pi.producto_id')
                ->where('pe.estado', 'COBRADO')
                ->whereDate('pe.cobrado_at', '>=', $desde)
                ->whereDate('pe.cobrado_at', '<=', $hasta)
                ->select('p.id', 'p.nombre')
                ->selectRaw('SUM(pi.cantidad) as cantidad, SUM(pi.subtotal) as importe')
                ->groupBy('p.id', 'p.nombre')
                ->orderByDesc('cantidad')->limit(6)->get()
            : [];

        // Solo devolver datos que el usuario tiene derecho a visualizar.
        $agotados = $stockCero
            ? DB::table('productos')
                ->where('tipo', 'PRODUCTO')->where('estado', true)
                ->where('stock_actual', '<=', 0)
                ->orderBy('nombre')->limit(20)
                ->get(['id', 'codigo', 'nombre', 'stock_actual'])
            : [];

        $bajos = $stockMinimo
            ? DB::table('productos')
                ->where('tipo', 'PRODUCTO')->where('estado', true)
                ->where('stock_actual', '>', 0)
                ->whereColumn('stock_actual', '<=', 'stock_minimo')
                ->orderBy('stock_actual')->orderBy('nombre')->limit(20)
                ->get(['id', 'codigo', 'nombre', 'stock_actual', 'stock_minimo'])
            : [];

        return Inertia::render('Dashboard', [
            'estadisticas' => [
                'productos' => DB::table('productos')->where('tipo', 'PRODUCTO')->count(),
                'clientes' => DB::table('clientes')->count(),
                'proveedores' => DB::table('proveedores')->count(),
            ],
            'filtros' => ['desde' => $desde, 'hasta' => $hasta],
            'masVendidos' => $vendidos,
            'stockCero' => $agotados,
            'stockMinimo' => $bajos,
            'visibilidad' => [
                'masVendidos' => $masVendidos,
                'stockCero' => $stockCero,
                'stockMinimo' => $stockMinimo,
            ],
        ]);
    }
}
