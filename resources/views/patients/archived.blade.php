<x-app-layout title="Patients archivés" subtitle="Rien n'est effacé : le dossier médical et les rendez-vous sont conservés (RG-22).">
    <x-slot name="actions">
        <a href="{{ route('patients.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" /> Retour à la liste</a>
    </x-slot>

    <div class="card table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>CIN</th>
                    <th>Archivé le</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($patients as $patient)
                    <tr>
                        <td class="font-semibold">{{ $patient->user->last_name }} {{ $patient->user->first_name }}</td>
                        <td class="text-ink-3">{{ $patient->cin ?? '—' }}</td>
                        <td class="text-ink-3">{{ $patient->deleted_at->format('d/m/Y à H:i') }}</td>
                        <td class="text-right">
                            @can('restore', $patient)
                                <form method="POST" action="{{ route('patients.restore', $patient) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-secondary btn-sm">Restaurer</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-muted">Aucun patient archivé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $patients->links('pagination.simple-pages') }}
</x-app-layout>
