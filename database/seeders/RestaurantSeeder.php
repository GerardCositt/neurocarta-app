<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run()
    {
        // Misma intención que la migración add_ai_credits: el restaurante demo «carta» debe poder usar IA
        // sin créditos en saldo (demo ilimitada). Sin OPENAI_API_KEY en .env o key por restaurante, la IA sigue desactivada.
        Restaurant::updateOrCreate(
            ['subdomain' => 'carta'],
            [
                'name' => 'Bar Jaen III',
                'ai_demo_unlimited' => true,
                'ai_credits' => 999_999,
            ]
        );

        Restaurant::firstOrCreate(
            ['subdomain' => 'elpuerto'],
            ['name' => 'El Puerto']
        );
    }
}
