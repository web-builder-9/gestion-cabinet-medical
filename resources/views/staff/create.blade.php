<x-app-layout :title="'Créer un compte'">
    <form method="POST" action="{{ route('staff.store') }}" class="card max-w-3xl">
        @csrf

        <div class="p-5 sm:p-6">
            @include('staff.partials.form')
        </div>

        <div class="flex justify-end gap-3 border-t border-line p-5 sm:p-6">
            <a href="{{ route('staff.index') }}" class="btn btn-secondary">Annuler</a>
            <x-primary-button>Créer le compte</x-primary-button>
        </div>
    </form>
</x-app-layout>
