<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-2">
                    <p class="text-lg font-medium">Bonjour {{ Auth::user()->name }}</p>
                    <p class="text-sm text-gray-500">{{ ucfirst(now()->translatedFormat('l j F Y')) }}</p>

                    {{-- Le contenu dépend des droits (Policies), pas d'un test direct du rôle. --}}
                    @can('viewAny', App\Models\Appointment::class)
                        <p class="pt-2">
                            Les indicateurs du cabinet (rendez-vous du jour, nombre de patients,
                            recettes du mois) s'afficheront ici.
                        </p>
                    @else
                        <p class="pt-2">
                            Votre espace patient, avec vos prochains rendez-vous, sera bientôt disponible.
                        </p>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
