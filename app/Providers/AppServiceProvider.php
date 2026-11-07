<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // RG-04 : au moins 8 caractères, dont au moins une lettre et un chiffre.
        // S'applique partout où Breeze et nos formulaires utilisent Password::defaults().
        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
