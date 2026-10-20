<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Patients</h2>
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($patients as $patient)
                            <tr>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ $patient->last_name }} {{ $patient->first_name }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $patient->cin ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $patient->birth_date->format('d/m/Y') }}
                                    <span class="text-gray-400">({{ $patient->age }} ans)</span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $patient->phone }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-gray-500">
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
