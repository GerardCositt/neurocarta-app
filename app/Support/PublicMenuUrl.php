<?php

namespace App\Support;

use Illuminate\Http\Request;

class PublicMenuUrl
{
    /**
     * URL para cambiar idioma en la carta pública sin serializar el modelo Restaurant en la query.
     */
    public static function withLang(Request $request, string $lang): string
    {
        $params = ['lang' => $lang];

        $restaurantId = $request->query('restaurant');
        if ($restaurantId !== null && $restaurantId !== '' && is_numeric($restaurantId)) {
            $params['restaurant'] = (int) $restaurantId;
        }

        $query = http_build_query($params);

        return $request->url().($query !== '' ? '?'.$query : '');
    }
}
