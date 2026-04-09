<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Allergen;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportRemoteMenu extends Command
{
    protected $signature = 'demo:import-remote-menu
                            {--source=https://elpuerto.marisqueriabarjaen.com : URL base del restaurante}
                            {--subdomain=elpuerto : Subdominio del restaurante en la BD local}
                            {--with-images : Descarga y asigna las imágenes de los productos}
                            {--map-allergens : Mapea el texto de alérgenos a los alérgenos oficiales (relación) cuando sea posible}
                            {--dry-run : No escribe en la base de datos}';

    protected $description = 'Importa categorías y productos desde una carta remota (HTML) a un restaurante local.';

    public function handle(): int
    {
        $source = rtrim((string) $this->option('source'), '/');
        $subdomain = (string) $this->option('subdomain');
        $withImages = (bool) $this->option('with-images');
        $mapAllergens = (bool) $this->option('map-allergens');
        $dryRun = (bool) $this->option('dry-run');

        $restaurant = Restaurant::where('subdomain', $subdomain)->first();
        if (! $restaurant) {
            $this->error("No existe el restaurante con subdomain='{$subdomain}' en la BD local.");
            return self::FAILURE;
        }

        $this->info("Origen: {$source}");
        $this->info("Destino: {$restaurant->name} ({$restaurant->subdomain}) [id={$restaurant->id}]");
        if ($dryRun) {
            $this->warn('DRY RUN: no se guardará nada.');
        }
        if ($withImages) {
            $this->info('Imágenes: se descargarán y asignarán.');
        }
        if ($mapAllergens) {
            $this->info('Alérgenos: se intentará mapear a alérgenos oficiales.');
        }

        $homeHtml = $this->fetchHtml($source . '/');
        if ($homeHtml === null) {
            $this->error('No pude descargar la página principal.');
            return self::FAILURE;
        }

        $categoryLinks = $this->extractCategoryLinks($source, $homeHtml);
        if (! count($categoryLinks)) {
            $this->error('No he encontrado categorías (links /list/{id}) en la página principal.');
            return self::FAILURE;
        }

        $this->line('Categorías detectadas:');
        foreach ($categoryLinks as $c) {
            $this->line("- {$c['name']} ({$c['url']})");
        }

        $createdCats = 0;
        $createdProds = 0;
        $updatedProds = 0;
        $downloadedImages = 0;
        $assignedImages = 0;
        $allergenLinks = 0;

        $allergenIndex = $mapAllergens ? $this->buildAllergenIndex() : [];

        foreach ($categoryLinks as $idx => $cat) {
            $categoryName = $cat['name'];
            $categoryUrl = $cat['url'];

            $categoryModel = Category::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('name', $categoryName)
                ->first();

            if (! $categoryModel) {
                $categoryModel = new Category();
                $categoryModel->name = $categoryName;
                $categoryModel->restaurant_id = $restaurant->id;
                $categoryModel->order = ($idx + 1) * 10;

                if (! $dryRun) {
                    $categoryModel->save();
                }
                $createdCats++;
            }

            $pageHtml = $this->fetchHtml($categoryUrl);
            if ($pageHtml === null) {
                $this->warn("No pude descargar {$categoryUrl}; salto esta categoría.");
                continue;
            }

            $items = $this->parseProductsFromListHtml($source, $pageHtml);
            $this->line("Importando {$categoryName}: " . count($items) . ' productos');

            foreach ($items as $i => $item) {
                if (! $item['name'] || ! $item['price']) {
                    continue;
                }

                $allerFreeText = $item['allergens_free'] ?: null;
                $payload = [
                    'restaurant_id' => $restaurant->id,
                    'category_id' => $categoryModel->id,
                    'name' => $item['name'],
                    'description' => $item['description'] ?: $item['name'],
                    'price' => $item['price'],
                    'aller' => $allerFreeText,
                    'offer' => false,
                    'active' => 0,
                    'order' => ($i + 1) * 10,
                ];

                $existing = Product::query()
                    ->where('restaurant_id', $restaurant->id)
                    ->where('category_id', $categoryModel->id)
                    ->where('name', $item['name'])
                    ->first();

                if (! $existing) {
                    if (! $dryRun) {
                        $existing = Product::create($payload);
                    }
                    $createdProds++;
                } else {
                    $changed = false;
                    foreach (['description', 'price', 'aller'] as $k) {
                        $newVal = $payload[$k];
                        $oldVal = $existing->$k;
                        if ((string) ($oldVal ?? '') !== (string) ($newVal ?? '')) {
                            $existing->$k = $newVal;
                            $changed = true;
                        }
                    }
                    if ($changed) {
                        if (! $dryRun) {
                            $existing->save();
                        }
                        $updatedProds++;
                    }
                }

                if ($withImages && $existing && ! $dryRun) {
                    $photoUrl = $item['photo_url'] ?? '';
                    if ($photoUrl) {
                        $storedPath = $this->downloadProductImage($photoUrl, $restaurant->subdomain);
                        if ($storedPath) {
                            $downloadedImages++;
                            if ((string) ($existing->photo ?? '') !== $storedPath) {
                                $existing->photo = $storedPath;
                                $existing->save();
                                $assignedImages++;
                            }
                        }
                    }
                }

                if ($mapAllergens && $existing && ! $dryRun) {
                    $linked = $this->syncOfficialAllergensFromText($existing, $item['allergens_raw'] ?? '', $allergenIndex);
                    $allergenLinks += $linked;
                }
            }
        }

        $this->newLine();
        $this->info('Resumen:');
        $this->line("- Categorías creadas: {$createdCats}");
        $this->line("- Productos creados: {$createdProds}");
        $this->line("- Productos actualizados: {$updatedProds}");
        if ($withImages) {
            $this->line("- Imágenes descargadas: {$downloadedImages}");
            $this->line("- Imágenes asignadas: {$assignedImages}");
        }
        if ($mapAllergens) {
            $this->line("- Enlaces alérgenos (relación): {$allergenLinks}");
        }

        return self::SUCCESS;
    }

    private function fetchHtml(string $url): ?string
    {
        try {
            $res = Http::timeout(20)->retry(2, 250)->get($url);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $res->successful()) {
            return null;
        }

        return (string) $res->body();
    }

    /**
     * @return array<int, array{name:string,url:string}>
     */
    private function extractCategoryLinks(string $base, string $homeHtml): array
    {
        $links = [];
        if (preg_match_all('~<a[^>]+href=["\']([^"\']*/list/\d+)["\'][^>]*>(.*?)</a>~is', $homeHtml, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $href = $row[1];
                $name = trim(strip_tags($row[2]));
                if ($name === '') continue;
                if (! Str::startsWith($href, 'http')) {
                    $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                }
                $key = strtolower($name) . '|' . $href;
                $links[$key] = ['name' => $name, 'url' => $href];
            }
        }

        return array_values($links);
    }

    /**
     * @return array<int, array{name:string,description:string,price:string,allergens_raw:string,allergens_free:string,photo_url:string}>
     */
    private function parseProductsFromListHtml(string $base, string $html): array
    {
        $out = [];

        if (preg_match_all('~<div class="max-w-md[^"]*".*?>\s*<div class="md:flex">.*?<img[^>]+src="([^"]+)"[^>]*>.*?<a[^>]*class="block[^"]*">(.*?)</a>.*?<p class="mt-2 text-gray-500">(.*?)</p>.*?<p[^>]*>\s*Precio:\s*(.*?)</p>~is', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                $src = trim($row[1]);
                $name = trim(strip_tags($row[2]));
                $descHtml = $row[3];
                $priceRaw = trim(strip_tags($row[4]));

                $descText = html_entity_decode(strip_tags(str_replace(['<br/>', '<br>', '<br />'], "\n", $descHtml)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $descText = preg_replace("/\r\n|\r/", "\n", $descText) ?? $descText;
                $descText = preg_replace("/[ \t]+/", ' ', $descText) ?? $descText;
                $descText = preg_replace("/\n{2,}/", "\n", $descText) ?? $descText;
                $descText = trim($descText);

                $allergens = '';
                if (preg_match('/\bal[ée]rgenos\s*:/iu', $descText)) {
                    [$descPart, $allPart] = preg_split('/\bal[ée]rgenos\s*:/iu', $descText, 2);
                    $descText = trim($descPart);
                    $allergens = trim($allPart);
                }

                $photoUrl = $src;
                if ($photoUrl && ! Str::startsWith($photoUrl, 'http')) {
                    $photoUrl = rtrim($base, '/') . '/' . ltrim($photoUrl, '/');
                }

                [$allRaw, $allFree] = $this->splitAllergenText($allergens);

                // Blindaje: nunca tratamos como “producto” una línea de alérgenos.
                if (preg_match('/^\s*al[ée]rgenos\s*:/iu', $name)) {
                    continue;
                }

                $out[] = [
                    'name' => $name,
                    'description' => $descText,
                    'price' => $this->normalizePrice($priceRaw),
                    'allergens_raw' => $allRaw,
                    'allergens_free' => $allFree,
                    'photo_url' => $photoUrl,
                ];
            }
        }

        return $out;
    }

    private function normalizePrice(string $raw): string
    {
        $s = trim($raw);
        // Mantener formato original (con €) si lo trae
        // Unificar espacios raros
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        return $s;
    }

    private function downloadProductImage(string $url, string $restaurantSubdomain): ?string
    {
        $pathPart = parse_url($url, PHP_URL_PATH);
        $basename = $pathPart ? basename((string) $pathPart) : null;
        if (! $basename) {
            $basename = Str::random(20) . '.jpg';
        }

        $destDir = 'img/remote/' . Str::slug($restaurantSubdomain);
        $destPath = $destDir . '/' . $basename;

        if (Storage::disk('public')->exists($destPath)) {
            return $destPath;
        }

        try {
            $res = Http::timeout(30)->retry(2, 250)->get($url);
            if (! $res->successful()) {
                return null;
            }
            $bytes = $res->body();
        } catch (\Throwable $e) {
            return null;
        }

        Storage::disk('public')->put($destPath, $bytes);

        return $destPath;
    }

    /**
     * Devuelve [raw, free] donde raw es el texto completo y free contiene solo avisos no “oficiales”
     * (p. ej. contaminación cruzada / trazas), para guardarlo en `products.aller`.
     *
     * @return array{0:string,1:string}
     */
    private function splitAllergenText(string $raw): array
    {
        $s = trim($raw);
        if ($s === '') return ['', ''];

        $s = preg_replace('/\s+/', ' ', $s) ?? $s;

        // Mantener textos “libres” típicos como aviso
        $freeParts = [];
        $lower = mb_strtolower($s);

        foreach (['trazas', 'contaminación', 'contaminacion', 'posible'] as $kw) {
            if (Str::contains($lower, $kw)) {
                $freeParts[] = $s;
                break;
            }
        }

        return [$s, implode(' ', array_unique($freeParts))];
    }

    /**
     * @return array<string,int> mapa normalizado -> allergen_id
     */
    private function buildAllergenIndex(): array
    {
        $all = Allergen::query()->get(['id', 'name']);
        $idx = [];
        foreach ($all as $a) {
            $idx[$this->normAllergenName($a->name)] = (int) $a->id;
        }

        // Sinónimos comunes
        $syn = [
            'lacteos' => 'lácteos',
            'lacteos.' => 'lácteos',
            'lacteos,' => 'lácteos',
            'marisco' => 'marisco',
            'gambon' => 'crustáceos',
            'gambón' => 'crustáceos',
            'frutos secos' => 'frutos de cáscara',
            'frutos de cascara' => 'frutos de cáscara',
            'sulfitos' => 'dióxido de azufre y sulfitos',
            'sulfito' => 'dióxido de azufre y sulfitos',
            'molusco' => 'moluscos',
            'crustaceo' => 'crustáceos',
            'crustaceos' => 'crustáceos',
            'sesamo' => 'granos de sésamo',
            'sesamo.' => 'granos de sésamo',
        ];

        foreach ($syn as $from => $to) {
            $fromN = $this->normAllergenName($from);
            $toN = $this->normAllergenName($to);
            if (isset($idx[$toN])) {
                $idx[$fromN] = $idx[$toN];
            }
        }

        return $idx;
    }

    private function normAllergenName(string $name): string
    {
        $s = mb_strtolower(trim($name));
        $s = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $s);
        $s = preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        return trim($s);
    }

    /**
     * Intenta mapear texto a alérgenos oficiales y sincroniza la relación.
     * Devuelve cuántos enlaces se han añadido.
     */
    private function syncOfficialAllergensFromText(Product $product, string $raw, array $index): int
    {
        $raw = trim($raw);
        if ($raw === '' || ! count($index)) return 0;

        // Trocear por separadores típicos
        $parts = preg_split('/[,\.;·\n]+/u', $raw) ?: [];
        $ids = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') continue;
            // Quitar coletillas tipo "posible..." para el mapeo
            $pLower = mb_strtolower($p);
            if (Str::contains($pLower, 'posible') || Str::contains($pLower, 'traza') || Str::contains($pLower, 'contamin')) {
                continue;
            }
            $norm = $this->normAllergenName($p);
            if (isset($index[$norm])) {
                $ids[] = $index[$norm];
            }
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (! count($ids)) return 0;

        $before = $product->allergens()->count();
        $product->allergens()->syncWithoutDetaching($ids);
        $after = $product->allergens()->count();

        return max(0, $after - $before);
    }
}

