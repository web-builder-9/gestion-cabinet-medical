<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Personnel</h2>

            @can('create', App\Models\User::class)
                <a href="{{ route('staff.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Ajouter un membre
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-xs sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-6 py-3 font-medium">Nom</th>
                            <th class="px-6 py-3 font-medium">Adresse e-mail</th>
                            <th class="px-6 py-3 font-medium">Téléphone</th>
                            <th class="px-6 py-3 font-medium">Rôle</th>
                            <th class="px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($members as $member)
                            <tr>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ $member->name }}
                                    @if ($member->is(Auth::user()))
                                        <span class="text-gray-400">(vous)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $member->email }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $member->phone ?? '—' }}</td>
                                <td class="px-6 py-4">{{ $member->role->label() }}</td>
                                <td class="px-6 py-4 text-right">
                                    @can('update', $member)
                                        <a href="{{ route('staff.edit', $member) }}" class="text-indigo-600 hover:text-indigo-800">
                                            Modifier
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-gray-500">Aucun membre du personnel.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
