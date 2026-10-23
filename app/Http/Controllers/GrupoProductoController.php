<?php

namespace App\Http\Controllers;

use App\Models\GrupoProducto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GrupoProductoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->input('buscar', '');

        $grupos = GrupoProducto::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where('nombre', 'ilike', "%{$buscar}%");
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Catalogos/Grupos', [
            'grupos' => $grupos,
            'filtros' => [
                'buscar' => $buscar,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150', 'unique:grupo_productos,nombre'],
            'estado' => ['required', 'boolean'],
        ]);

        GrupoProducto::create($datos);

        return redirect()->back()->with('success', 'Grupo registrado correctamente.');
    }

    public function update(Request $request, GrupoProducto $grupo)
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('grupo_productos', 'nombre')->ignore($grupo->id),
            ],
            'estado' => ['required', 'boolean'],
        ]);

        $grupo->update($datos);

        return redirect()->back()->with('success', 'Grupo actualizado correctamente.');
    }

    public function cambiarEstado(GrupoProducto $grupo)
    {
        $grupo->update([
            'estado' => !$grupo->estado,
        ]);

        return redirect()->back()->with('success', 'Estado actualizado correctamente.');
    }
}
