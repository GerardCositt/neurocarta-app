<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

/**
 * Platos de ejemplo para el primer restaurante (útil en Codespace / BD vacía).
 * No se ejecuta en DatabaseSeeder por defecto; llama:
 *   php artisan db:seed --class=DemoMenuSeeder
 */
class DemoMenuSeeder extends Seeder
{
    public function run()
    {
        $restaurant = Restaurant::first();
        if (! $restaurant) {
            $this->command->error('No hay restaurantes. Ejecuta antes: php artisan db:seed --class=RestaurantSeeder');

            return;
        }

        if (Category::where('restaurant_id', $restaurant->id)->exists()) {
            $this->command->info('Ya existen categorías para «'.$restaurant->name.'»; no se inserta menú demo.');

            return;
        }

        $category = Category::create([
            'name'          => 'Entrantes',
            'active'        => false,
            'order'         => 1,
            'restaurant_id' => $restaurant->id,
        ]);

        Product::create([
            'name'          => 'Ensalada de la casa',
            'description'   => 'Mezcla de lechugas, tomate y ventresca.',
            'active'        => false,
            'offer'         => false,
            'featured'      => true,
            'recommended'   => false,
            'price'         => '9,50 €',
            'category_id'   => $category->id,
            'restaurant_id' => $restaurant->id,
            'order'         => 1,
        ]);

        Product::create([
            'name'          => 'Mejillones al vapor',
            'description'   => 'Ración para compartir.',
            'active'        => false,
            'offer'         => false,
            'featured'      => false,
            'recommended'   => true,
            'price'         => '12,00 €',
            'category_id'   => $category->id,
            'restaurant_id' => $restaurant->id,
            'order'         => 2,
        ]);

        $this->command->info('Menú demo creado para «'.$restaurant->name.'».');
    }
}
