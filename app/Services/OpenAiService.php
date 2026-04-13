<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    private string $apiKey;
    private string $model = 'gpt-4o-mini';
    private string $imageModel = 'gpt-image-1';
    private string $keySource = 'none';

    public function __construct()
    {
        $restaurantId = session('admin_restaurant_id');
        $restaurantKey = trim((string) Setting::get('openai_api_key', '', $restaurantId));
        $globalKey = trim((string) config('services.openai.key', ''));
        if ($globalKey === '') {
            $globalKey = trim((string) Setting::get('openai_api_key', '', null));
        }

        if ($restaurantKey !== '') {
            $this->apiKey = $restaurantKey;
            $this->keySource = 'client_key';
        } elseif ($globalKey !== '') {
            $this->apiKey = $globalKey;
            $this->keySource = 'platform';
        } else {
            $this->apiKey = '';
            $this->keySource = 'none';
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function keySource(): string
    {
        return $this->keySource;
    }

    public function usesClientKey(): bool
    {
        return $this->keySource === 'client_key';
    }

    /**
     * Extrae la estructura de una carta a partir de una imagen en base64.
     * Devuelve array con 'categories' => [ ['name'=>…, 'products'=>[…]] ]
     */
    public function extractMenuFromImage(string $base64Image, string $mimeType = 'image/jpeg'): array
    {
        $prompt = $this->buildPrompt();

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => $this->model,
                'max_tokens'      => 4096,
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                            [
                                'type'      => 'image_url',
                                'image_url' => [
                                    'url'    => "data:{$mimeType};base64,{$base64Image}",
                                    'detail' => 'high',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        if (!$response->successful()) {
            Log::error('OpenAI error', ['body' => $response->body()]);
            throw new \RuntimeException('Error en la API de OpenAI: ' . $this->httpErrorMessage($response));
        }

        $content = $response->json('choices.0.message.content', '{}');
        $data    = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['categories'])) {
            Log::error('OpenAI invalid JSON', ['content' => $content]);
            throw new \RuntimeException('La IA no devolvió una estructura válida. Inténtalo de nuevo.');
        }

        return $data;
    }

    public function generateImage(string $prompt): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => $this->imageModel,
                'prompt' => $prompt,
                'size' => '1024x1024',
                'quality' => 'medium',
                'output_format' => 'jpeg',
            ]);

        return $this->extractImageBinaryFromResponse($response);
    }

    public function editImage(string $absoluteImagePath, string $prompt): string
    {
        if (! is_file($absoluteImagePath)) {
            throw new \RuntimeException('No se encontró la imagen enviada a OpenAI.');
        }

        $binary = file_get_contents($absoluteImagePath);
        if ($binary === false) {
            throw new \RuntimeException('No se pudo leer la imagen enviada a OpenAI.');
        }

        $mime = mime_content_type($absoluteImagePath) ?: 'image/jpeg';
        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->attach('image', $binary, basename($absoluteImagePath), ['Content-Type' => $mime])
            ->post('https://api.openai.com/v1/images/edits', [
                'model' => $this->imageModel,
                'prompt' => $prompt,
                'size' => '1024x1024',
                'quality' => 'medium',
                'output_format' => 'jpeg',
            ]);

        return $this->extractImageBinaryFromResponse($response);
    }

    public function generateProductDescription(array $context): string
    {
        $name = trim((string) ($context['name'] ?? ''));
        $category = trim((string) ($context['category'] ?? ''));
        $pairing = trim((string) ($context['pairing'] ?? ''));
        $existing = trim((string) ($context['existing_description'] ?? ''));
        $allergens = array_values(array_filter(array_map('trim', $context['allergens'] ?? [])));
        $guide = $this->restaurantWritingGuide();

        $prompt = "Genera una descripcion breve y comercial para un plato de restaurante en espanol.\n"
            . "Debe sonar natural, apetecible y clara, sin exageraciones vacias.\n"
            . "Longitud objetivo: entre 12 y 28 palabras.\n"
            . "No uses emojis, ni comillas, ni listas, ni encabezados.\n"
            . "Devuelve solo el texto final.\n\n"
            . "Nombre del plato: {$name}\n"
            . ($category !== '' ? "Categoria: {$category}\n" : '')
            . ($pairing !== '' ? "Maridaje: {$pairing}\n" : '')
            . ($existing !== '' ? "Descripcion actual: {$existing}\n" : '')
            . ($allergens !== [] ? "Alergenos seleccionados: " . implode(', ', $allergens) . "\n" : '');

        if ($guide !== '') {
            $prompt .= "\nGuia de estilo del restaurante:\n{$guide}\n";
        }

        return $this->completeText($prompt);
    }

    /**
     * Texto de maridaje / bebida (vino, cerveza, etc.) a partir del nombre de la botella o referencia.
     */
    public function generatePairingDescription(array $context): string
    {
        $name = trim((string) ($context['name'] ?? ''));
        $existing = trim((string) ($context['existing_description'] ?? ''));
        $guide = $this->restaurantWritingGuide();

        $prompt = "Genera una descripcion breve y comercial en espanol para un maridaje o bebida en carta de restaurante (vino, cava, cerveza, vermut, etc.).\n"
            . "Describe aroma, cuerpo o por que encaja con pescados y mariscos, sin inventar datos tecnicos (anada, denominacion) que no aparezcan en el nombre.\n"
            . "Longitud objetivo: entre 12 y 32 palabras.\n"
            . "No uses emojis, ni comillas, ni listas, ni encabezados.\n"
            . "Devuelve solo el texto final.\n\n"
            . "Nombre o referencia: {$name}\n"
            . ($existing !== '' ? "Descripcion actual: {$existing}\n" : '');

        if ($guide !== '') {
            $prompt .= "\nGuia de estilo del restaurante:\n{$guide}\n";
        }

        return $this->completeText($prompt);
    }

    public function generateProductAllergenText(array $context): string
    {
        $name = trim((string) ($context['name'] ?? ''));
        $description = trim((string) ($context['description'] ?? ''));
        $allergens = array_values(array_filter(array_map('trim', $context['allergens'] ?? [])));
        $guide = $this->restaurantWritingGuide();

        $prompt = "Redacta un texto alternativo breve para la cartela de alergenos de un plato en espanol.\n"
            . "Debe sonar claro, responsable y natural.\n"
            . "Si hay alergenos seleccionados, menciona solo esos alergenos.\n"
            . "No inventes trazas ni advertencias legales no indicadas.\n"
            . "Longitud objetivo: entre 8 y 24 palabras.\n"
            . "Devuelve solo el texto final.\n\n"
            . "Nombre del plato: {$name}\n"
            . ($description !== '' ? "Descripcion del plato: {$description}\n" : '')
            . "Alergenos seleccionados: " . ($allergens !== [] ? implode(', ', $allergens) : 'ninguno') . "\n";

        if ($guide !== '') {
            $prompt .= "\nGuia de estilo del restaurante:\n{$guide}\n";
        }

        return $this->completeText($prompt);
    }

    private function extractImageBinaryFromResponse($response): string
    {
        if (! $response->successful()) {
            Log::error('OpenAI image error', ['body' => $response->body()]);
            throw new \RuntimeException('Error en la API de OpenAI (imagen): ' . $this->httpErrorMessage($response));
        }

        $base64 = (string) $response->json('data.0.b64_json', '');
        if ($base64 !== '') {
            $binary = base64_decode($base64, true);
            if ($binary !== false && $binary !== '') {
                return $binary;
            }
        }

        // Algunos modelos devuelven solo URL (p. ej. dall-e-3 con response_format=url por defecto).
        $url = (string) $response->json('data.0.url', '');
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $binary = Http::timeout(120)->get($url)->body();
            if ($binary !== '') {
                return $binary;
            }
        }

        Log::error('OpenAI image response invalid', ['body' => $response->body()]);
        throw new \RuntimeException('La IA no devolvió ninguna imagen válida (ni base64 ni URL descargable).');
    }

    private function httpErrorMessage(Response $response): string
    {
        $msg = $response->json('error.message');
        if (is_string($msg) && $msg !== '') {
            return $msg . ' (HTTP ' . $response->status() . ')';
        }

        return 'HTTP ' . $response->status() . ' — ' . substr($response->body(), 0, 500);
    }

    private function completeText(string $prompt): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'max_tokens' => 300,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres un redactor profesional de cartas de restaurante. Responde siempre en espanol y devuelve solo el texto final solicitado.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::error('OpenAI text error', ['body' => $response->body()]);
            throw new \RuntimeException('Error en la API de OpenAI: ' . $this->httpErrorMessage($response));
        }

        $content = trim((string) $response->json('choices.0.message.content', ''));
        if ($content === '') {
            throw new \RuntimeException('La IA no devolvio ningun texto valido.');
        }

        return trim(preg_replace('/\s+/', ' ', $content));
    }

    private function restaurantWritingGuide(): string
    {
        $restaurantId = session('admin_restaurant_id');
        $candidateKeys = [
            'ai_writing_guide',
            'openai_writing_guide',
            'writing_guide',
            'brand_guide',
            'style_guide',
        ];

        foreach ($candidateKeys as $key) {
            $value = trim((string) Setting::get($key, '', $restaurantId));
            if ($value !== '') {
                return $value;
            }

            $globalValue = trim((string) Setting::get($key, '', null));
            if ($globalValue !== '') {
                return $globalValue;
            }
        }

        return '';
    }

    private function buildPrompt(): string
    {
        return <<<PROMPT
Eres un experto en digitalización de cartas de restaurante.
Analiza esta imagen de carta de restaurante y extrae TODA la información que puedas encontrar.

Devuelve ÚNICAMENTE un JSON válido con esta estructura exacta:
{
  "categories": [
    {
      "name": "Nombre de la categoría",
      "products": [
        {
          "name": "Nombre del producto",
          "description": "Descripción si existe, si no cadena vacía",
          "price": 9.50,
          "allergens": ["gluten", "lácteos"]
        }
      ]
    }
  ]
}

Reglas importantes:
- El precio debe ser un número decimal (ej: 9.50), sin símbolo de moneda. Si no hay precio pon null.
- Los alérgenos solo si aparecen explícitamente en la carta. Usa nombres en español: gluten, crustáceos, huevos, pescado, cacahuetes, soja, lácteos, frutos de cáscara, apio, mostaza, sésamo, sulfitos, altramuz, moluscos.
- Si no detectas alérgenos deja el array vacío.
- Mantén los nombres de productos y categorías tal como aparecen en la carta.
- Si hay productos sin categoría clara, agrúpalos en "Sin categoría".
- No inventes información que no aparezca en la imagen.
PROMPT;
    }
}
