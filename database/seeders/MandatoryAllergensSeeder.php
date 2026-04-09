<?php

namespace Database\Seeders;

use App\Models\Allergen;
use Illuminate\Database\Seeder;

class MandatoryAllergensSeeder extends Seeder
{
    /**
     * Los 14 alérgenos obligatorios en etiquetado UE (Reg. 1169/2011), con pictogramas incluidos en public/allergens/official/.
     */
    public function run(): void
    {
        $rows = [
            ['slug' => 'cereales-gluten', 'name' => 'Cereales con gluten', 'file' => 'cereales_gluten.png', 'sort' => 1],
            ['slug' => 'crustaceos', 'name' => 'Crustáceos', 'file' => 'crustaceos.png', 'sort' => 2],
            ['slug' => 'huevos', 'name' => 'Huevos', 'file' => 'huevos.png', 'sort' => 3],
            ['slug' => 'pescado', 'name' => 'Pescado', 'file' => 'pescado.png', 'sort' => 4],
            ['slug' => 'cacahuetes', 'name' => 'Cacahuetes', 'file' => 'cacahuetes.png', 'sort' => 5],
            ['slug' => 'soja', 'name' => 'Soja', 'file' => 'soja.png', 'sort' => 6],
            ['slug' => 'lacteos', 'name' => 'Lácteos', 'file' => 'lacteos.png', 'sort' => 7],
            ['slug' => 'frutos-cascara', 'name' => 'Frutos de cáscara', 'file' => 'frutos_cascara.png', 'sort' => 8],
            ['slug' => 'apio', 'name' => 'Apio', 'file' => 'apio.png', 'sort' => 9],
            ['slug' => 'mostaza', 'name' => 'Mostaza', 'file' => 'mostaza.png', 'sort' => 10],
            ['slug' => 'sesamo', 'name' => 'Sésamo', 'file' => 'sesamo.png', 'sort' => 11],
            ['slug' => 'sulfitos', 'name' => 'Sulfitos', 'file' => 'sulfitos.png', 'sort' => 12],
            ['slug' => 'altramuz', 'name' => 'Altramuz', 'file' => 'altramuz.png', 'sort' => 13],
            ['slug' => 'moluscos', 'name' => 'Moluscos', 'file' => 'moluscos.png', 'sort' => 14],
        ];

        foreach ($rows as $row) {
            Allergen::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name'        => $row['name'],
                    'image'       => 'allergens/official/' . $row['file'],
                    'is_official' => true,
                    'sort_order'  => $row['sort'],
                    'active'      => false,
                ]
            );
        }
    }
}
