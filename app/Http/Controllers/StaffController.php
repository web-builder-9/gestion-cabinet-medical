<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Gestion des comptes du personnel (médecins, secrétaires) par le médecin.
 *
 * Double protection : le middleware « role:doctor » sur les routes, et les
 * Policies (UserPolicy) dans chaque action ou FormRequest.
 */
class StaffController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('staff.index', [
            'members' => User::query()->staff()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('staff.create', [
            'member' => new User,
            'roles' => self::staffRoles(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $member = new User($request->safe()->only(['name', 'email', 'phone', 'password']));
        // Le rôle n'est pas « fillable » : on l'affecte explicitement, après validation.
        $member->role = $request->enum('role', Role::class);
        $member->save();

        return redirect()->route('staff.index')
            ->with('status', "Le compte de {$member->name} a été créé.");
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('staff.edit', [
            'member' => $user,
            'roles' => self::staffRoles(),
        ]);
    }

    public function update(UpdateStaffRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email', 'phone']));

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        if ($request->user()->can('updateRole', $user)) {
            $user->role = $request->enum('role', Role::class);
        }

        $user->save();

        return redirect()->route('staff.index')
            ->with('status', "Le compte de {$user->name} a été modifié.");
    }

    /**
     * Rôles proposés dans le formulaire (secrétaire en premier : cas le plus courant).
     *
     * @return list<Role>
     */
    private static function staffRoles(): array
    {
        return [Role::Secretary, Role::Doctor];
    }
}
