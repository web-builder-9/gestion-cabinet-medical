{{-- Messages après une action : redirect()->with('status', '…') ou ->with('error', '…'). --}}
@if (session('status'))
    <div {{ $attributes->merge(['class' => 'alert alert-success']) }} role="status">
        <x-icon name="check-circle" />
        <span>{{ session('status') }}</span>
    </div>
@endif

@if (session('error'))
    <div {{ $attributes->merge(['class' => 'alert alert-danger']) }} role="alert">
        <x-icon name="warning" />
        <span>{{ session('error') }}</span>
    </div>
@endif
