<x-app-layout :title="'Dossier médical de '.$patient->full_name" subtitle="Visible et modifiable par les médecins uniquement.">
    <form method="POST" action="{{ route('patients.medical-record.update', $patient) }}" class="card">
        @csrf
        @method('PUT')

        <div class="space-y-5 p-5 sm:p-6">
            <div class="sm:w-1/3">
                <x-input-label for="blood_type" value="Groupe sanguin" />
                <select id="blood_type" name="blood_type" class="field">
                    <option value="">Inconnu</option>
                    @foreach ($bloodTypes as $bloodType)
                        <option value="{{ $bloodType->value }}" @selected(old('blood_type', $record->blood_type?->value) === $bloodType->value)>
                            {{ $bloodType->label() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('blood_type')" />
            </div>

            @foreach ([
                'allergies' => ['Allergies', 'Ex. : pénicilline, arachides'],
                'medical_history' => ['Antécédents', 'Maladies, opérations, antécédents familiaux…'],
                'current_treatments' => ['Traitements en cours', 'Médicament, dose, fréquence'],
            ] as $field => [$label, $placeholder])
                <div>
                    <x-input-label :for="$field" :value="$label" />
                    <textarea id="{{ $field }}" name="{{ $field }}" rows="3" placeholder="{{ $placeholder }}" class="field">{{ old($field, $record->{$field}) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get($field)" />
                </div>
            @endforeach
        </div>

        <div class="flex justify-end gap-3 border-t border-line p-5 sm:p-6">
            <a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary">Annuler</a>
            <x-primary-button>Enregistrer le dossier</x-primary-button>
        </div>
    </form>
</x-app-layout>
