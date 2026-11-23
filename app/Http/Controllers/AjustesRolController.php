<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AjustesRolController extends Controller
{
    public const MODULOS = [
        'Dashboard'=>['ver'],
        'Catálogos'=>['listar','crear','editar'],
        'Unidad'=>['listar','crear','editar'],
        'Producto'=>['listar','crear','editar','detalle','actualizar'],
        'Caja'=>['listar','crear','cerrar','detalle'],
        'Compra'=>['listar','crear','detalle','recibir'],
        'Proveedor'=>['listar','crear','editar','detalle','pagos'],
        'Venta'=>['listar','crear','detalle','cobrar'],
        'Cocina'=>['listar','marcar_listo'],
        'Clientes'=>['listar','crear','editar'],
        'Reportes'=>['listar','exportar'],
        'Usuarios'=>['listar','crear','editar'],
        'Roles'=>['listar','crear','editar','asignar_permisos'],
    ];
    // Acciones visibles en la captura original pero todavía sin una operación
    // equivalente implementada en SistemaSnackVue. Se muestran deshabilitadas:
    // no se guardan como permisos ni conceden acceso a operaciones inexistentes.
    public const PENDIENTES = [
        'Dashboard'=>['ProductosStockCero','ProductosStockMin','Productos+Vendidos'],
        'Laboratorio'=>['Listar','Crear','Editar','Detalle','Eliminar'],
        'Unidad'=>['Eliminar'],
        'Producto'=>['Eliminar','VerImagen'],
        'Caja'=>['Eliminar','DescargarPDF'],
        'Compra'=>['DescargarPDF','DetalleRecepcion','Anular','NuevaSolicitud'],
        'Proveedor'=>['Eliminar'],
        'Venta'=>['Anular','DescargarPDF','Descuentos','M.Pagos','EditarPrecio'],
    ];

    private function admin(Request $request): void
    {
        if (!DB::table('users')->join('roles','roles.id','=','users.rol_id')
            ->where('users.id',$request->user()->id)
            ->where('roles.nombre','Administrador')->exists()) abort(403,'Solo administradores.');
    }
    public function index(Request $request)
    {
        $this->admin($request);
        return Inertia::render('Ajustes/Roles',[
            'roles'=>DB::table('roles')->orderBy('id')->paginate(10)->withQueryString(),
            'modulos'=>self::MODULOS,
            'pendientes'=>self::PENDIENTES,
            'usuariosPorRol'=>DB::table('users')->whereNotNull('rol_id')
                ->select('rol_id',DB::raw('COUNT(*) as total'))->groupBy('rol_id')->pluck('total','rol_id'),
        ]);
    }
    public function store(Request $request)
    {
        $this->admin($request);
        $data=$request->validate(['nombre'=>['required','string','max:80','unique:roles,nombre']]);
        DB::table('roles')->insert(['nombre'=>$data['nombre'],'permisos'=>'[]','created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','Rol creado.');
    }
    public function update(Request $request,int $rol)
    {
        $this->admin($request);
        $data=$request->validate(['nombre'=>['required','string','max:80',Rule::unique('roles','nombre')->ignore($rol)]]);
        $actual=DB::table('roles')->where('id',$rol)->first();
        if(!$actual)abort(404);
        if($actual->nombre==='Administrador' && $data['nombre']!=='Administrador')
            throw ValidationException::withMessages(['nombre'=>'No se puede cambiar el nombre del rol Administrador.']);
        DB::table('roles')->where('id',$rol)->update(['nombre'=>$data['nombre'],'updated_at'=>now()]);
        return back()->with('success','Rol actualizado.');
    }
    public function permisos(Request $request,int $rol)
    {
        $this->admin($request);
        $claves=[];
        // Permitir configurar tanto funciones existentes como opciones futuras de la referencia.
        foreach(self::MODULOS as $modulo=>$acciones)foreach($acciones as $accion)$claves[]=$modulo.'.'.$accion;
        foreach(self::PENDIENTES as $modulo=>$acciones)foreach($acciones as $accion)$claves[]=$modulo.'.'.$accion;
        $data=$request->validate([
            'permisos'=>['present','array'],
            'permisos.*'=>['string',Rule::in($claves)],
        ]);
        $actual=DB::table('roles')->where('id',$rol)->first();
        if(!$actual)abort(404);
        if($actual->nombre==='Administrador')
            throw ValidationException::withMessages(['permisos'=>'El administrador conserva acceso completo.']);
        DB::table('roles')->where('id',$rol)->update([
            'permisos'=>json_encode(array_values(array_unique($data['permisos']))),'updated_at'=>now(),
        ]);
        return back()->with('success','Permisos guardados.');
    }
    public function destroy(Request $request,int $rol)
    {
        $this->admin($request);
        DB::transaction(function()use($rol){
            $actual=DB::table('roles')->where('id',$rol)->lockForUpdate()->first();
            if(!$actual)abort(404);
            if($actual->nombre==='Administrador'||DB::table('users')->where('rol_id',$rol)->exists())
                throw ValidationException::withMessages(['rol'=>'No puedes eliminar el Administrador ni un rol asignado a usuarios.']);
            DB::table('roles')->where('id',$rol)->delete();
        });
        return back()->with('success','Rol eliminado.');
    }
}
