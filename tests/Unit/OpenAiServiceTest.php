<?php

namespace Tests\Unit;

use App\Services\OpenAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Start with no keys so tests are isolated.
        config(['services.openai.key' => '']);
    }

    // ── Key detection ─────────────────────────────────────────────────────────

    public function test_not_configured_when_no_key_set(): void
    {
        $svc = new OpenAiService();

        $this->assertFalse($svc->isConfigured());
        $this->assertSame('none', $svc->keySource());
        $this->assertFalse($svc->usesClientKey());
    }

    public function test_configured_as_platform_when_global_key_is_set(): void
    {
        config(['services.openai.key' => 'sk-platform-key-test']);

        $svc = new OpenAiService();

        $this->assertTrue($svc->isConfigured());
        $this->assertSame('platform', $svc->keySource());
        $this->assertFalse($svc->usesClientKey());
    }

    // ── generateProductDescription ────────────────────────────────────────────

    public function test_generate_product_description_returns_trimmed_text(): void
    {
        config(['services.openai.key' => 'sk-test-fake']);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '  Pulpo tierno con pimentón sobre patata.  ']],
                ],
            ], 200),
        ]);

        $result = (new OpenAiService())->generateProductDescription([
            'name'      => 'Pulpo a la gallega',
            'allergens' => [],
        ]);

        $this->assertSame('Pulpo tierno con pimentón sobre patata.', $result);
    }

    public function test_generate_product_description_throws_on_api_client_error(): void
    {
        config(['services.openai.key' => 'sk-test-fake']);

        Http::fake([
            'api.openai.com/*' => Http::response(
                ['error' => ['message' => 'Invalid API key']],
                401
            ),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/OpenAI/');

        (new OpenAiService())->generateProductDescription(['name' => 'Pulpo', 'allergens' => []]);
    }

    public function test_generate_product_description_throws_on_empty_response(): void
    {
        config(['services.openai.key' => 'sk-test-fake']);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '']],
                ],
            ], 200),
        ]);

        $this->expectException(\RuntimeException::class);

        (new OpenAiService())->generateProductDescription(['name' => 'Pulpo', 'allergens' => []]);
    }

    // ── generateProductAllergenText ───────────────────────────────────────────

    public function test_generate_allergen_text_returns_text(): void
    {
        config(['services.openai.key' => 'sk-test-fake']);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Contiene gluten y huevos.']],
                ],
            ], 200),
        ]);

        $result = (new OpenAiService())->generateProductAllergenText([
            'name'        => 'Tortilla',
            'description' => '',
            'allergens'   => ['gluten', 'huevos'],
        ]);

        $this->assertSame('Contiene gluten y huevos.', $result);
    }

    // ── generatePairingDescription ────────────────────────────────────────────

    public function test_generate_pairing_description_returns_text(): void
    {
        config(['services.openai.key' => 'sk-test-fake']);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Albariño fresco y mineral, ideal con mariscos.']],
                ],
            ], 200),
        ]);

        $result = (new OpenAiService())->generatePairingDescription([
            'name' => 'Albariño Martín Codax',
        ]);

        $this->assertSame('Albariño fresco y mineral, ideal con mariscos.', $result);
    }

    // ── extractMenuFromImage ──────────────────────────────────────────────────

    public function test_extract_menu_from_image_parses_categories(): void
    {
        config(['services.openai.key' => 'sk-test-fake']);

        $jsonContent = json_encode([
            'categories' => [
                ['name' => 'Entrantes', 'products' => [
                    ['name' => 'Ensalada', 'description' => '', 'price' => 8.5, 'allergens' => []],
                ]],
            ],
        ]);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => $jsonContent]],
                ],
            ], 200),
        ]);

        $result = (new OpenAiService())->extractMenuFromImage(base64_encode('fake-image-bytes'));

        $this->assertArrayHasKey('categories', $result);
        $this->assertCount(1, $result['categories']);
        $this->assertSame('Entrantes', $result['categories'][0]['name']);
    }

    public function test_extract_menu_from_image_throws_on_invalid_json(): void
    {
        config(['services.openai.key' => 'sk-test-fake']);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'not json at all']],
                ],
            ], 200),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/estructura válida/');

        (new OpenAiService())->extractMenuFromImage(base64_encode('fake'));
    }
}
