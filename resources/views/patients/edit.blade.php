<x-app-layout :title="'Modifier le dossier de '.$patient->full_name">
    <form method="POST" action="{{ route('patients.update', $patient) }}" class="card">
        @csrf
        @method('PUT')

        @include('patients.partials.form')

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line p-5 sm:p-6">
            <a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary">Annuler</a>
            <x-primary-button>Enregistrer</x-primary-button>
        </div>
    </form>
</x-app-layout>
