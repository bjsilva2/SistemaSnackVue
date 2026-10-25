<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CajaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $cajas = DB::table('cajas as c')
            ->join('users as u', 'u.id', '=', 'c.usuario_id')
            ->select('c.id','u.name as usuario','c.sucursal','c.monto_inicial','c.ingresos',
                'c.egresos','c.total_ventas','c.saldo','c.fecha_apertura','c.fecha_cierre','c.estado')
            ->when($buscar !== '', fn ($q) => $q->where(function ($q) use ($buscar) {
                $q->where('u.name', 'ilike', '%'.$buscar.'%')
                  ->orWhere('c.sucursal', 'ilike', '%'.$buscar.'%')
                  ->orWhereRaw('CAST(c.id AS TEXT) LIKE ?', ['%'.$buscar.'%']);
            }))
            ->orderByDesc('c.id')->paginate(10)->withQueryString();
        return Inertia::render('Ventas/Cajas', [
            'cajas' => $cajas,
            'filtros' => ['buscar' => $buscar],
            'cajaAbierta' => DB::table('cajas')
                ->where('usuario_id', $request->user()->id)
                ->where('estado', 'ABIERTO')->exists(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'sucursal' => ['required', 'string', 'max:150', 'in:Sistema Snack'],
            'monto_inicial' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);
        DB::transaction(function () use ($request, $datos) {
            // Una apertura simultánea del mismo usuario no debe duplicar su caja.
            DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first();
            if (DB::table('cajas')->where('usuario_id', $request->user()->id)
                ->where('estado', 'ABIERTO')->exists()) {
                throw ValidationException::withMessages([
                    'monto_inicial' => 'Ya tienes una caja abierta. Debes cerrarla antes de abrir otra.',
                ]);
            }
            DB::table('cajas')->insert([
                'usuario_id' => $request->user()->id,
                'sucursal' => $datos['sucursal'],
                'monto_inicial' => $datos['monto_inicial'],
                'saldo' => $datos['monto_inicial'],
                'fecha_apertura' => now(),
                'estado' => 'ABIERTO',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return back()->with('success', 'Caja abierta correctamente.');
    }

    public function cerrar(Request $request, int $caja)
    {
        DB::transaction(function () use ($request, $caja) {
            $registro = DB::table('cajas')->where('id', $caja)->lockForUpdate()->first();
            if (!$registro || $registro->usuario_id !== $request->user()->id) {
                abort(403, 'Solo puedes cerrar tu propia caja.');
            }
            if ($registro->estado !== 'ABIERTO') {
                throw ValidationException::withMessages(['caja' => 'La caja ya está cerrada.']);
            }
            DB::table('cajas')->where('id', $caja)->update([
                'estado' => 'CERRADO', 'fecha_cierre' => now(), 'updated_at' => now(),
            ]);
        });
        return back()->with('success', 'Caja cerrada correctamente.');
    }
}
