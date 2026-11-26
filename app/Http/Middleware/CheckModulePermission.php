<?php

namespace App\Http\Middleware;

use App\Support\RolePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $permissions = RolePermissions::forUser($request->user());
        if ($permissions['admin']) return $next($request);

        // Las rutas dentro de este grupo requieren permisos expresos.
        $name = $request->route()?->getName();
        $key = $name ? (RolePermissions::ROUTES[$name] ?? null) : null;
        abort_unless($key && RolePermissions::allows($permissions, $key), 403,
            'Tu rol no tiene permiso para esta operación.');

        return $next($request);
    }
}
