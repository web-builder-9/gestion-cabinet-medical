<x-app-layout title="Nouveau dossier patient">
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <x-icon name="warning" />
            <span><strong>Le dossier n'a pas été créé.</strong> Corrigez les champs signalés ; ce patient existe peut-être déjà.</span>
        </div>
    @endif

    <form method="POST" action="{{ route('patients.store') }}" class="card">
        @csrf

        @include('patients.partials.form')

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-line p-5 sm:p-6">
            <a href="{{ route('patients.index') }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-secondary">Enregistrer le dossier</button>
            {{-- Maquette 9 : enchaîner directement sur la prise de rendez-vous. --}}
            <button type="submit" name="next" value="appointment" class="btn btn-primary">Enregistrer et donner un rendez-vous</button>
        </div>
    </form>
</x-app-layout>
