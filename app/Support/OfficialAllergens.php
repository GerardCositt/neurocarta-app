<?php

namespace App\Support;

class OfficialAllergens
{
    /** Los 14 alérgenos obligatorios UE (Reglamento 1169/2011). */
    public static function list(): array
    {
        return [
            ['slug' => 'cereales-gluten', 'name' => 'Cereales con gluten', 'file' => 'cereales_gluten.png', 'sort' => 1],
            ['slug' => 'crustaceos',      'name' => 'Crustáceos',          'file' => 'crustaceos.png',      'sort' => 2],
            ['slug' => 'huevos',          'name' => 'Huevos',              'file' => 'huevos.png',          'sort' => 3],
            ['slug' => 'pescado',         'name' => 'Pescado',             'file' => 'pescado.png',         'sort' => 4],
            ['slug' => 'cacahuetes',      'name' => 'Cacahuetes',          'file' => 'cacahuetes.png',      'sort' => 5],
            ['slug' => 'soja',            'name' => 'Soja',                'file' => 'soja.png',            'sort' => 6],
            ['slug' => 'lacteos',         'name' => 'Lácteos',             'file' => 'lacteos.png',         'sort' => 7],
            ['slug' => 'frutos-cascara',  'name' => 'Frutos de cáscara',   'file' => 'frutos_cascara.png',  'sort' => 8],
            ['slug' => 'apio',            'name' => 'Apio',                'file' => 'apio.png',            'sort' => 9],
            ['slug' => 'mostaza',         'name' => 'Mostaza',             'file' => 'mostaza.png',         'sort' => 10],
            ['slug' => 'sesamo',          'name' => 'Sésamo',              'file' => 'sesamo.png',          'sort' => 11],
            ['slug' => 'sulfitos',        'name' => 'Sulfitos',            'file' => 'sulfitos.png',        'sort' => 12],
            ['slug' => 'altramuz',        'name' => 'Altramuz',            'file' => 'altramuz.png',        'sort' => 13],
            ['slug' => 'moluscos',        'name' => 'Moluscos',            'file' => 'moluscos.png',        'sort' => 14],
        ];
    }

    public static function slugs(): array
    {
        return array_column(self::list(), 'slug');
    }
}
