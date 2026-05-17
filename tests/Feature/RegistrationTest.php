<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        Mail::fake();

        $response = $this->post('/register', [
            'email'           => 'test@example.com',
            'restaurant_name' => 'Mi Restaurante Test',
            'phone'           => '+34 600 000 000',
            'plan'            => 'trial',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('register.check-email'));
        $this->assertDatabaseHas('users', [
            'email'             => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $this->assertDatabaseHas('restaurants', [
            'name' => 'Mi Restaurante Test',
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'plan_code' => 'trial',
            'status'    => 'trialing',
        ]);
    }
}
