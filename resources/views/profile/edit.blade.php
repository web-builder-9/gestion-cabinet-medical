<x-app-layout :title="__('Profile')" subtitle="Vos informations personnelles et votre mot de passe.">
    <div class="grid gap-5 lg:grid-cols-2">
        <div class="card p-5 sm:p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="card p-5 sm:p-6">
            @include('profile.partials.update-password-form')
        </div>
    </div>
</x-app-layout>
