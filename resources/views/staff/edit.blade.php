<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifier le compte de {{ $member->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-xs sm:rounded-lg">
                <form method="POST" action="{{ route('staff.update', $member) }}" class="max-w-xl space-y-6">
                    @csrf
                    @method('PUT')

                    @include('staff.partials.form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>Enregistrer</x-primary-button>
                        <a href="{{ route('staff.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
