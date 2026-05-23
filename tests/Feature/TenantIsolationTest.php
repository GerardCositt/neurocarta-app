<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRestaurant(string $email): array
    {
        $user    = User::factory()->create(['email' => $email, 'email_verified_at' => now()]);
        $account = Account::create(['name' => $email]);
        $account->users()->attach($user);

        $restaurant = Restaurant::create([
            'account_id' => $account->id,
            'name'       => "Restaurant of {$email}",
            'subdomain'  => str_replace(['@', '.'], '-', $email),
        ]);

        // Active trial so CheckSubscription passes
        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'trial',
            'status'                => 'trialing',
            'current_period_end_at' => now()->addDays(7),
        ]);

        return [$user, $account, $restaurant];
    }

    private function makeProduct(Restaurant $restaurant, string $name): Product
    {
        $category = Category::withoutGlobalScopes()->firstOrCreate(
            ['name' => 'Test Cat', 'restaurant_id' => $restaurant->id],
            ['hidden' => false, 'order' => 1]
        );

        return Product::withoutGlobalScopes()->create([
            'name'          => $name,
            'restaurant_id' => $restaurant->id,
            'category_id'   => $category->id,
            'hidden'        => false,
            'price'         => '10.00',
            'order'         => 1,
        ]);
    }

    public function test_user_cannot_see_another_tenants_products(): void
    {
        [$userA, , $restaurantA] = $this->makeUserWithRestaurant('a@example.com');
        [$userB, , $restaurantB] = $this->makeUserWithRestaurant('b@example.com');

        $this->makeProduct($restaurantA, 'Pizza A');

        // User B sees their own panel — should not see Restaurant A's products
        $response = $this->actingAs($userB)
            ->withSession(['admin_restaurant_id' => $restaurantB->id])
            ->get('/product');

        $response->assertSuccessful();
        $response->assertDontSee('Pizza A');
    }

    public function test_user_can_see_own_restaurants_products(): void
    {
        [$userA, , $restaurantA] = $this->makeUserWithRestaurant('c@example.com');

        $this->makeProduct($restaurantA, 'Burger C');

        $response = $this->actingAs($userA)
            ->withSession(['admin_restaurant_id' => $restaurantA->id])
            ->get('/product');

        $response->assertSuccessful();
        $response->assertSee('Burger C');
    }
}
