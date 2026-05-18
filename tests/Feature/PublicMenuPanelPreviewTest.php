<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Restaurant;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que "Ver carta" desde el panel (app.neurocarta.ai) muestra la carta pública
 * correctamente usando sesión, cookie o ?restaurant=, y que hosts que no son el panel
 * siguen redirigiendo al login/dashboard.
 */
class PublicMenuPanelPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function makeRestaurantWithTrial(): Restaurant
    {
        $account = Account::create(['name' => 'Panel Preview Account ' . uniqid()]);

        $restaurant = Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Panel Preview Restaurant',
            'subdomain'  => 'panel-preview-' . uniqid(),
        ]);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'trial',
            'status'                => 'trialing',
            'current_period_end_at' => now()->addDays(5),
        ]);

        return $restaurant;
    }

    /** Configura el host como si fuera el panel de producción (APP_URL). */
    private function asPanelHost(): static
    {
        config(['app.url' => 'https://app.neurocarta.ai']);

        return $this->withServerVariables([
            'HTTP_HOST'   => 'app.neurocarta.ai',
            'SERVER_NAME' => 'app.neurocarta.ai',
            'HTTPS'       => 'on',
        ]);
    }

    public function test_panel_host_with_restaurant_query_shows_public_menu(): void
    {
        $restaurant = $this->makeRestaurantWithTrial();

        $response = $this->asPanelHost()->get('/?restaurant=' . $restaurant->id);

        $response->assertSuccessful();
    }

    public function test_panel_host_with_session_shows_public_menu(): void
    {
        $restaurant = $this->makeRestaurantWithTrial();

        $response = $this->asPanelHost()
            ->withSession(['admin_restaurant_id' => $restaurant->id])
            ->get('/');

        $response->assertSuccessful();
    }

    public function test_panel_host_without_preview_context_redirects_guest_to_login(): void
    {
        $response = $this->asPanelHost()->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_panel_host_without_preview_context_redirects_authed_to_dashboard(): void
    {
        $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);

        $account = Account::create(['name' => 'Auth account']);
        $account->users()->attach($user);

        Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Auth Restaurant',
            'subdomain'  => 'auth-rest-' . uniqid(),
        ]);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'trial',
            'status'                => 'trialing',
            'current_period_end_at' => now()->addDays(5),
        ]);

        $response = $this->asPanelHost()
            ->actingAs($user)
            ->get('/');

        // Sin preview context → va al panel, no a la carta
        $response->assertRedirect(route('dashboard'));
    }

    public function test_restaurant_subdomain_still_resolves_correctly(): void
    {
        $restaurant = $this->makeRestaurantWithTrial();

        // Seguir posibles redirects canónicos (p.ej. ?restaurant= añadido por ProductController).
        $response = $this->followingRedirects()->withServerVariables([
            'HTTP_HOST'   => $restaurant->subdomain . '.neurocarta.ai',
            'SERVER_NAME' => $restaurant->subdomain . '.neurocarta.ai',
        ])->get('/');

        $response->assertSuccessful();
    }

    public function test_non_panel_central_host_redirects_guest_to_login(): void
    {
        // www.neurocarta.ai no es el panel ni un subdominio de restaurante.
        config(['app.url' => 'https://app.neurocarta.ai']);

        $response = $this->withServerVariables([
            'HTTP_HOST'   => 'www.neurocarta.ai',
            'SERVER_NAME' => 'www.neurocarta.ai',
        ])->get('/');

        $response->assertRedirect(route('login'));
    }
}
