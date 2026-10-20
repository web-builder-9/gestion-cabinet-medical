{{-- Message de confirmation après une action (redirect()->with('status', '...')). --}}
@if (session('status'))
    <div {{ $attributes->merge(['class' => 'p-4 rounded-md bg-green-50 text-sm text-green-800']) }} role="status">
        {{ session('status') }}
    </div>
@endif
