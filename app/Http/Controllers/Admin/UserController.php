<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Specialty;
use App\Models\User;
use App\Services\AccountInvitations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Comptes du personnel (BF-23, BF-24), gérés par l'administrateur (maquette 16).
 * Double protection : middleware « role:admin » sur les routes, et UserPolicy
 * dans chaque action ou FormRequest.
 */
class UserController extends Controller
{
    public function __construct(private readonly AccountInvitations $invitations) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        // Onglets : Tous, Médecins, Secrétaires, Administrateurs.
        $role = Role::tryFrom((string) $request->query('role'));
        $role = in_array($role, Role::staff(), true) ? $role : null;

        return view('admin.users.index', [
            'members' => User::query()->staff()
                ->when($role, fn ($q) => $q->where('role', $role))
                ->with('doctor.specialty')
                ->orderBy('last_name')->orderBy('first_name')
                ->get(),
            'role' => $role,
            'roles' => Role::staff(),
            'specialties' => Specialty::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $member = DB::transaction(function () use ($request) {
            $member = new User($request->safe()->only(['last_name', 'first_name', 'email', 'phone']));
            // Rôle et mot de passe ne sont pas « fillable » : affectés explicitement.
            $member->role = $request->enum('role', Role::class);
            // Mot de passe aléatoire que personne ne connaît : la personne choisit le sien via l'invitation.
            $member->password = Str::password(32);
            $member->save();

            if ($member->isDoctor()) {
                $member->doctor()->create(['specialty_id' => $request->integer('specialty_id')]);
            }

            return $member;
        });

        $this->invitations->send($member);

        return redirect()->route('admin.users.index')
            ->with('status', "Le compte de {$member->full_name} a été créé. Un e-mail l'invite à choisir son mot de passe.");
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', [
            'member' => $user->load('doctor'),
            'roles' => Role::staff(),
            'specialties' => Specialty::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user) {
            $user->fill($request->safe()->only(['last_name', 'first_name', 'email', 'phone']));

            if ($request->user()->can('updateRole', $user)) {
                $user->role = $request->enum('role', Role::class);
            }

            $user->save();

            // Un médecin a un profil avec sa spécialité (RG-06). Si le compte cesse d'être
            // médecin, le profil est gardé : ses rendez-vous et consultations y sont liés.
            if ($user->isDoctor()) {
                $user->doctor()->updateOrCreate([], [
                    'specialty_id' => $request->integer('specialty_id'),
                    'presentation' => $request->validated('presentation'),
                ]);
            }
        });

        return redirect()->route('admin.users.index')
            ->with('status', "Le compte de {$user->full_name} a été modifié.");
    }

    /**
     * RG-02 : un compte désactivé ne peut plus se connecter.
     * RG-08 : un médecin qui a des rendez-vous futurs actifs ne peut pas être désactivé.
     */
    public function deactivate(User $user): RedirectResponse
    {
        Gate::authorize('toggleActive', $user);

        $upcoming = $user->doctor?->appointments()->active()->upcoming()->count() ?? 0;

        if ($upcoming > 0) {
            $them = $upcoming === 1 ? "l'annuler ou le déplacer" : 'les annuler ou les déplacer';

            return redirect()->route('admin.users.index')->with(
                'error',
                "{$user->doctor->display_name} n'a pas été désactivé : il a {$upcoming} rendez-vous à venir. "
                ."Le secrétariat doit d'abord {$them}."
            );
        }

        $user->forceFill(['is_active' => false])->save();

        return redirect()->route('admin.users.index')->with('status', "Le compte de {$user->full_name} est désactivé : il ne peut plus se connecter.");
    }

    public function activate(User $user): RedirectResponse
    {
        Gate::authorize('toggleActive', $user);

        $user->forceFill(['is_active' => true])->save();

        return redirect()->route('admin.users.index')->with('status', "Le compte de {$user->full_name} est réactivé.");
    }

    /**
     * Renvoie l'invitation (lien expiré, e-mail perdu…).
     */
    public function invite(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $this->invitations->send($user);

        return back()->with('status', "Une nouvelle invitation a été envoyée à {$user->email}.");
    }
}
