<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

/**
 * Traducciones EN/FR para la carta demo (selector de idioma en carta pública).
 */
class DemoMenuTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Restaurant::query()->where('subdomain', 'demo')->first();
        if (! $restaurant) {
            $this->command?->warn('No hay restaurante demo.');

            return;
        }

        $categoryLabels = [
            'Entrantes' => [
                'en' => 'Starters',
                'fr' => 'Entrées',
            ],
        ];

        foreach (Category::where('restaurant_id', $restaurant->id)->get() as $category) {
            $labels = $categoryLabels[$category->name] ?? null;
            if (! $labels) {
                continue;
            }
            foreach ($labels as $locale => $name) {
                $category->setTranslation($locale, 'name', $name);
            }
        }

        $productCopy = [
            'Ensalada de la casa' => [
                'en' => ['name' => 'House salad', 'description' => 'Mixed greens, tomato and ventresca tuna.'],
                'fr' => ['name' => 'Salade maison', 'description' => 'Mesclum, tomate et ventrèche.'],
            ],
            'Mejillones al vapor' => [
                'en' => ['name' => 'Steamed mussels', 'description' => 'Sharing portion.'],
                'fr' => ['name' => 'Moules vapeur', 'description' => 'Portion à partager.'],
            ],
        ];

        foreach (Product::where('restaurant_id', $restaurant->id)->get() as $product) {
            $copy = $productCopy[$product->name] ?? null;
            if (! $copy) {
                continue;
            }
            foreach ($copy as $locale => $fields) {
                foreach ($fields as $key => $value) {
                    $product->setTranslation($locale, $key, $value);
                }
            }
        }

        $this->command?->info('Traducciones demo (en, fr) listas para «'.$restaurant->name.'».');
    }
}
