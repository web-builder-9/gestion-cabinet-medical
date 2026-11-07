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
            {{-- Rendez-vous confirmé du jour avec le médecin connecté : il peut démarrer la consultation. --}}
            @if ($startable)
                <a href="{{ route('consultations.create', $startable) }}" class="btn btn-primary">
                    <x-icon name="consultation" /> Démarrer la consultation ({{ $startable->starts_at->format('H:i') }})
                </a>
            @endif
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
                {{-- Le patient reçoit un e-mail pour choisir (ou rechoisir) son mot de passe. --}}
                @if ($patient->user->email)
                    @can('update', $patient)
                        <form method="POST" action="{{ route('patients.invite', $patient) }}" class="border-t border-line px-5 py-4">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">Envoyer l'accès à l'espace patient</button>
                        </form>
                    @endcan
                @endif
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
                <div class="card-header">
                    <h2 class="card-title">Rendez-vous à venir</h2>
                    @can('create', App\Models\Appointment::class)
                        <a href="{{ route('appointments.create', ['patient' => $patient->id]) }}" class="btn btn-primary btn-sm">
                            <x-icon name="plus" class="size-4" /> Donner un rendez-vous
                        </a>
                    @endcan
                </div>
                @include('patients.partials.appointments', ['appointments' => $upcoming, 'empty' => 'Aucun rendez-vous à venir.'])
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Derniers rendez-vous</h2></div>
                @include('patients.partials.appointments', ['appointments' => $past, 'empty' => 'Aucun rendez-vous passé.'])
            </section>

            {{-- Historique des consultations, tous médecins (BF-20) : médecins uniquement (RG-21). --}}
            @can('viewAny', App\Models\Consultation::class)
                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title">Historique des consultations</h2>
                        <span class="text-sm text-muted">{{ trans_choice(':count consultation|:count consultations', $consultations->count()) }} · tous médecins</span>
                    </div>
                    <ul class="divide-y divide-line-soft">
                        @forelse ($consultations as $consultation)
                            @php($own = $consultation->appointment->doctor->user_id === Auth::id())
                            <li class="flex gap-3 px-5 py-4 text-sm">
                                <span class="mt-1.5 size-2.5 flex-none rounded-full bg-brand" aria-hidden="true"></span>
                                <div class="space-y-0.5">
                                    <a href="{{ route('consultations.show', $consultation) }}" class="font-semibold text-ink hover:text-brand hover:underline">
                                        {{ $consultation->consulted_at->translatedFormat('j F Y') }} · {{ $consultation->reason }}
                                    </a>
                                    <p class="text-muted">{{ $consultation->appointment->doctor->display_name }}{{ $own ? '' : ' · lecture seule' }}</p>
                                    @if ($consultation->diagnosis)
                                        <p><span class="text-muted">Diagnostic :</span> {{ $consultation->diagnosis }}</p>
                                    @endif
                                    @if ($consultation->prescriptionItems->isNotEmpty())
                                        <p><span class="text-muted">Traitement :</span> {{ $consultation->prescriptionItems->pluck('medication')->implode(', ') }}</p>
                                        <a href="{{ route('consultations.prescription', $consultation) }}" class="link">Voir l'ordonnance</a>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-sm text-muted">Aucune consultation enregistrée.</li>
                        @endforelse
                    </ul>
                </section>
            @endcan
        </div>
    </div>
</x-app-layout>
