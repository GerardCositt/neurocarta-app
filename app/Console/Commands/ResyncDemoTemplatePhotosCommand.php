<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\DemoContent;
use App\Support\DemoProductPhotoResolver;
use Illuminate\Console\Command;

/**
 * Tras importar SQL o rutas rotas, vuelve a enlazar fotos con DemoContent y copia desde public/demo al storage si no hay CDN.
 */
class ResyncDemoTemplatePhotosCommand extends Command
{
    protected $signature = 'demo:resync-template-photos
                            {--restaurant= : Limitar a restaurant_id}
                            {--by-name : Actualizar cualquier producto cuyo nombre coincida con DemoContent (importaciones laxas)}
                            {--dry-run : Solo mostraría cambios}';

    protected $description = 'Reasigna fotos de la plantilla DemoContent (corrige BD/SQL con rutas que no existen en este proyecto).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $byName = (bool) $this->option('by-name');
        $restaurantId = $this->option('restaurant');

        $demoByName = [];
        foreach (DemoContent::products() as $row) {
            $demoByName[trim((string) $row['name'])] = $row;
        }

        $demoBySig = [];
        foreach (DemoContent::products() as $row) {
            $demoBySig[$this->signatureFromDemoRow($row)] = $row;
        }

        $query = Product::query()->withoutGlobalScopes();
        if ($restaurantId !== null && $restaurantId !== '') {
            $query->where('restaurant_id', (int) $restaurantId);
        }

        $consider = 0;
        $updated = 0;

        foreach ($query->cursor() as $product) {
            $demoRow = null;

            if ($byName) {
                $demoRow = $demoByName[trim((string) $product->name)] ?? null;
            } else {
                $demoRow = $demoBySig[$this->signatureFromProduct($product)] ?? null;
                if ($demoRow === null && $product->is_template) {
                    $demoRow = $demoByName[trim((string) $product->name)] ?? null;
                }
            }

            if ($demoRow === null) {
                continue;
            }

            $resolved = DemoProductPhotoResolver::resolveForTemplateProduct($demoRow['photo']);
            if ($resolved === null || $resolved === '') {
                $this->warn("No se pudo resolver foto para «{$product->name}» (¿falta el archivo en public/demo/?).");

                continue;
            }

            ++$consider;

            if ($dry) {
                $this->line("[dry-run] #{$product->id} {$product->name}: «{$product->photo}» → «{$resolved}»");

                continue;
            }

            if ($product->photo === $resolved && $product->is_template) {
                continue;
            }

            $product->forceFill([
                'photo'       => $resolved,
                'is_template' => true,
            ])->saveQuietly();
            ++$updated;
        }

        if ($dry) {
            $this->info("Filas de plantilla detectadas: {$consider}. Usa sin --dry-run para aplicar.");
        } else {
            $this->info("Productos actualizados: {$updated}".($consider ? " (coincidencias evaluadas: {$consider})" : ''));
        }

        if ($consider === 0) {
            $this->comment('Ningún producto coincide con DemoContent. Prueba --by-name o revisa nombres/precios del SQL.');
        }

        return self::SUCCESS;
    }

    private function signatureFromDemoRow(array $product): string
    {
        return implode('|', [
            trim((string) ($product['name'] ?? '')),
            trim((string) ($product['description'] ?? '')),
            trim((string) ($product['price'] ?? '')),
        ]);
    }

    private function signatureFromProduct(Product $p): string
    {
        return implode('|', [
            trim((string) $p->name),
            trim((string) $p->description),
            trim((string) $p->price),
        ]);
    }
}
