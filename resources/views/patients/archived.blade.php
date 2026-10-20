<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Patients archivés</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <p class="text-sm text-gray-600">
                Les fiches archivées n'apparaissent plus dans la liste des patients, mais rien n'est effacé :
                dossier médical et rendez-vous sont conservés. Restaurer une fiche la remet dans la liste.
            </p>

            <div class="bg-white shadow-xs sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Nom</th>
                            <th class="px-6 py-3 font-medium">CIN</th>
                            <th class="px-6 py-3 font-medium">Archivé le</th>
                            <th class="px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($patients as $patient)
                            <tr>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $patient->last_name }} {{ $patient->first_name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $patient->cin ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $patient->deleted_at->format('d/m/Y à H:i') }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('restore', $patient)
                                        <form method="POST" action="{{ route('patients.restore', $patient) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-800">Restaurer</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-gray-500">Aucun patient archivé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $patients->links() }}

            <a href="{{ route('patients.index') }}" class="inline-block text-sm text-gray-600 hover:text-gray-900">
                &larr; Retour à la liste
            </a>
        </div>
    </div>
</x-app-layout>
