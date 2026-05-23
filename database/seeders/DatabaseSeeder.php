<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        if (app()->isProduction()) {
            $this->error('DatabaseSeeder está deshabilitado en producción. Usa --force si es imprescindible.');
            return;
        }

        $this->call(MandatoryAllergensSeeder::class);
        $this->call(RestaurantSeeder::class);
        $this->call(AdviceSeeder::class);
    }
}
