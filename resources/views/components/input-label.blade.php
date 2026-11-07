@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'label']) }}>
    {{ $value ?? $slot }}@if ($required)<span class="text-danger-ink" aria-hidden="true"> *</span>@endif
</label>
