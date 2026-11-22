<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MonitorCocinaController extends Controller
{
    public function index()
    {
        $pedidos = DB::table('pedidos as p')
            ->leftJoin('mesas as m', 'm.id', '=', 'p.mesa_id')
            ->whereIn('p.cocina_estado', ['EN_COCINA', 'LISTO'])
            ->select('p.id', 'p.codigo', 'p.modalidad', 'p.nombre_cliente',
                'p.nota_cocina', 'p.cocina_estado', 'p.created_at', 'p.cocina_listo_at',
                'm.numero as mesa_numero')
            ->orderByRaw("CASE WHEN p.cocina_estado = 'EN_COCINA' THEN 0 ELSE 1 END")
            ->orderBy('p.created_at')
            ->limit(100)
            ->get();

        $items = DB::table('pedido_items as pi')
            ->join('productos as pr', 'pr.id', '=', 'pi.producto_id')
            ->whereIn('pi.pedido_id', $pedidos->pluck('id'))
            ->select('pi.pedido_id', 'pr.nombre', 'pi.cantidad')
            ->orderBy('pi.id')
            ->get()
            ->groupBy('pedido_id');

        $pedidos->each(function ($pedido) use ($items) {
            $pedido->items = $items->get($pedido->id, collect())->values();
        });

        return Inertia::render('Ventas/MonitorCocina', [
            'pedidos' => $pedidos,
            'horaServidor' => now()->toIso8601String(),
        ]);
    }

    public function cambiarEstado(Request $request, int $pedido)
    {
        $datos = $request->validate([
            'estado' => ['required', 'in:EN_COCINA,LISTO'],
        ]);
        DB::transaction(function () use ($pedido, $datos) {
            $actual = DB::table('pedidos')->where('id', $pedido)->lockForUpdate()->first();
            if (!$actual) {
                abort(404);
            }
            if ($actual->cocina_estado === $datos['estado']) {
                return;
            }
            if ($actual->cocina_estado !== 'EN_COCINA' || $datos['estado'] !== 'LISTO') {
                throw ValidationException::withMessages([
                    'estado' => 'Solo puedes marcar como LISTO un pedido que está en cocina.',
                ]);
            }
            DB::table('pedidos')->where('id', $pedido)->update([
                'cocina_estado' => 'LISTO',
                'cocina_listo_at' => now(),
                'updated_at' => now(),
            ]);
        });
        return back()->with('success', 'Pedido marcado como listo.');
    }
}
