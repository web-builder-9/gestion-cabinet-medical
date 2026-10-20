<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ajouter un membre du personnel</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-xs sm:rounded-lg">
                <form method="POST" action="{{ route('staff.store') }}" class="max-w-xl space-y-6">
                    @csrf

                    @include('staff.partials.form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>Créer le compte</x-primary-button>
                        <a href="{{ route('staff.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
