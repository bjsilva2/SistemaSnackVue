<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $clientes = Cliente::query()
            ->when($buscar !== '', fn ($q) => $q->where(function ($q) use ($buscar) {
                $q->where('nombre_completo', 'ilike', "%{$buscar}%")
                  ->orWhere('codigo', 'ilike', "%{$buscar}%")
                  ->orWhere('ci_nit', 'ilike', "%{$buscar}%")
                  ->orWhere('telefono', 'ilike', "%{$buscar}%");
            }))
            ->orderByDesc('id')->paginate(10)->withQueryString();
        return Inertia::render('Ventas/Clientes', [
            'clientes' => $clientes,
            'filtros' => ['buscar' => $buscar],
        ]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre_completo' => ['required', 'string', 'max:200'],
            'ci_nit' => ['nullable', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'domicilio' => ['nullable', 'string', 'max:250'],
            'tipo_cliente' => ['required', 'in:Normal,Mayorista'],
            'estado' => ['required', 'boolean'],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        DB::transaction(function () use ($datos) {
            $cliente = Cliente::create(array_merge($datos, ['codigo' => 'PENDIENTE-'.uniqid()]));
            $cliente->codigo = 'CLI-' . str_pad((string) $cliente->id, 4, '0', STR_PAD_LEFT);
            $cliente->save();
        });
        return back()->with('success', 'Cliente registrado correctamente.');
    }

    public function update(Request $request, Cliente $cliente)
    {
        // El código no es editable.
        $cliente->update($this->validar($request));
        return back()->with('success', 'Cliente actualizado correctamente.');
    }

    public function cambiarEstado(Cliente $cliente)
    {
        $cliente->update(['estado' => !$cliente->estado]);
        return back()->with('success', 'Estado del cliente actualizado.');
    }
}
