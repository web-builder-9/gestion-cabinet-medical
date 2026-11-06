<x-app-layout title="Utilisateurs" subtitle="Comptes du personnel : un seul rôle par compte.">
    <x-slot name="actions">
        @can('create', App\Models\User::class)
            <a href="{{ route('staff.create') }}" class="btn btn-primary"><x-icon name="plus" /> Créer un compte</a>
        @endcan
    </x-slot>

    <x-flash-status />

    <div class="card table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Rôle et spécialité</th>
                    <th>Téléphone</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($members as $member)
                    <tr>
                        <td>
                            <div class="font-semibold">{{ $member->doctor ? $member->doctor->display_name : $member->full_name }}</div>
                            <div class="text-xs text-muted">{{ $member->email }}</div>
                        </td>
                        <td>
                            <span class="badge bg-brand-soft text-brand">{{ $member->role->label() }}</span>
                            @if ($member->doctor)
                                <span class="ml-1 text-ink-3">{{ $member->doctor->specialty->name }}</span>
                            @endif
                        </td>
                        <td class="text-ink-3">{{ $member->phone ?? '—' }}</td>
                        <td class="text-right">
                            @if ($member->is(Auth::user()))
                                <span class="mr-3 text-sm text-muted">Vous</span>
                            @endif
                            @can('update', $member)
                                <a href="{{ route('staff.edit', $member) }}" class="btn btn-secondary btn-sm">Modifier</a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
