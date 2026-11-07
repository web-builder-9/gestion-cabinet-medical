<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dossier médical de {{ $patient->full_name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-xs sm:rounded-lg">
                <form method="POST" action="{{ route('patients.medical-record.update', $patient) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="max-w-xs">
                        <x-input-label for="blood_type" value="Groupe sanguin" />
                        <select id="blood_type" name="blood_type"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs">
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
                            <textarea id="{{ $field }}" name="{{ $field }}" rows="4" placeholder="{{ $placeholder }}"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs">{{ old($field, $record->{$field}) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get($field)" />
                        </div>
                    @endforeach

                    <div class="flex items-center gap-4">
                        <x-primary-button>Enregistrer le dossier</x-primary-button>
                        <a href="{{ route('patients.show', $patient) }}" class="text-sm text-gray-600 hover:text-gray-900">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
