<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run()
    {
        Restaurant::firstOrCreate(
            ['subdomain' => 'carta'],
            ['name' => 'Bar Jaen III']
        );

        Restaurant::firstOrCreate(
            ['subdomain' => 'elpuerto'],
            ['name' => 'El Puerto']
        );
    }
}
