<x-app-layout :title="$patient->full_name">
    <x-flash-status />

    {{-- En-tête du dossier (maquette 13). --}}
    <div class="card flex flex-wrap items-center justify-between gap-4 p-5">
        <div class="flex items-center gap-4">
            <span class="avatar size-14 text-lg">{{ $patient->user->initials }}</span>
            <div>
                <p class="text-xl font-bold text-ink">{{ $patient->full_name }}</p>
                <p class="text-sm text-muted">
                    {{ $patient->age }} ans · {{ $patient->gender->label() }} · {{ $patient->user->phone }}
                    · Patient{{ $patient->gender === App\Enums\Gender::Female ? 'e' : '' }} depuis {{ $patient->created_at->translatedFormat('F Y') }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @can('update', $patient)
                <a href="{{ route('patients.edit', $patient) }}" class="btn btn-secondary"><x-icon name="edit" /> Modifier</a>
            @endcan
            @can('delete', $patient)
                <form method="POST" action="{{ route('patients.destroy', $patient) }}"
                      onsubmit="return confirm(@js("Archiver le dossier de {$patient->full_name} ? Il pourra être restauré."))">
                    @csrf
                    @method('DELETE')
                    <x-danger-button><x-icon name="archive" /> Archiver</x-danger-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="flex flex-col gap-5">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Identité et coordonnées</h2></div>
                <dl class="grid grid-cols-3 gap-x-4 gap-y-3 p-5 text-sm">
                    <dt class="text-muted">Date de naissance</dt>
                    <dd class="col-span-2">{{ $patient->birth_date->translatedFormat('j F Y') }}</dd>
                    <dt class="text-muted">CIN</dt>
                    <dd class="col-span-2">{{ $patient->cin ?? '—' }}</dd>
                    <dt class="text-muted">Téléphone</dt>
                    <dd class="col-span-2">{{ $patient->user->phone }}</dd>
                    <dt class="text-muted">Adresse e-mail</dt>
                    <dd class="col-span-2 break-all">{{ $patient->user->email ?? '—' }}</dd>
                    <dt class="text-muted">Adresse</dt>
                    <dd class="col-span-2">{{ $patient->address ?? '—' }}</dd>
                    <dt class="text-muted">Espace patient</dt>
                    <dd class="col-span-2">{{ $patient->hasPortalAccess() ? 'Accès en ligne actif' : 'Pas d\'accès en ligne' }}</dd>
                </dl>
            </section>

            {{-- Données de santé : visibles par les médecins uniquement (MedicalRecordPolicy, RG-21). --}}
            @can('view', $record)
                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title">Dossier médical</h2>
                        <span class="flex items-center gap-1 text-xs font-semibold text-brand"><x-icon name="lock" class="size-4" /> Médecins uniquement</span>
                    </div>

                    <dl class="grid grid-cols-3 gap-x-4 gap-y-3 p-5 text-sm">
                        <dt class="text-muted">Groupe sanguin</dt>
                        <dd class="col-span-2 font-semibold">{{ $record->blood_type?->label() ?? 'Inconnu' }}</dd>

                        <dt class="text-muted">Allergies</dt>
                        <dd class="col-span-2">
                            @if ($record->allergies)
                                <span class="badge bg-danger text-danger-ink">{{ $record->allergies }}</span>
                            @else
                                <span class="text-muted">Aucune allergie connue</span>
                            @endif
                        </dd>

                        <dt class="text-muted">Antécédents</dt>
                        <dd class="col-span-2 whitespace-pre-line">{{ $record->medical_history ?? '—' }}</dd>

                        <dt class="text-muted">Traitements en cours</dt>
                        <dd class="col-span-2 whitespace-pre-line">{{ $record->current_treatments ?? '—' }}</dd>
                    </dl>

                    @can('update', $record)
                        <div class="px-5 pb-5">
                            <a href="{{ route('patients.medical-record.edit', $patient) }}" class="btn btn-secondary btn-sm">Modifier le dossier médical</a>
                        </div>
                    @endcan
                </section>
            @endcan
        </div>

        <div class="flex flex-col gap-5">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Rendez-vous à venir</h2></div>
                @include('patients.partials.appointments', ['appointments' => $upcoming, 'empty' => 'Aucun rendez-vous à venir.'])
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Derniers rendez-vous</h2></div>
                @include('patients.partials.appointments', ['appointments' => $past, 'empty' => 'Aucun rendez-vous passé.'])
            </section>
        </div>
    </div>
</x-app-layout>
