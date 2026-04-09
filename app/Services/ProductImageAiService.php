<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ProductImageAiService
{
    public function __construct(
        private OpenAiService $openAiService,
        private ImageAssetService $imageAssetService,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->openAiService->isConfigured();
    }

    public function generateForProduct(Product $product): string
    {
        $binary = $this->openAiService->generateImage(
            $this->buildGenerationPrompt($product)
        );

        return $this->imageAssetService->storeBinaryImage(
            $binary,
            'img',
            1600,
            $product->name
        );
    }

    public function improveExistingProductPhoto(Product $product): string
    {
        if (! $product->photo) {
            throw new RuntimeException('El producto no tiene una foto que mejorar.');
        }

        $absolutePath = storage_path('app/public/' . ltrim($product->photo, '/'));
        if (! is_file($absolutePath)) {
            throw new RuntimeException('No se encontró la imagen actual del producto.');
        }

        $binary = $this->openAiService->editImage(
            $absolutePath,
            $this->buildEditPrompt($product)
        );

        return $this->imageAssetService->storeBinaryImage(
            $binary,
            'img',
            1600,
            $product->name . '-mejorada'
        );
    }

    public function safelyGenerateForProduct(Product $product): ?string
    {
        try {
            return $this->generateForProduct($product);
        } catch (\Throwable $e) {
            Log::warning('No se pudo generar imagen IA para producto', [
                'product_id' => $product->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildGenerationPrompt(Product $product): string
    {
        $name = trim((string) $product->name);
        $description = trim((string) ($product->description ?? ''));

        return "Crea una foto gastronómica realista y apetecible de un plato de restaurante.\n"
            . "Plato: {$name}.\n"
            . ($description !== '' ? "Descripción: {$description}.\n" : '')
            . "Requisitos:\n"
            . "- Debe parecer una foto profesional de carta, no una ilustración.\n"
            . "- Mantén una presentación coherente con el nombre del plato.\n"
            . "- Fondo limpio y discreto, sin texto, sin logotipos, sin marcas de agua.\n"
            . "- Sin personas, sin manos, sin utensilios dominando la escena.\n"
            . "- Encuadre centrado, iluminación cuidada y aspecto natural.\n";
    }

    private function buildEditPrompt(Product $product): string
    {
        $name = trim((string) $product->name);
        $description = trim((string) ($product->description ?? ''));

        return "Mejora esta foto real de un plato para usarla en una carta digital.\n"
            . "Plato: {$name}.\n"
            . ($description !== '' ? "Descripción: {$description}.\n" : '')
            . "Requisitos:\n"
            . "- Conserva el mismo plato, ingredientes y emplatado.\n"
            . "- Corrige luz, color, enfoque y encuadre.\n"
            . "- Limpia pequeñas distracciones del fondo si las hubiera.\n"
            . "- No añadas texto, logos, marcas de agua, manos ni elementos que no estaban.\n"
            . "- El resultado debe seguir pareciendo una foto realista del mismo plato.\n";
    }
}
