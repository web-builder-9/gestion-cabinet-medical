<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nouveau patient</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-xs sm:rounded-lg">
                <form method="POST" action="{{ route('patients.store') }}" class="space-y-6">
                    @csrf

                    @include('patients.partials.form')

                    <p class="text-sm text-gray-500">
                        Le dossier médical du patient est créé automatiquement ; le médecin pourra le compléter.
                    </p>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Ajouter le patient</x-primary-button>
                        <a href="{{ route('patients.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
