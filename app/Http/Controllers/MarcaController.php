<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MarcaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->input('buscar', '');

        $marcas = Marca::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where('nombre', 'ilike', "%{$buscar}%");
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Catalogos/Marcas', [
            'marcas' => $marcas,
            'filtros' => [
                'buscar' => $buscar,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('marcas', 'nombre'),
            ],
            'estado' => ['required', 'boolean'],
        ]);

        Marca::create($datos);

        return redirect()->back()
            ->with('success', 'Marca registrada correctamente.');
    }

    public function update(Request $request, Marca $marca)
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('marcas', 'nombre')->ignore($marca->id),
            ],
            'estado' => ['required', 'boolean'],
        ]);

        $marca->update($datos);

        return redirect()->back()
            ->with('success', 'Marca actualizada correctamente.');
    }

    public function cambiarEstado(Marca $marca)
    {
        $marca->update([
            'estado' => !$marca->estado,
        ]);

        return redirect()->back()
            ->with('success', 'Estado actualizado correctamente.');
    }
}
