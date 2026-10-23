<?php

namespace App\Http\Controllers;

use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UnidadMedidaController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim($request->input('buscar', ''));

        $unidades = UnidadMedida::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'ilike', "%{$buscar}%")
                      ->orWhere('abreviatura', 'ilike', "%{$buscar}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Catalogos/Unidades', [
            'unidades' => $unidades,
            'filtros' => [
                'buscar' => $buscar,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150',
                Rule::unique('unidad_medidas', 'nombre')],
            'abreviatura' => ['required', 'string', 'max:20',
                Rule::unique('unidad_medidas', 'abreviatura')],
            'estado' => ['required', 'boolean'],
        ]);

        UnidadMedida::create($datos);

        return redirect()->back()
            ->with('success', 'Unidad registrada correctamente.');
    }

    public function update(Request $request, UnidadMedida $unidad)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150',
                Rule::unique('unidad_medidas', 'nombre')->ignore($unidad->id)],
            'abreviatura' => ['required', 'string', 'max:20',
                Rule::unique('unidad_medidas', 'abreviatura')->ignore($unidad->id)],
            'estado' => ['required', 'boolean'],
        ]);

        $unidad->update($datos);

        return redirect()->back()
            ->with('success', 'Unidad actualizada correctamente.');
    }

    public function cambiarEstado(UnidadMedida $unidad)
    {
        $unidad->update([
            'estado' => !$unidad->estado,
        ]);

        return redirect()->back()
            ->with('success', 'Estado actualizado correctamente.');
    }
}
