{{--
    Enregistrement (BF-21) ou modification (RG-20) d'une consultation (maquette 14).
    Les lignes de l'ordonnance sont gérées par Alpine.js (ajouter, retirer) ;
    les noms « items[0][medication] »… sont recalculés à chaque changement.
    Variables : $appointment, $consultation, $patient, $record, $items, $history.
--}}
@php
    $editing = $consultation->exists;
    $rows = old('items', $items) ?: [['medication' => '', 'dosage' => '', 'posology' => '', 'duration' => '']];
    $itemErrors = collect($errors->get('items.*'))->flatten()->merge($errors->get('items'));
    $patientWord = $patient->gender === App\Enums\Gender::Female ? 'Patiente allergique' : 'Patient allergique';
@endphp

<x-app-layout :title="'Consultation du '.$appointment->starts_at->translatedFormat('j F Y')"
              :subtitle="$editing ? 'Modification du compte rendu' : 'Rendez-vous de '.$appointment->starts_at->format('H:i').' · il passera à « Terminé » à l\'enregistrement'">
    <form method="POST" action="{{ $editing ? route('consultations.update', $consultation) : route('consultations.store', $appointment) }}" class="space-y-5">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        @include('consultations.partials.patient-header')

        <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Compte rendu</h2></div>
                <div class="space-y-5 p-5 sm:p-6">
                    <div>
                        <x-input-label for="reason" value="Motif" required />
                        <x-text-input id="reason" name="reason" type="text" maxlength="255" required :value="old('reason', $consultation->reason)" />
                        <x-input-error class="mt-2" :messages="$errors->get('reason')" />
                    </div>
                    <div>
                        <x-input-label for="observations" value="Observations" />
                        <textarea id="observations" name="observations" rows="4" maxlength="5000"
                                  @class(['field', 'field-error' => $errors->has('observations')])>{{ old('observations', $consultation->observations) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('observations')" />
                    </div>
                    <div>
                        <x-input-label for="diagnosis" value="Diagnostic" />
                        <x-text-input id="diagnosis" name="diagnosis" type="text" maxlength="255" :value="old('diagnosis', $consultation->diagnosis)" />
                        <x-input-error class="mt-2" :messages="$errors->get('diagnosis')" />
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Historique du patient</h2></div>
                @include('consultations.partials.history')
                <div class="border-t border-line px-5 py-4">
                    <a href="{{ route('patients.show', $patient) }}" class="btn btn-secondary btn-sm">Dossier complet</a>
                </div>
            </section>
        </div>

        <section class="card" x-data="{ items: @js(array_values($rows)) }">
            <div class="card-header">
                <h2 class="card-title">Traitement prescrit</h2>
                <span class="text-sm text-muted">Imprimé ou exporté en PDF sous forme d'ordonnance</span>
            </div>

            <div class="space-y-4 p-5 sm:p-6">
                @if ($record?->allergies)
                    <div class="alert alert-warning" role="note">
                        <x-icon name="warning" />
                        <span>{{ $patientWord }} : {{ $record->allergies }}. Vérifiez la composition de tout médicament avant de le prescrire.</span>
                    </div>
                @endif

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="min-w-48">Médicament</th>
                                <th class="w-32">Dosage</th>
                                <th class="min-w-56">Posologie</th>
                                <th class="w-36">Durée</th>
                                <th class="w-12"><span class="sr-only">Retirer</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td>
                                        <label class="sr-only" :for="'item-' + index + '-medication'">Médicament</label>
                                        <input type="text" class="field mt-0" maxlength="150" placeholder="Ex. : Paracétamol"
                                               :id="'item-' + index + '-medication'" :name="'items[' + index + '][medication]'" x-model="item.medication">
                                    </td>
                                    <td>
                                        <label class="sr-only" :for="'item-' + index + '-dosage'">Dosage</label>
                                        <input type="text" class="field mt-0" maxlength="50" placeholder="500 mg"
                                               :id="'item-' + index + '-dosage'" :name="'items[' + index + '][dosage]'" x-model="item.dosage">
                                    </td>
                                    <td>
                                        <label class="sr-only" :for="'item-' + index + '-posology'">Posologie</label>
                                        <input type="text" class="field mt-0" maxlength="255" placeholder="1 comprimé matin et soir"
                                               :id="'item-' + index + '-posology'" :name="'items[' + index + '][posology]'" x-model="item.posology">
                                    </td>
                                    <td>
                                        <label class="sr-only" :for="'item-' + index + '-duration'">Durée</label>
                                        <input type="text" class="field mt-0" maxlength="50" placeholder="5 jours"
                                               :id="'item-' + index + '-duration'" :name="'items[' + index + '][duration]'" x-model="item.duration">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="rounded-md p-2 text-muted hover:bg-subtle hover:text-danger-ink"
                                                x-on:click="items.splice(index, 1)" :aria-label="'Retirer la ligne ' + (index + 1)">
                                            <x-icon name="close" class="size-5" />
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <x-input-error :messages="$itemErrors->all()" />

                <button type="button" class="link inline-flex items-center gap-1 text-sm font-semibold"
                        x-on:click="items.push({ medication: '', dosage: '', posology: '', duration: '' })"
                        x-show="items.length < {{ App\Http\Requests\Doctor\ConsultationRequest::MAX_ITEMS }}">
                    <x-icon name="plus" class="size-4" /> Ajouter un médicament
                </button>

                <div>
                    <x-input-label for="treatment" value="Recommandations (imprimées sur l'ordonnance)" />
                    <textarea id="treatment" name="treatment" rows="2" maxlength="2000" placeholder="Ex. : repos, boire beaucoup ; revoir si la fièvre persiste"
                              @class(['field', 'field-error' => $errors->has('treatment')])>{{ old('treatment', $consultation->treatment) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('treatment')" />
                </div>
            </div>
        </section>

        <p class="text-sm text-muted">Seul le médecin auteur pourra modifier ce compte rendu. Le patient le verra en lecture seule.</p>

        <div class="flex flex-wrap gap-3">
            <a href="{{ $editing ? route('consultations.show', $consultation) : route('agenda', ['vue' => 'jour', 'date' => $appointment->starts_at->format('Y-m-d')]) }}" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-secondary">Enregistrer</button>
            <button type="submit" name="next" value="prescription" class="btn btn-primary">
                <x-icon name="printer" /> Enregistrer et imprimer l'ordonnance
            </button>
        </div>
    </form>
</x-app-layout>
