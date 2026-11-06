<x-app-layout :title="'Modifier le compte de '.$member->full_name">
    <form method="POST" action="{{ route('staff.update', $member) }}" class="card max-w-3xl">
        @csrf
        @method('PUT')

        <div class="p-5 sm:p-6">
            @include('staff.partials.form')
        </div>

        <div class="flex justify-end gap-3 border-t border-line p-5 sm:p-6">
            <a href="{{ route('staff.index') }}" class="btn btn-secondary">Annuler</a>
            <x-primary-button>Enregistrer</x-primary-button>
        </div>
    </form>
</x-app-layout>
