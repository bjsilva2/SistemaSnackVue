<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\GrupoProducto;
use App\Models\Marca;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $productos = Producto::with(['grupo', 'marca', 'unidadMedida'])
            ->when($buscar !== '', fn ($query) => $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'ilike', "%{$buscar}%")
                  ->orWhere('codigo', 'ilike', "%{$buscar}%");
            }))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $productos->getCollection()->each(function ($producto) {
            $producto->imagen_url = $producto->imagen
                ? Storage::disk('public')->url($producto->imagen)
                : null;
        });

        return Inertia::render('Catalogos/Productos', [
            'productos' => $productos,
            'grupos' => GrupoProducto::where('estado', true)->orderBy('nombre')->get(['id', 'nombre']),
            'marcas' => Marca::where('estado', true)->orderBy('nombre')->get(['id', 'nombre']),
            'unidades' => UnidadMedida::where('estado', true)->orderBy('nombre')->get(['id', 'nombre', 'abreviatura']),
            'filtros' => ['buscar' => $buscar],
        ]);
    }

    private function validar(Request $request, ?Producto $producto = null): array
    {
        return $request->validate([
            // El código se genera en el servidor: el formulario NO puede cambiarlo.
            'nombre' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::in(['PRODUCTO', 'SERVICIO'])],
            'grupo_id' => ['nullable', Rule::exists('grupo_productos', 'id')],
            'marca_id' => ['nullable', Rule::exists('marcas', 'id')],
            'unidad_medida_id' => ['nullable', Rule::exists('unidad_medidas', 'id')],
            'procedencia' => ['nullable', 'string', 'max:150'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'caso_uso' => ['nullable', 'string', 'max:200'],
            'sucursal' => ['nullable', 'string', 'max:150'],
            'orden_menu' => ['required', 'integer', 'min:0', 'max:999999'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'estado' => ['required', 'boolean'],
            'observaciones' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'quitar_imagen' => ['nullable', 'boolean'],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $stock = $request->validate(['stock_actual' => ['required', 'numeric', 'min:0']]);
        $archivo = $request->file('imagen');
        unset($datos['imagen'], $datos['quitar_imagen']);
        $datos['stock_actual'] = $datos['tipo'] === 'SERVICIO' ? 0 : $stock['stock_actual'];

        // Bloqueo de transacción PostgreSQL para códigos consecutivos sin duplicados.
        // Una vez usado, el código no se reutiliza mientras haya un registro con ese código.
        $rutaNueva = null;
        try {
            DB::transaction(function () use (&$rutaNueva, $datos, $archivo) {
                DB::select('SELECT pg_advisory_xact_lock(894162)');
                $ultimo = DB::selectOne("SELECT COALESCE(MAX(CAST(SUBSTRING(codigo FROM 4) AS BIGINT)), 0) AS numero FROM productos WHERE codigo ~ '^PRD[0-9]+$'");
                $datos['codigo'] = 'PRD' . str_pad((string) ((int) $ultimo->numero + 1), 6, '0', STR_PAD_LEFT);
                if ($archivo) {
                    $rutaNueva = $archivo->store('productos', 'public');
                    $datos['imagen'] = $rutaNueva;
                }
                Producto::create($datos);
            });
        } catch (\Throwable $e) {
            if ($rutaNueva) Storage::disk('public')->delete($rutaNueva);
            throw $e;
        }

        return redirect()->back()->with('success', 'Producto registrado correctamente.');
    }

    public function update(Request $request, Producto $producto)
    {
        $datos = $this->validar($request, $producto);
        $archivo = $request->file('imagen');
        $quitarImagen = (bool) ($datos['quitar_imagen'] ?? false);
        unset($datos['imagen'], $datos['quitar_imagen']);

        // El código y el stock actual nunca se modifican desde este formulario.
        $rutaAnterior = $producto->imagen;
        $rutaNueva = null;

        try {
            if ($archivo) {
                $rutaNueva = $archivo->store('productos', 'public');
                $datos['imagen'] = $rutaNueva;
            } elseif ($quitarImagen) {
                $datos['imagen'] = null;
            }
            $producto->update($datos);
        } catch (\Throwable $e) {
            if ($rutaNueva) Storage::disk('public')->delete($rutaNueva);
            throw $e;
        }

        if ($rutaAnterior && ($rutaNueva || $quitarImagen)) {
            Storage::disk('public')->delete($rutaAnterior);
        }

        return redirect()->back()->with('success', 'Producto actualizado correctamente.');
    }

    public function cambiarEstado(Producto $producto)
    {
        $producto->update(['estado' => ! $producto->estado]);
        return redirect()->back()->with('success', 'Estado actualizado correctamente.');
    }
}
