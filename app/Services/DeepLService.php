<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class DeepLService
{
    // Límite mensual (DeepL Free = 500.000 → guardamos margen de 1)
    public const MONTHLY_LIMIT = 499_999;

    private const API_URL = 'https://api-free.deepl.com/v2/translate';
    private const USAGE_URL = 'https://api-free.deepl.com/v2/usage';

    // Idiomas soportados por DeepL (target lang codes)
    public const SUPPORTED_LANGUAGES = [
        'AR' => 'Árabe',
        'BG' => 'Búlgaro',
        'CS' => 'Checo',
        'DA' => 'Danés',
        'DE' => 'Alemán',
        'EL' => 'Griego',
        'EN-GB' => 'Inglés (Reino Unido)',
        'EN-US' => 'Inglés (EE.UU.)',
        'ES' => 'Español',
        'ET' => 'Estonio',
        'FI' => 'Finlandés',
        'FR' => 'Francés',
        'HU' => 'Húngaro',
        'ID' => 'Indonesio',
        'IT' => 'Italiano',
        'JA' => 'Japonés',
        'KO' => 'Coreano',
        'LT' => 'Lituano',
        'LV' => 'Letón',
        'NB' => 'Noruego',
        'NL' => 'Neerlandés',
        'PL' => 'Polaco',
        'PT-BR' => 'Portugués (Brasil)',
        'PT-PT' => 'Portugués (Portugal)',
        'RO' => 'Rumano',
        'RU' => 'Ruso',
        'SK' => 'Eslovaco',
        'SL' => 'Esloveno',
        'SV' => 'Sueco',
        'TR' => 'Turco',
        'UK' => 'Ucraniano',
        'ZH' => 'Chino',
    ];

    // ---------------------------------------------------------------

    public function getApiKey(): ?string
    {
        $restaurantId = session('admin_restaurant_id');
        $restaurantKey = Setting::get('deepl_api_key', null, $restaurantId);
        if (! empty($restaurantKey)) {
            return (string) $restaurantKey;
        }

        $globalKey = Setting::get('deepl_api_key');

        return $globalKey ?: null;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getApiKey());
    }

    // ---------------------------------------------------------------
    // Uso mensual (guardado en settings, clave global sin restaurant_id)
    // ---------------------------------------------------------------

    private function usageKey(): string
    {
        return 'deepl_chars_' . now()->format('Y_m');
    }

    public function getMonthlyUsed(): int
    {
        return (int) Setting::get($this->usageKey(), 0);
    }

    public function getMonthlyRemaining(): int
    {
        return max(0, self::MONTHLY_LIMIT - $this->getMonthlyUsed());
    }

    public function getUsagePercent(): float
    {
        return round(($this->getMonthlyUsed() / self::MONTHLY_LIMIT) * 100, 1);
    }

    private function incrementUsage(int $chars): void
    {
        $key     = $this->usageKey();
        $current = (int) Setting::get($key, 0);
        Setting::put($key, $current + $chars);
    }

    // ---------------------------------------------------------------
    // Traducción
    // ---------------------------------------------------------------

    /**
     * Traduce un array de textos al idioma destino.
     * Devuelve un array con las traducciones en el mismo orden.
     *
     * @throws \RuntimeException si no hay API key, límite superado o error de API.
     */
    public function translate(array $texts, string $targetLang, string $sourceLang = 'ES'): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('No hay API key de DeepL configurada. Ve a Ajustes → Traducciones.');
        }

        // Eliminar textos vacíos pero conservar posición
        $totalChars = array_sum(array_map('mb_strlen', $texts));

        if ($totalChars === 0) {
            return $texts;
        }

        if ($totalChars > $this->getMonthlyRemaining()) {
            throw new \RuntimeException(
                sprintf(
                    'Límite mensual de DeepL casi alcanzado. Restantes: %s caracteres. Necesarios: %s.',
                    number_format($this->getMonthlyRemaining()),
                    number_format($totalChars)
                )
            );
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'DeepL-Auth-Key ' . $this->getApiKey(),
                'Content-Type'  => 'application/json',
            ])
            ->post(self::API_URL, [
                'text'        => array_values($texts),
                'target_lang' => strtoupper($targetLang),
                'source_lang' => strtoupper($sourceLang),
            ]);

        if (!$response->successful()) {
            $code = $response->status();
            $body = $response->body();
            throw new \RuntimeException("Error DeepL [{$code}]: {$body}");
        }

        $this->incrementUsage($totalChars);

        return array_column($response->json('translations'), 'text');
    }

    /**
     * Traduce un único texto. Atajo conveniente.
     */
    public function translateOne(string $text, string $targetLang, string $sourceLang = 'ES'): string
    {
        if (trim($text) === '') return $text;
        $result = $this->translate([$text], $targetLang, $sourceLang);
        return $result[0] ?? $text;
    }

    /**
     * Consulta el uso real en la API de DeepL (no el contador local).
     * Útil para sincronizar si hay desvíos.
     */
    public function fetchApiUsage(): ?array
    {
        if (!$this->isConfigured()) return null;

        $response = Http::timeout(10)
            ->withHeaders(['Authorization' => 'DeepL-Auth-Key ' . $this->getApiKey()])
            ->get(self::USAGE_URL);

        if (!$response->successful()) return null;

        return $response->json();
    }

    /**
     * Convierte el código de locale de la app al formato DeepL.
     * Ej: 'en' → 'EN-US', 'pt' → 'PT-PT', 'zh' → 'ZH'
     */
    public static function localeToDeepL(string $locale): string
    {
        $map = [
            'en' => 'EN-US',
            'pt' => 'PT-PT',
            'pt_BR' => 'PT-BR',
            'pt_PT' => 'PT-PT',
            'zh' => 'ZH',
            'zh_CN' => 'ZH',
            'nb' => 'NB',
        ];

        $lower = strtolower($locale);
        if (isset($map[$lower])) return $map[$lower];

        // Formato xx-XX → XX
        return strtoupper(explode('-', explode('_', $locale)[0])[0]);
    }

    /**
     * Normaliza un código de locale al formato de la app (minúsculas simples).
     * EN-US → en,  PT-BR → pt_BR,  ZH → zh
     */
    public static function deepLToLocale(string $deepLCode): string
    {
        $map = [
            'EN-US' => 'en',
            'EN-GB' => 'en',
            'PT-BR' => 'pt_BR',
            'PT-PT' => 'pt',
            'ZH'    => 'zh',
            'NB'    => 'nb',
        ];
        $upper = strtoupper($deepLCode);
        return $map[$upper] ?? strtolower(explode('-', $deepLCode)[0]);
    }
}
