<?php

namespace Database\Seeders;

use App\Models\Allergen;
use App\Support\OfficialAllergens;
use Illuminate\Database\Seeder;

class MandatoryAllergensSeeder extends Seeder
{
    public function run(): void
    {
        foreach (OfficialAllergens::list() as $row) {
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
