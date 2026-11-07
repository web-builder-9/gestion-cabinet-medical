<x-guest-layout title="Connexion" subtitle="Accédez à votre espace : patient, secrétariat, médecin ou administration.">
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="link text-[13px]" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                @endif
            </div>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex items-center gap-2 text-sm text-ink-3">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-line-strong text-brand focus:ring-brand">
            {{ __('Remember me') }}
        </label>

        <x-primary-button class="w-full">{{ __('Log in') }}</x-primary-button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-muted">
                Pas encore de compte ? <a href="{{ route('register') }}" class="link">Créer mon compte patient</a>
            </p>
        @endif
    </form>
</x-guest-layout>
