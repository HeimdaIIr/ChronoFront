<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();
        $userRole = $user->role;

        // Admin always has access to everything
        if ($userRole === 'admin') {
            return $next($request);
        }

        // Check if user's role is in the allowed roles
        if (!in_array($userRole, $roles)) {
            abort(403, 'Accès non autorisé. Votre rôle ne permet pas d\'accéder à cette ressource.');
        }

        return $next($request);
    }
}
