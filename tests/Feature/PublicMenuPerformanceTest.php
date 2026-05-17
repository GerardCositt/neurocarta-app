<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicMenuPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @group performance */
    public function test_public_menu_query_count_with_large_catalog(): void
    {
        $account = Account::create(['name' => 'Perf account']);
        $restaurant = Restaurant::create([
            'account_id' => $account->id,
            'name'       => 'Perf Restaurant',
            'subdomain'  => 'perf-restaurant',
        ]);

        Subscription::create([
            'account_id'            => $account->id,
            'plan_code'             => 'trial',
            'status'                => 'trialing',
            'current_period_end_at' => now()->addDays(7),
        ]);

        for ($c = 1; $c <= 12; $c++) {
            $category = Category::withoutGlobalScopes()->create([
                'restaurant_id' => $restaurant->id,
                'name'          => "Category {$c}",
                'active'        => false,
                'order'         => $c,
            ]);

            for ($p = 1; $p <= 10; $p++) {
                Product::withoutGlobalScopes()->create([
                    'restaurant_id' => $restaurant->id,
                    'category_id'   => $category->id,
                    'name'          => "Dish {$c}-{$p}",
                    'description'   => 'Test description',
                    'active'        => false,
                    'price'         => '12.50',
                    'order'         => $p,
                    'offer'         => $c === 1 && $p <= 3,
                ]);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get('/?restaurant='.$restaurant->id);

        $response->assertSuccessful();

        $queryCount = count(DB::getQueryLog());

        $this->assertLessThan(
            22,
            $queryCount,
            "Public menu issued {$queryCount} SQL queries for 120 products; expected fewer than 22."
        );
    }
}
