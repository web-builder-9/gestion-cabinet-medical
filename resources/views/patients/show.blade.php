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
