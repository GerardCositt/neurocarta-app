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
            'Entrantes' => ['en' => 'Starters', 'fr' => 'Entrées'],
            'Principales' => ['en' => 'Main courses', 'fr' => 'Plats'],
            'Postres' => ['en' => 'Desserts', 'fr' => 'Desserts'],
            'Bebidas' => ['en' => 'Drinks', 'fr' => 'Boissons'],
        ];

        foreach (Category::where('restaurant_id', $restaurant->id)->get() as $category) {
            $labels = $categoryLabels[$category->name] ?? [
                'en' => $category->name,
                'fr' => $category->name,
            ];
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
            if ($copy) {
                foreach ($copy as $locale => $fields) {
                    foreach ($fields as $key => $value) {
                        $product->setTranslation($locale, $key, $value);
                    }
                }

                continue;
            }

            // Fallback: al menos nombre en EN/FR para que el cambio de idioma se note en la carta.
            $product->setTranslation('en', 'name', $product->name);
            $product->setTranslation('fr', 'name', $product->name);
            if ($product->description) {
                $product->setTranslation('en', 'description', $product->description);
                $product->setTranslation('fr', 'description', $product->description);
            }
        }

        $this->command?->info('Traducciones demo (en, fr) listas para «'.$restaurant->name.'».');
    }
}
