<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page d'arrivée après la connexion : chaque rôle a la sienne.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // L'administrateur arrive sur la gestion des comptes (maquette 16).
        if ($user->isAdmin()) {
            return redirect()->route('admin.users.index');
        }

        return view('dashboard');
    }
}
