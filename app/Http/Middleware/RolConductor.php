<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolConductor
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->role->nombre !== 'conductor') {
            return response()->json(['error' => 'Acceso denegado. Solo conductores pueden acceder.'], 403);
        }

        return $next($request);
    }
}
