{{-- Tableau de bord provisoire : chaque rôle aura le sien (secrétariat, médecin, patient). --}}
<x-app-layout :title="'Bonjour '.Auth::user()->first_name" :subtitle="ucfirst(now()->translatedFormat('l j F Y'))">
    <div class="card p-6 text-sm text-ink-3">
        @can('viewAny', App\Models\Appointment::class)
            Les indicateurs du cabinet (rendez-vous du jour, demandes à confirmer, absences) s'afficheront ici.
        @else
            Votre espace patient, avec vos prochains rendez-vous, sera bientôt disponible.
        @endcan
    </div>
</x-app-layout>
