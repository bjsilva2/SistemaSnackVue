<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ListadoVentasController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'buscar' => ['nullable', 'string', 'max:160'],
            'estado' => ['nullable', Rule::in(['TODOS','COBRADO','PENDIENTE'])],
            'tipo' => ['nullable', Rule::in(['TODOS','MESA','LLEVAR','YANGO'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $query = DB::table('pedidos as p')
            ->join('users as u', 'u.id', '=', 'p.usuario_id')
            ->leftJoin('clientes as c', 'c.id', '=', 'p.cliente_id')
            ->leftJoin('mesas as m', 'm.id', '=', 'p.mesa_id')
            ->select([
                'p.id', 'p.codigo', 'p.created_at as fecha', 'p.cobrado_at',
                'p.modalidad', 'm.numero as mesa_numero',
                'p.nombre_cliente', 'c.nombre_completo as cliente_registrado',
                'u.name as usuario', 'p.total', 'p.estado', 'p.metodo_pago',
            ])
            ->when(!empty($f['buscar']), function ($q) use ($f) {
                $like = '%'.$f['buscar'].'%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('p.codigo', 'ilike', $like)
                        ->orWhere('p.nombre_cliente', 'ilike', $like)
                        ->orWhere('c.nombre_completo', 'ilike', $like)
                        ->orWhere('u.name', 'ilike', $like);
                });
            })
            ->when(($f['estado'] ?? 'TODOS') === 'COBRADO',
                fn ($q) => $q->where('p.estado', 'COBRADO'))
            ->when(($f['estado'] ?? 'TODOS') === 'PENDIENTE',
                fn ($q) => $q->where('p.estado', '!=', 'COBRADO'))
            ->when(!empty($f['tipo']) && $f['tipo'] !== 'TODOS',
                fn ($q) => $q->where('p.modalidad', $f['tipo']))
            ->when(!empty($f['desde']),
                fn ($q) => $q->whereDate('p.created_at', '>=', $f['desde']))
            ->when(!empty($f['hasta']),
                fn ($q) => $q->whereDate('p.created_at', '<=', $f['hasta']));

        $cantidad = (clone $query)->count();
        $pedidos = $query->orderByDesc('p.id')->paginate(10)->withQueryString();

        $ids = $pedidos->getCollection()->pluck('id')->all();
        $items = DB::table('pedido_items as i')
            ->join('productos as pr', 'pr.id', '=', 'i.producto_id')
            ->whereIn('i.pedido_id', $ids)
            ->orderBy('i.id')
            ->get(['i.pedido_id', 'pr.nombre as producto', 'i.cantidad', 'i.precio_unitario', 'i.subtotal'])
            ->groupBy('pedido_id');
        $pedidos->getCollection()->transform(function ($pedido) use ($items) {
            $pedido->detalles = $items->get($pedido->id, collect())->values();
            return $pedido;
        });

        return Inertia::render('Ventas/ListadoVentas', [
            'ventas' => $pedidos,
            'totalRegistros' => $cantidad,
            'filtros' => array_merge([
                'buscar' => '', 'estado' => 'TODOS', 'tipo' => 'TODOS',
                'desde' => '', 'hasta' => '',
            ], $f),
        ]);
    }
}
