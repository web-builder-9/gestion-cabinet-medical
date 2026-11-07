<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Comptes du personnel (médecins, secrétaires, administrateurs), gérés par
 * l'administrateur. Double protection : middleware « role:admin » sur les
 * routes, et UserPolicy dans chaque action ou FormRequest.
 */
class StaffController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('staff.index', [
            'members' => User::query()->staff()->with('doctor.specialty')
                ->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        $member = new User;
        $member->role = Role::Secretary; // rôle proposé par défaut

        return view('staff.create', [
            'member' => $member,
            'roles' => Role::staff(),
            'specialties' => Specialty::orderBy('name')->get(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $member = DB::transaction(function () use ($request) {
            $member = new User($request->safe()->only(['last_name', 'first_name', 'email', 'phone', 'password']));
            // Le rôle n'est pas « fillable » : on l'affecte explicitement, après validation.
            $member->role = $request->enum('role', Role::class);
            $member->save();

            $this->syncDoctorProfile($member, $request->integer('specialty_id'));

            return $member;
        });

        return redirect()->route('staff.index')
            ->with('status', "Le compte de {$member->full_name} a été créé.");
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('staff.edit', [
            'member' => $user->load('doctor'),
            'roles' => Role::staff(),
            'specialties' => Specialty::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateStaffRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $user->fill($request->safe()->only(['last_name', 'first_name', 'email', 'phone']));

            if ($request->filled('password')) {
                $user->password = $request->validated('password');
            }

            if ($request->user()->can('updateRole', $user)) {
                $user->role = $request->enum('role', Role::class);
            }

            $user->save();
            $this->syncDoctorProfile($user, $request->integer('specialty_id'));
        });

        return redirect()->route('staff.index')
            ->with('status', "Le compte de {$user->full_name} a été modifié.");
    }

    /**
     * Un médecin a un profil (table doctors) avec sa spécialité (RG-06).
     * Si le compte cesse d'être médecin, le profil est gardé : ses rendez-vous y sont liés.
     */
    private function syncDoctorProfile(User $user, int $specialtyId): void
    {
        if ($user->isDoctor()) {
            $user->doctor()->updateOrCreate([], ['specialty_id' => $specialtyId]);
        }
    }
}
