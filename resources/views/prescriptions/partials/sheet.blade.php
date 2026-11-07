{{--
    Feuille d'ordonnance (maquette 15), commune à l'aperçu et au PDF (dompdf).
    dompdf ne gère ni flexbox ni grid : mise en page en tableaux, styles de prescriptions/partials/styles.
    Variables : $consultation, $patient, $doctor, $settings.
--}}
@php
    $civility = $patient->gender === App\Enums\Gender::Female ? 'Mme' : 'M.';
    $patientWord = $patient->gender === App\Enums\Gender::Female ? 'Patiente' : 'Patient';
    $place = $settings->city();
@endphp

<div class="rx">
    <table class="rx-head">
        <tr>
            <td>
                <p class="rx-doctor">{{ $doctor->display_name }}</p>
                <p class="rx-specialty">{{ $doctor->specialty->name }}</p>
                <p class="rx-muted">{{ $settings->cabinet_name }}@if ($settings->address)<br>{{ $settings->address }}@endif
                    @if ($settings->phone)<br>Tél. {{ $settings->phone }}@endif</p>
            </td>
            <td class="rx-right">
                <p class="rx-cross">&#10010;</p>
                <p>{{ $place ? $place.', le' : 'Le' }} {{ $consultation->consulted_at->translatedFormat('j F Y') }}</p>
            </td>
        </tr>
    </table>

    <h1 class="rx-title">Ordonnance</h1>

    <p class="rx-patient"><span class="rx-muted">{{ $patientWord }} :</span> <strong>{{ $civility }} {{ $patient->full_name }}</strong>, {{ $patient->age }} ans</p>

    <table class="rx-items">
        @foreach ($consultation->prescriptionItems as $item)
            <tr>
                <td class="rx-number"><span>{{ $item->position }}</span></td>
                <td>
                    <p class="rx-medication">{{ $item->medication }} {{ $item->dosage }}</p>
                    @if ($item->instructions())
                        <p>{{ $item->instructions() }}</p>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    @if ($consultation->treatment)
        <p class="rx-advice"><span class="rx-muted">Recommandations :</span> {{ $consultation->treatment }}</p>
    @endif

    <table class="rx-signature">
        <tr>
            <td></td>
            <td class="rx-signature-box">Signature et cachet</td>
        </tr>
    </table>

    <p class="rx-footer">Ordonnance n° {{ $consultation->prescriptionNumber() }} · éditée depuis le logiciel du cabinet</p>
</div>
