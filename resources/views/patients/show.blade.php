<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $patient->full_name }}</h2>
                <p class="text-sm text-gray-500">{{ $patient->gender->label() }} · {{ $patient->age }} ans</p>
            </div>

            <div class="flex items-center gap-3">
                @can('update', $patient)
                    <a href="{{ route('patients.edit', $patient) }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                        Modifier
                    </a>
                @endcan

                @can('delete', $patient)
                    <form method="POST" action="{{ route('patients.destroy', $patient) }}"
                          onsubmit="return confirm(@js("Archiver la fiche de {$patient->full_name} ? Elle pourra être restaurée."))">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>Supprimer</x-danger-button>
                    </form>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-flash-status />

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="p-6 bg-white shadow-xs sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Identité</h3>
                    <dl class="mt-4 grid grid-cols-3 gap-x-4 gap-y-3 text-sm">
                        <dt class="text-gray-500">Nom</dt>
                        <dd class="col-span-2 text-gray-900">{{ $patient->last_name }}</dd>
                        <dt class="text-gray-500">Prénom</dt>
                        <dd class="col-span-2 text-gray-900">{{ $patient->first_name }}</dd>
                        <dt class="text-gray-500">Date de naissance</dt>
                        <dd class="col-span-2 text-gray-900">{{ $patient->birth_date->translatedFormat('j F Y') }}</dd>
                        <dt class="text-gray-500">CIN</dt>
                        <dd class="col-span-2 text-gray-900">{{ $patient->cin ?? '—' }}</dd>
                    </dl>
                </section>

                <section class="p-6 bg-white shadow-xs sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Coordonnées</h3>
                    <dl class="mt-4 grid grid-cols-3 gap-x-4 gap-y-3 text-sm">
                        <dt class="text-gray-500">Téléphone</dt>
                        <dd class="col-span-2 text-gray-900">{{ $patient->phone }}</dd>
                        <dt class="text-gray-500">Adresse e-mail</dt>
                        <dd class="col-span-2 text-gray-900">{{ $patient->email ?? '—' }}</dd>
                        <dt class="text-gray-500">Adresse</dt>
                        <dd class="col-span-2 text-gray-900">{{ $patient->address ?? '—' }}</dd>
                    </dl>
                </section>
            </div>

            {{-- Données de santé : visibles par le médecin uniquement (MedicalRecordPolicy). --}}
            @can('view', $record)
                <section class="p-6 bg-white shadow-xs sm:rounded-lg">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <h3 class="text-lg font-medium text-gray-900">Dossier médical</h3>
                        @can('update', $record)
                            <a href="{{ route('patients.medical-record.edit', $patient) }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                                Modifier le dossier
                            </a>
                        @endcan
                    </div>

                    <dl class="mt-4 grid gap-x-4 gap-y-3 text-sm sm:grid-cols-4">
                        <dt class="text-gray-500">Groupe sanguin</dt>
                        <dd class="sm:col-span-3 text-gray-900">{{ $record->blood_type?->label() ?? 'Inconnu' }}</dd>

                        <dt class="text-gray-500">Allergies</dt>
                        <dd class="sm:col-span-3">
                            @if ($record->allergies)
                                <span class="font-medium text-red-700 whitespace-pre-line">{{ $record->allergies }}</span>
                            @else
                                <span class="text-gray-500">Aucune allergie connue</span>
                            @endif
                        </dd>

                        <dt class="text-gray-500">Antécédents</dt>
                        <dd class="sm:col-span-3 text-gray-900 whitespace-pre-line">{{ $record->medical_history ?? '—' }}</dd>

                        <dt class="text-gray-500">Traitements en cours</dt>
                        <dd class="sm:col-span-3 text-gray-900 whitespace-pre-line">{{ $record->current_treatments ?? '—' }}</dd>
                    </dl>
                </section>
            @endcan

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="p-6 bg-white shadow-xs sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Rendez-vous à venir</h3>
                    <div class="mt-4">
                        @include('patients.partials.appointments', [
                            'appointments' => $upcoming,
                            'empty' => 'Aucun rendez-vous à venir.',
                        ])
                    </div>
                </section>

                <section class="p-6 bg-white shadow-xs sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">Derniers rendez-vous</h3>
                    <div class="mt-4">
                        @include('patients.partials.appointments', [
                            'appointments' => $past,
                            'empty' => 'Aucun rendez-vous passé.',
                        ])
                    </div>
                </section>
            </div>

            <a href="{{ route('patients.index') }}" class="inline-block text-sm text-gray-600 hover:text-gray-900">
                &larr; Retour à la liste
            </a>
        </div>
    </div>
</x-app-layout>
