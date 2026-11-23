<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario=$request->user();
        abort_unless($usuario,401);
        $rol=DB::table('roles')->where('id',$usuario->rol_id)->first();
        if($rol?->nombre==='Administrador')return $next($request);
        if(!$rol)abort(403,'Usuario sin rol. Pide al administrador que asigne uno.');

        $ruta=$request->path();
        $metodo=$request->method();
        $modulo=null;$accion=null;
        if(str_starts_with($ruta,'catalogos/productos')){
            $modulo='Producto';$accion=match($metodo){'GET'=>'listar','POST'=>'crear',default=>'editar'};
        }elseif(str_starts_with($ruta,'catalogos/')){
            $modulo='Catálogos';$accion=match($metodo){'GET'=>'listar','POST'=>'crear',default=>'editar'};
        }elseif(str_starts_with($ruta,'compras/proveedores')){
            $modulo='Proveedor';$accion=match($metodo){'GET'=>'listar','POST'=>'crear',default=>'editar'};
        }elseif(str_starts_with($ruta,'compras/pagos')){
            $modulo='Proveedor';$accion=$metodo==='GET'?'pagos':'pagos';
        }elseif(str_starts_with($ruta,'compras/')){
            $modulo='Compra';$accion=$metodo==='GET'?'listar':($metodo==='POST'?'recibir':'detalle');
        }elseif(str_starts_with($ruta,'ventas/cajas')){
            $modulo='Caja';$accion=$metodo==='GET'?'listar':($metodo==='POST'?'crear':'cerrar');
        }elseif(str_starts_with($ruta,'ventas/clientes')){
            $modulo='Clientes';$accion=$metodo==='GET'?'listar':($metodo==='POST'?'crear':'editar');
        }elseif(str_starts_with($ruta,'ventas/cocina')){
            $modulo='Cocina';$accion=$metodo==='GET'?'listar':'marcar_listo';
        }elseif(str_starts_with($ruta,'ventas/pedidos')||str_starts_with($ruta,'ventas/mesas')){
            $modulo='Venta';$accion=$metodo==='GET'?'listar':(str_ends_with($ruta,'/cobrar')?'cobrar':'crear');
        }elseif(str_starts_with($ruta,'reportes/')){
            $modulo='Reportes';$accion=str_ends_with($ruta,'/exportar')?'exportar':'listar';
        }elseif(str_starts_with($ruta,'ajustes/usuarios')){
            $modulo='Usuarios';$accion=$metodo==='GET'?'listar':($metodo==='POST'?'crear':'editar');
        }elseif(str_starts_with($ruta,'ajustes/roles')){
            $modulo='Roles';$accion=$metodo==='GET'?'listar':($metodo==='POST'?'crear':(str_ends_with($ruta,'/permisos')?'asignar_permisos':'editar'));
        }elseif($ruta==='dashboard'){
            $modulo='Dashboard';$accion='ver';
        }
        if($modulo){
            $permisos=json_decode($rol->permisos??'[]',true);
            if(!is_array($permisos)||!in_array($modulo.'.'.$accion,$permisos,true)){
                abort(403,'Tu rol no tiene permiso para realizar esta acción.');
            }
        }
        return $next($request);
    }
}
