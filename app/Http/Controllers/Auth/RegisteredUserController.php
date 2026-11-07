<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Gender;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterPatientRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Création d'un compte patient par le visiteur (BF-03, RG-03, maquette 2).
 * Les comptes du personnel sont créés par l'administrateur (Admin\UserController).
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', ['genders' => Gender::cases()]);
    }

    public function store(RegisterPatientRequest $request): RedirectResponse
    {
        // Compte, dossier patient et dossier médical vide : tout ou rien (transaction).
        $user = DB::transaction(function () use ($request) {
            $user = new User($request->safe()->only(['last_name', 'first_name', 'email', 'phone']));
            $user->role = Role::Patient;
            $user->password = $request->validated('password'); // haché par le cast « hashed »
            $user->save();

            $patient = $user->patient()->create([
                ...$request->safe()->only(['birth_date', 'gender']),
                'data_consent' => true,
                'consent_given_at' => now(),
            ]);
            $patient->medicalRecord()->create();

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);

        return redirect()->intended(route('dashboard'))
            ->with('status', "Bienvenue {$user->first_name} : votre compte patient est créé.");
    }
}
