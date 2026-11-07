<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Patients</h2>

            <div class="flex items-center gap-4">
                @can('viewArchived', App\Models\Patient::class)
                    <a href="{{ route('patients.archived') }}" class="text-sm text-gray-600 hover:text-gray-900">Archives</a>
                @endcan

                @can('create', App\Models\Patient::class)
                    <a href="{{ route('patients.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Nouveau patient
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-flash-status />

            <form method="GET" action="{{ route('patients.index') }}" class="flex flex-wrap items-center gap-2" role="search">
                <label for="q" class="sr-only">Rechercher un patient</label>
                <x-text-input id="q" name="q" type="search" :value="$search" class="w-full sm:w-96"
                              placeholder="Nom, prénom, CIN ou téléphone" />
                <x-primary-button>Rechercher</x-primary-button>
                @if ($search !== '')
                    <a href="{{ route('patients.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Effacer</a>
                @endif
            </form>

            <div class="bg-white shadow-xs sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Nom</th>
                            <th class="px-6 py-3 font-medium">CIN</th>
                            <th class="px-6 py-3 font-medium">Date de naissance</th>
                            <th class="px-6 py-3 font-medium">Téléphone</th>
                            <th class="px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($patients as $patient)
                            <tr>
                                <td class="px-6 py-4 font-medium">
                                    <a href="{{ route('patients.show', $patient) }}" class="text-gray-900 hover:text-indigo-700 hover:underline">
                                        {{ $patient->last_name }} {{ $patient->first_name }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $patient->cin ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $patient->birth_date->format('d/m/Y') }}
                                    <span class="text-gray-400">({{ $patient->age }} ans)</span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $patient->phone }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('update', $patient)
                                        <a href="{{ route('patients.edit', $patient) }}" class="text-indigo-600 hover:text-indigo-800">Modifier</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-gray-500">
                                    @if ($search !== '')
                                        Aucun patient ne correspond à « {{ $search }} ».
                                    @else
                                        Aucun patient pour le moment.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $patients->links() }}
        </div>
    </div>
</x-app-layout>
