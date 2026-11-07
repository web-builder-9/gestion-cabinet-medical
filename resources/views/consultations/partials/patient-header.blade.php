{{--
    Bandeau du patient en consultation (maquette 14) : âge, genre, groupe sanguin,
    allergies et antécédents en évidence. Variables : $patient, $record (peut être null).
--}}
<div class="card flex flex-wrap items-center gap-4 p-5">
    <span class="avatar size-12 text-base">{{ $patient->user->initials }}</span>
    <div class="leading-tight">
        <p class="text-lg font-semibold text-ink">{{ $patient->full_name }}</p>
        <p class="text-sm text-muted">
            {{ $patient->age }} ans · {{ $patient->gender?->label() }}
            @if ($record?->blood_type) · Groupe {{ $record->blood_type->label() }} @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if ($record?->allergies)
            <span class="badge bg-danger text-danger-ink"><x-icon name="warning" class="size-4" /> Allergie : {{ $record->allergies }}</span>
        @endif
        @if ($record?->medical_history)
            <span class="badge bg-neutral text-neutral-ink" title="{{ $record->medical_history }}">{{ Str::limit($record->medical_history, 40) }}</span>
        @endif
    </div>
    <a href="{{ route('patients.show', $patient) }}" class="link ml-auto text-sm">Voir le dossier</a>
</div>
