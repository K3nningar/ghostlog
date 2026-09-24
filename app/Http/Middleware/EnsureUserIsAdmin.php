<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * ACL : refuse l'accès à tout utilisateur non-administrateur.
     * Les visiteurs non authentifiés sont déjà bloqués par le middleware
     * `auth` qui précède celui-ci dans la pile de la route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_admin) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}
