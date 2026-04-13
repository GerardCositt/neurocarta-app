<?php

namespace Database\Seeders;

use App\Models\Advice;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class AdviceSeeder extends Seeder
{
    /**
     * Avisos de ejemplo para el primer restaurante (panel /advice).
     */
    public function run()
    {
        $restaurant = Restaurant::query()->first();
        if (! $restaurant) {
            return;
        }

        if (Advice::query()->where('restaurant_id', $restaurant->id)->exists()) {
            return;
        }

        Advice::query()->create([
            'restaurant_id' => $restaurant->id,
            'title'         => 'Bienvenida a la carta digital',
            'advice'        => 'Consulta al personal si tienes alergias o dudas sobre los platos.',
            'status'        => true,
            'starts_at'     => null,
            'ends_at'       => null,
        ]);
    }
}
