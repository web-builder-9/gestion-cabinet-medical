<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_is_in_french(): void
    {
        $this->assertSame('fr', app()->getLocale());
    }

    public function test_the_login_page_is_in_french(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Adresse e-mail')
            ->assertSee('Mot de passe')
            ->assertSee('Se souvenir de moi')
            ->assertSee('Se connecter');
    }

    public function test_validation_messages_are_in_french(): void
    {
        $this->post('/login', [])->assertSessionHasErrors([
            'email' => 'Le champ adresse e-mail est obligatoire.',
            'password' => 'Le champ mot de passe est obligatoire.',
        ]);
    }

    public function test_wrong_credentials_message_is_in_french(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'mauvais'])
            ->assertSessionHasErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.']);
    }

    public function test_dates_are_formatted_in_french(): void
    {
        $date = CarbonImmutable::parse('2026-10-19');

        $this->assertSame('lundi 19 octobre 2026', $date->translatedFormat('l j F Y'));
    }
}
