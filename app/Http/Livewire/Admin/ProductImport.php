<?php

namespace App\Http\Livewire\Admin;

use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductImport extends Component
{
    use WithFileUploads;

    /** @var \Livewire\TemporaryUploadedFile|null */
    public $file;

    /** @var array<int, array<string, mixed>> */
    public $previewRows = [];

    /** @var array<int, string> */
    public $previewWarnings = [];

    /** @var array<int, string> */
    public $previewErrors = [];

    public $hasPreview = false;

    private const MAX_PREVIEW_ROWS = 25;
    private const MAX_IMPORT_ROWS = 1000;

    public function updatedFile(): void
    {
        $this->resetPreview();
        $this->validate([
            'file' => 'required|file|max:5120|mimes:csv,txt',
        ]);
        $this->buildPreview();
    }

    public function downloadTemplate()
    {
        $headers = [
            'id',
            'name',
            'category',
            'description',
            'price',
            'offer',
            'offer_price',
            'offer_badge',
            'offer_start',
            'offer_end',
            'active',
            'featured',
            'recommended',
            'order',
            'allergens',
        ];

        $example = [
            '',
            'Ensalada Mixta',
            'Entrantes',
            'Lechuga, tomate, atún…',
            '9,50€',
            '0',
            '',
            'Oferta',
            '',
            '',
            '0',
            '0',
            '0',
            '',
            'Gluten|Pescado',
        ];

        $out = fopen('php://temp', 'r+');
        fputcsv($out, $headers, ';');
        fputcsv($out, $example, ';');
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-productos.csv"',
        ]);
    }

    public function import(): void
    {
        $this->validate([
            'file' => 'required|file|max:5120|mimes:csv,txt',
        ]);

        [$rows, $errors, $warnings] = $this->parseCsvFile(self::MAX_IMPORT_ROWS);
        $this->previewErrors = $errors;
        $this->previewWarnings = $warnings;

        if (!empty($errors)) {
            $this->hasPreview = true;
            return;
        }

        $restaurant = app()->bound('restaurant') ? app('restaurant') : Restaurant::first();
        if (! $restaurant instanceof Restaurant) {
            $this->previewErrors = ['No hay restaurante activo en el panel. Crea uno o recarga sesión.'];
            $this->hasPreview = true;

            return;
        }

        $restaurantId = (int) $restaurant->id;

        $created = 0;
        $updated = 0;
        $linkedAllergens = 0;
        $unknownAllergens = 0;

        DB::transaction(function () use ($rows, $restaurantId, &$created, &$updated, &$linkedAllergens, &$unknownAllergens) {
            $maxCategoryOrder = (int) (Category::where('restaurant_id', $restaurantId)->max('order') ?? 0);
            $maxProductOrder = (int) (Product::where('restaurant_id', $restaurantId)->max('order') ?? 0);

            foreach ($rows as $row) {
                $catName = trim((string) ($row['category'] ?? ''));
                $category = null;
                if ($catName !== '') {
                    $category = Category::firstOrCreate(
                        ['name' => $catName, 'restaurant_id' => $restaurantId],
                        ['active' => false, 'order' => ++$maxCategoryOrder, 'restaurant_id' => $restaurantId]
                    );
                }

                $payload = [
                    'name'           => trim((string) ($row['name'] ?? '')),
                    'description'    => ($row['description'] ?? null) !== '' ? (string) $row['description'] : null,
                    'price'          => ($row['price'] ?? null) !== '' ? (string) $row['price'] : null,
                    'offer'          => $this->toBool($row['offer'] ?? false),
                    'offer_price'    => ($row['offer_price'] ?? null) !== '' ? (string) $row['offer_price'] : null,
                    'offer_badge'    => ($row['offer_badge'] ?? null) !== '' ? (string) $row['offer_badge'] : null,
                    'offer_start'    => ($row['offer_start'] ?? null) !== '' ? (string) $row['offer_start'] : null,
                    'offer_end'      => ($row['offer_end'] ?? null) !== '' ? (string) $row['offer_end'] : null,
                    'active'         => $this->toBool($row['active'] ?? false),
                    'featured'       => $this->toBool($row['featured'] ?? false),
                    'recommended'    => $this->toBool($row['recommended'] ?? false),
                    'category_id'    => $category ? $category->id : null,
                    'restaurant_id'  => $restaurantId,
                ];

                // Order
                $ord = trim((string) ($row['order'] ?? ''));
                if ($ord !== '' && ctype_digit($ord)) {
                    $payload['order'] = (int) $ord;
                } else {
                    $payload['order'] = ++$maxProductOrder;
                }

                $id = trim((string) ($row['id'] ?? ''));
                $product = null;
                if ($id !== '' && ctype_digit($id)) {
                    $candidate = Product::find((int) $id);
                    if ($candidate && ($candidate->restaurant_id === null || (int) $candidate->restaurant_id === $restaurantId)) {
                        $product = $candidate;
                    }
                }

                if ($product) {
                    $product->update(Arr::except($payload, ['category_id']) + ['category_id' => $payload['category_id']]);
                    $updated++;
                } else {
                    $product = Product::create($payload);
                    $created++;
                }

                // Allergens sync
                $all = trim((string) ($row['allergens'] ?? ''));
                if ($all !== '') {
                    $names = array_values(array_filter(array_map('trim', explode('|', $all))));
                    $ids = [];
                    foreach ($names as $name) {
                        $a = Allergen::query()->where('name', $name)->first();
                        if ($a) {
                            $ids[] = $a->id;
                        } else {
                            $unknownAllergens++;
                        }
                    }
                    $ids = array_values(array_unique($ids));
                    $product->allergens()->sync($ids);
                    $linkedAllergens += count($ids);
                }
            }
        });

        session()->flash('message', __('admin.product_import.flash_done', [
            'created' => $created,
            'updated' => $updated,
            'linked' => $linkedAllergens,
            'unknown' => $unknownAllergens,
        ]));
        $this->reset(['file']);
        $this->resetPreview();
    }

    private function buildPreview(): void
    {
        [$rows, $errors, $warnings] = $this->parseCsvFile(self::MAX_PREVIEW_ROWS);
        $this->previewRows = $rows;
        $this->previewErrors = $errors;
        $this->previewWarnings = $warnings;
        $this->hasPreview = true;
    }

    private function resetPreview(): void
    {
        $this->previewRows = [];
        $this->previewWarnings = [];
        $this->previewErrors = [];
        $this->hasPreview = false;
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>, 2: array<int, string>}
     */
    private function parseCsvFile(int $maxRows): array
    {
        $errors = [];
        $warnings = [];
        $rows = [];

        if (!$this->file) {
            return [$rows, $errors, $warnings];
        }

        $path = $this->file->getRealPath();
        if (!$path) {
            return [$rows, ['No se pudo leer el archivo.'], $warnings];
        }

        $fh = fopen($path, 'r');
        if (!$fh) {
            return [$rows, ['No se pudo abrir el archivo.'], $warnings];
        }

        // Detect delimiter (prefer ;)
        $firstLine = fgets($fh);
        if ($firstLine === false) {
            fclose($fh);
            return [$rows, ['El archivo está vacío.'], $warnings];
        }
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
        rewind($fh);

        $header = fgetcsv($fh, 0, $delimiter);
        if (!$header || count($header) < 2) {
            fclose($fh);
            return [$rows, ['No se pudo leer la cabecera del CSV.'], $warnings];
        }

        $header = array_map(function ($h) { return trim((string) $h); }, $header);

        // Permite cabeceras en castellano o inglés
        $map = [
            'id'             => 'id',
            'nombre'         => 'name',
            'name'           => 'name',
            'categoria'      => 'category',
            'category'       => 'category',
            'descripcion'    => 'description',
            'description'    => 'description',
            'precio'         => 'price',
            'price'          => 'price',
            'oferta'         => 'offer',
            'offer'          => 'offer',
            'precio_oferta'  => 'offer_price',
            'offer_price'    => 'offer_price',
            'etiqueta_oferta'=> 'offer_badge',
            'offer_badge'    => 'offer_badge',
            'inicio_oferta'  => 'offer_start',
            'offer_start'    => 'offer_start',
            'fin_oferta'     => 'offer_end',
            'offer_end'      => 'offer_end',
            'oculto'         => 'active',
            'active'         => 'active',
            'destacado'      => 'featured',
            'featured'       => 'featured',
            'recomendado'    => 'recommended',
            'recommended'    => 'recommended',
            'orden'          => 'order',
            'order'          => 'order',
            'alergenos'      => 'allergens',
            'allergens'      => 'allergens',
        ];

        $normalizedHeader = [];
        foreach ($header as $col) {
            $key = strtolower($col);
            $normalizedHeader[] = $map[$key] ?? $key;
        }

        $required = ['name', 'category', 'price'];
        foreach ($required as $col) {
            if (!in_array($col, $normalizedHeader, true)) {
                $errors[] = "Falta la columna obligatoria: {$col}";
            }
        }
        if (!empty($errors)) {
            fclose($fh);
            return [$rows, $errors, $warnings];
        }

        $rowNum = 1;
        while (($data = fgetcsv($fh, 0, $delimiter)) !== false) {
            $rowNum++;
            if ($data === [null] || count(array_filter($data, fn($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }
            $assoc = [];
            foreach ($normalizedHeader as $i => $key) {
                $assoc[$key] = $data[$i] ?? '';
            }
            $name = trim((string) ($assoc['name'] ?? ''));
            if ($name === '') {
                $errors[] = "Fila {$rowNum}: name vacío.";
            }
            $cat = trim((string) ($assoc['category'] ?? ''));
            if ($cat === '') {
                $errors[] = "Fila {$rowNum}: category vacío.";
            }
            $price = trim((string) ($assoc['price'] ?? ''));
            if ($price === '') {
                $errors[] = "Fila {$rowNum}: price vacío.";
            }

            $all = trim((string) ($assoc['allergens'] ?? ''));
            if ($all !== '') {
                $names = array_values(array_filter(array_map('trim', explode('|', $all))));
                foreach ($names as $n) {
                    if (!Allergen::query()->where('name', $n)->exists()) {
                        $warnings[] = "Fila {$rowNum}: alérgeno no encontrado: {$n}";
                    }
                }
            }

            $rows[] = $assoc;
            if (count($rows) >= $maxRows) {
                break;
            }
        }
        fclose($fh);

        return [$rows, $errors, array_values(array_unique($warnings))];
    }

    private function toBool($v): bool
    {
        if (is_bool($v)) return $v;
        $s = strtolower(trim((string) $v));
        return in_array($s, ['1', 'true', 'si', 'sí', 'yes', 'on'], true);
    }

    public function render()
    {
        return view('livewire.admin.product-import');
    }
}
