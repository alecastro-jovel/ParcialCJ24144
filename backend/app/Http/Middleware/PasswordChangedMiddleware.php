<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mientras el usuario tenga una contraseña temporal no puede usar el resto de la API.
 */
class PasswordChangedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            return response()->json([
                'message'                  => 'Por seguridad, debes cambiar tu contraseña temporal antes de continuar.',
                'password_change_required' => true,
            ], 403);
        }

        return $next($request);
    }
}
