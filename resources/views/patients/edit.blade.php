<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifier la fiche de {{ $patient->full_name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-xs sm:rounded-lg">
                <form method="POST" action="{{ route('patients.update', $patient) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('patients.partials.form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>Enregistrer</x-primary-button>
                        <a href="{{ route('patients.show', $patient) }}" class="text-sm text-gray-600 hover:text-gray-900">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
