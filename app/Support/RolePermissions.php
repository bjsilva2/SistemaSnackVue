<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class RolePermissions
{
    public static function forUser($user): array
    {
        if (!$user) return ['admin'=>false,'granted'=>[]];
        $role = DB::table('roles')->where('id', $user->rol_id)->first();
        if (!$role) return ['admin'=>false,'granted'=>[]];
        $admin = $role->nombre === 'Administrador';
        $granted = json_decode($role->permisos ?? '[]', true);
        return ['admin'=>$admin,'granted'=>is_array($granted) ? $granted : []];
    }

    public static function allows(array $permissions, string $key): bool
    {
        // Solo el administrador gestiona cuentas y roles, para impedir escalada de privilegios.
        if (str_starts_with($key, 'Usuarios.') || str_starts_with($key, 'Roles.')) {
            return $permissions['admin'];
        }
        return $permissions['admin'] || in_array($key, $permissions['granted'], true);
    }

    public static function landingPath(array $permissions): string
    {
        $pages = [
            'Dashboard.ver'=>'/dashboard',
            'Cocina.listar'=>'/ventas/cocina',
            'Venta.listar'=>'/ventas/pedidos',
            'Caja.listar'=>'/ventas/cajas',
            'Compra.listar'=>'/compras/ordenes',
            'Proveedor.listar'=>'/compras/proveedores',
            'Proveedor.pagos'=>'/compras/pagos',
            'Producto.listar'=>'/catalogos/productos',
            'Catálogos.listar'=>'/catalogos/grupos',
            'Unidad.listar'=>'/catalogos/unidades',
            'Clientes.listar'=>'/ventas/clientes',
            'Reportes.listar'=>'/reportes/ventas',
        ];
        foreach ($pages as $permission=>$path) {
            if (self::allows($permissions, $permission)) return $path;
        }
        return '/settings/profile';
    }

    // Cada ruta se vincula con UNA operación autorizada; nunca inferir solo por prefijo.
    public const ROUTES = [
        'dashboard'=>'Dashboard.ver',
        'grupos.index'=>'Catálogos.listar',
        'grupos.store'=>'Catálogos.crear',
        'grupos.update'=>'Catálogos.editar',
        'grupos.estado'=>'Catálogos.editar',
        'marcas.index'=>'Catálogos.listar',
        'marcas.store'=>'Catálogos.crear',
        'marcas.update'=>'Catálogos.editar',
        'marcas.estado'=>'Catálogos.editar',
        'unidades.index'=>'Unidad.listar',
        'unidades.store'=>'Unidad.crear',
        'unidades.update'=>'Unidad.editar',
        'unidades.estado'=>'Unidad.editar',
        'productos.index'=>'Producto.listar',
        'productos.store'=>'Producto.crear',
        'productos.update'=>'Producto.editar',
        'productos.estado'=>'Producto.actualizar',
        'compras.ordenes.index'=>'Compra.listar',
        'compras.recepciones.index'=>'Compra.listar',
        'compras.recepciones.store'=>'Compra.recibir',
        'proveedores.index'=>'Proveedor.listar',
        'proveedores.store'=>'Proveedor.crear',
        'proveedores.update'=>'Proveedor.editar',
        'proveedores.estado'=>'Proveedor.editar',
        'compras.pagos.index'=>'Proveedor.pagos',
        'compras.pagos.store'=>'Proveedor.pagos',
        'cajas.index'=>'Caja.listar',
        'cajas.store'=>'Caja.crear',
        'cajas.cerrar'=>'Caja.cerrar',
        'clientes.index'=>'Clientes.listar',
        'clientes.store'=>'Clientes.crear',
        'clientes.update'=>'Clientes.editar',
        'clientes.estado'=>'Clientes.editar',
        'pedidos.index'=>'Venta.listar',
        'pedidos.store'=>'Venta.crear',
        'pedidos.cobrar'=>'Venta.cobrar',
        'mesas.liberar'=>'Venta.detalle',
        'cocina.index'=>'Cocina.listar',
        'cocina.estado'=>'Cocina.marcar_listo',
        'reportes.ventas'=>'Reportes.listar',
        'reportes.ventas.exportar'=>'Reportes.exportar',
        'reportes.compras'=>'Reportes.listar',
        'reportes.compras.exportar'=>'Reportes.exportar',
        'reportes.cajas'=>'Reportes.listar',
        'reportes.cajas.exportar'=>'Reportes.exportar',
        'reportes.stock'=>'Reportes.listar',
        'reportes.stock.exportar'=>'Reportes.exportar',
        'reportes.mensual'=>'Reportes.listar',
        'ajustes.usuarios'=>'Usuarios.listar',
        'ajustes.usuarios.store'=>'Usuarios.crear',
        'ajustes.usuarios.update'=>'Usuarios.editar',
        'ajustes.roles'=>'Roles.listar',
        'ajustes.roles.store'=>'Roles.crear',
        'ajustes.roles.update'=>'Roles.editar',
        'ajustes.roles.permisos'=>'Roles.asignar_permisos',
        'ajustes.roles.destroy'=>'Roles.eliminar',
    ];
}
