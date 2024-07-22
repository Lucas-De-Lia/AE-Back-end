<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica que la request tenga la key de app
        if ($request->header('X-API-Key') !== env("APP_X_API_KEY")) {
            return response()->json(['error' => 'Key de API inválida'], 401);
        }

        return $next($request);
    }
}
