@props(['disabled' => false])

{{-- Champ de saisie ; il passe en rouge quand le champ a une erreur de validation. --}}
<input @disabled($disabled) {{ $attributes->class(['field', 'field-error' => $errors->has($attributes->get('name'))]) }}>
