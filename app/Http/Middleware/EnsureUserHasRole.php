<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laisse passer uniquement les utilisateurs qui ont l'un des rôles demandés.
 *
 * Utilisation dans les routes (alias déclaré dans bootstrap/app.php) :
 *   Route::middleware(['auth', 'role:doctor,secretary'])->group(...);
 *
 * Il protège des groupes de routes entiers ; les droits fins, objet par
 * objet (ex. : voir CE dossier médical), sont vérifiés par les Policies.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Une valeur inconnue dans la route (faute de frappe) lève une erreur
        // immédiatement, au lieu de bloquer silencieusement tout le monde.
        $allowed = array_map(fn (string $role) => Role::from($role), $roles);

        abort_unless($user !== null && $user->hasRole(...$allowed), 403);

        return $next($request);
    }
}
