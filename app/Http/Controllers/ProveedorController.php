<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim($request->input('buscar', ''));

        $proveedores = Proveedor::query()
            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'ilike', "%{$buscar}%")
                      ->orWhere('codigo', 'ilike', "%{$buscar}%")
                      ->orWhere('nit', 'ilike', "%{$buscar}%")
                      ->orWhere('telefono', 'ilike', "%{$buscar}%")
                      ->orWhere('contacto', 'ilike', "%{$buscar}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Compras/Proveedores', [
            'proveedores' => $proveedores,
            'filtros' => [
                'buscar' => $buscar,
            ],
        ]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            // Nombre completo
            'nombre' => [
                'required',
                'string',
                'max:200',
            ],

            // Tipo de proveedor
            'tipo_proveedor' => [
                'required',
                Rule::in(['Natural', 'Juridico']),
            ],

            // Procedencia
            'procedencia' => [
                'required',
                Rule::in(['NACIONAL', 'EXTRANJERO']),
            ],

            // CI/NIT
            'nit' => [
                'nullable',
                'string',
                'max:30',
            ],

            // Teléfono
            'telefono' => [
                'nullable',
                'string',
                'max:30',
            ],

            // Contacto
            'contacto' => [
                'nullable',
                'string',
                'max:150',
            ],

            // Celular del contacto
            'celular_contacto' => [
                'nullable',
                'string',
                'max:30',
            ],

            // Nombre cheque
            'nombre_cheque' => [
                'nullable',
                'string',
                'max:200',
            ],

            // Nombre LCV
            'nombre_lcv' => [
                'nullable',
                'string',
                'max:200',
            ],

            // Estado
            'estado' => [
                'required',
                'boolean',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        DB::transaction(function () use ($datos) {

            // PostgreSQL reserva un ID único.
            $resultado = DB::selectOne(
                "SELECT nextval(
                    pg_get_serial_sequence('proveedores', 'id')
                ) AS numero"
            );

            $id = (int) $resultado->numero;

            // Código automático.
            $codigo = 'PROV' . str_pad(
                (string) $id,
                6,
                '0',
                STR_PAD_LEFT
            );

            $proveedor = new Proveedor();

            $proveedor->forceFill(array_merge($datos, [
                'id' => $id,
                'codigo' => $codigo,
            ]));

            $proveedor->save();
        });

        return redirect()->back()
            ->with(
                'success',
                'Proveedor registrado correctamente.'
            );
    }

    public function update(
        Request $request,
        Proveedor $proveedor
    ) {
        $datos = $this->validar($request);

        // El código automático nunca se modifica.
        $proveedor->update($datos);

        return redirect()->back()
            ->with(
                'success',
                'Proveedor actualizado correctamente.'
            );
    }

    public function cambiarEstado(Proveedor $proveedor)
    {
        $proveedor->update([
            'estado' => !$proveedor->estado,
        ]);

        return redirect()->back()
            ->with(
                'success',
                'Estado actualizado correctamente.'
            );
    }
}
