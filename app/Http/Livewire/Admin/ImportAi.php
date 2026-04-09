<?php

namespace App\Http\Livewire\Admin;

use App\Exceptions\InsufficientAiCreditsException;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Product;
use App\Services\AiCreditService;
use App\Services\OpenAiService;
use App\Services\ProductImageAiService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportAi extends Component
{
    use WithFileUploads;

    /** Lado máximo (px) al enviar a OpenAI; reduce memoria y evita tumbar php artisan serve. */
    private const OPEN_AI_IMAGE_MAX_SIDE = 2048;

    private const OPEN_AI_JPEG_QUALITY = 82;

    // ── Estados: upload | processing | preview | saving | done
    public string  $step          = 'upload';
    public ?string $flashMessage  = null;
    public ?string $flashType     = 'success';

    // ── API Key
    public string $apiKey     = '';
    public bool   $showApiForm = false;

    // ── Subida
    public $file = null;

    // ── Resultado editable
    public array $extracted = [];   // categorías con productos tal como devuelve la IA
    public array $selected  = [];   // [catIndex][prodIndex] => bool (si se va a importar)
    public bool $generateImages = true;

    // ── Resumen tras guardar
    public int $savedProducts   = 0;
    public int $savedCategories = 0;

    private function openAi(): OpenAiService
    {
        return app(OpenAiService::class);
    }

    private function productImageAi(): ProductImageAiService
    {
        return app(ProductImageAiService::class);
    }

    private function aiCredits(): AiCreditService
    {
        return app(AiCreditService::class);
    }

    public function mount(): void
    {
        $restaurantId = session('admin_restaurant_id');
        $this->apiKey = (string) \App\Models\Setting::get('openai_api_key', '', $restaurantId);
    }

    public function saveApiKey(): void
    {
        $this->validate(['apiKey' => 'required|string|min:10'], [
            'apiKey.required' => __('validation.api_key.required'),
            'apiKey.min' => __('validation.api_key.min', ['min' => 10]),
        ]);
        \App\Models\Setting::put('openai_api_key', trim($this->apiKey), session('admin_restaurant_id'));
        // Recarga el servicio con la nueva key
        config(['services.openai.key' => trim($this->apiKey)]);
        $this->showApiForm = false;
        $this->flash(__('admin.import_ai.flash_key_saved'));
    }

    // ──────────────────────────────────────────────────────
    // PASO 1 → PROCESO
    // ──────────────────────────────────────────────────────

    public function process(): void
    {
        // Petición larga + imagen en base64: sin esto el proceso puede agotar memoria y cerrar el servidor local (ERR_CONNECTION_REFUSED).
        @ini_set('memory_limit', '512M');
        @set_time_limit(180);

        $this->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ], [
            'file.required' => __('admin.import_ai.validation_file_required'),
            'file.mimes'    => __('admin.import_ai.validation_file_mimes'),
            'file.max'      => __('admin.import_ai.validation_file_max'),
        ]);

        if (!$this->openAi()->isConfigured()) {
            $this->flash(__('admin.import_ai.flash_configure_key'), 'error');
            return;
        }

        try {
            $path = $this->file->getRealPath();
            $mime = $this->file->getMimeType();

            if ($mime === 'application/pdf') {
                if (! extension_loaded('imagick')) {
                    throw new \RuntimeException(__('admin.import_ai.imagick_required'));
                }
                [$base64, $usedMime] = $this->encodePdfFirstPageForOpenAi($path);
            } else {
                [$base64, $usedMime] = $this->encodeRasterPathForOpenAi($path, $mime);
            }

            $result = $this->openAi()->extractMenuFromImage($base64, $usedMime);
            unset($base64);

            $this->extracted = $this->normalizeImportedCategories($result['categories'] ?? []);
            unset($result);

            // Quitar el archivo temporal del estado Livewire (evita payload gigante, checksum roto o crash al hidratar).
            $this->file = null;

            if ($this->countExtractedProducts() === 0) {
                $this->extracted = [];
                $this->selected = [];
                $this->flash(__('admin.import_ai.flash_no_products'), 'error');
                $this->step = 'upload';

                return;
            }

            // Pre-seleccionar todos
            foreach ($this->extracted as $ci => $cat) {
                foreach ($cat['products'] ?? [] as $pi => $prod) {
                    $this->selected[$ci][$pi] = true;
                }
            }

            $this->step = 'preview';

        } catch (\RuntimeException $e) {
            $this->flash($e->getMessage(), 'error');
            $this->step = 'upload';
        } catch (\Throwable $e) {
            Log::error('ImportAi process failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->flash(__('admin.import_ai.flash_unexpected', ['message' => $e->getMessage()]), 'error');
            $this->step = 'upload';
        }
    }

    // ──────────────────────────────────────────────────────
    // PASO 2 → GUARDAR
    // ──────────────────────────────────────────────────────

    public function save(): void
    {
        @ini_set('memory_limit', '384M');
        @set_time_limit(120);

        try {
            $this->step = 'saving';

            $selectedProductsCount = $this->totalSelected();
            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_IMPORT_MENU);
            if ($this->generateImages && $selectedProductsCount > 0) {
                $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_IMPORT_MENU_PRODUCT_IMAGE, $selectedProductsCount);
            }

            $rid           = session('admin_restaurant_id');
            $allergenMap   = $this->buildAllergenMap();
            $maxCatOrder   = Category::where('restaurant_id', $rid)->max('order') ?? 0;
            $maxProdOrder  = Product::where('restaurant_id', $rid)->max('order') ?? 0;

            $savedCats  = 0;
            $savedProds = 0;
            $generatedImages = 0;

            foreach ($this->extracted as $ci => $catData) {
                $anySelected = collect($this->selected[$ci] ?? [])->contains(true);
                if (!$anySelected) continue;

                $catName = trim($catData['name'] ?? 'Sin categoría');

                $category = Category::firstOrCreate(
                    ['name' => $catName, 'restaurant_id' => $rid],
                    ['active' => false, 'order' => ++$maxCatOrder]
                );
                if ($category->wasRecentlyCreated) $savedCats++;

                foreach ($catData['products'] ?? [] as $pi => $prodData) {
                    if (empty($this->selected[$ci][$pi])) continue;

                    $price = is_numeric($prodData['price'] ?? null)
                        ? round((float) $prodData['price'], 2)
                        : null;

                    $product = Product::create([
                        'restaurant_id' => $rid,
                        'category_id'   => $category->id,
                        'name'          => trim($prodData['name'] ?? 'Producto'),
                        'description'   => trim($prodData['description'] ?? ''),
                        'price'         => $price,
                        'active'        => false,
                        'order'         => ++$maxProdOrder,
                        'featured'      => false,
                        'recommended'   => false,
                    ]);

                    if ($this->generateImages && $this->productImageAi()->isConfigured()) {
                        $generatedPath = $this->productImageAi()->safelyGenerateForProduct($product);
                        if ($generatedPath) {
                            $product->photo = $generatedPath;
                            $product->save();
                            $generatedImages++;
                        }
                    }

                    $allergenIds = [];
                    foreach ($prodData['allergens'] ?? [] as $aName) {
                        $key = mb_strtolower(trim($aName));
                        if (isset($allergenMap[$key])) {
                            $allergenIds[] = $allergenMap[$key];
                        }
                    }
                    if ($allergenIds) {
                        $product->allergens()->sync($allergenIds);
                    }

                    $savedProds++;
                }
            }

            $this->aiCredits()->spend(AiCreditService::ACTION_IMPORT_MENU, 1, [
                'selected_products' => $selectedProductsCount,
            ]);
            if ($generatedImages > 0) {
                $this->aiCredits()->spend(AiCreditService::ACTION_IMPORT_MENU_PRODUCT_IMAGE, $generatedImages, [
                    'source' => 'import_ai',
                ]);
            }

            $this->emit('aiCreditsUpdated');

            $this->savedProducts   = $savedProds;
            $this->savedCategories = $savedCats;
            $this->step            = 'done';
        } catch (InsufficientAiCreditsException $e) {
            $this->flash($e->getMessage(), 'error');
            $this->step = 'preview';
        } catch (\Throwable $e) {
            Log::error('ImportAi save failed', [
                'message' => $e->getMessage(),
            ]);
            $this->flash($e->getMessage(), 'error');
            $this->step = 'preview';
        }
    }

    // ──────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────

    public function restart(): void
    {
        $this->step            = 'upload';
        $this->file            = null;
        $this->extracted       = [];
        $this->selected        = [];
        $this->flashMessage    = null;
        $this->savedProducts   = 0;
        $this->savedCategories = 0;
    }

    public function toggleProduct(int $ci, int $pi): void
    {
        $this->selected[$ci][$pi] = !($this->selected[$ci][$pi] ?? false);
    }

    public function toggleCategory(int $ci): void
    {
        $anyOn = collect($this->selected[$ci] ?? [])->contains(true);
        foreach (array_keys($this->extracted[$ci]['products'] ?? []) as $pi) {
            $this->selected[$ci][$pi] = !$anyOn;
        }
    }

    public function totalSelected(): int
    {
        $count = 0;
        foreach ($this->selected as $cat) {
            foreach ($cat as $v) {
                if ($v) $count++;
            }
        }
        return $count;
    }

    /**
     * Primera página del PDF → JPEG reducido para la API.
     *
     * @return array{0: string, 1: string} [base64, mime]
     */
    private function encodePdfFirstPageForOpenAi(string $path): array
    {
        $imagick = new \Imagick();
        $imagick->setResolution(150, 150);
        $imagick->readImage($path . '[0]');
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(self::OPEN_AI_JPEG_QUALITY);
        $this->downscaleImagick($imagick);
        $blob = $imagick->getImageBlob();
        $imagick->clear();
        $imagick->destroy();

        return [base64_encode($blob), 'image/jpeg'];
    }

    /**
     * JPG/PNG en disco → JPEG reducido (menos RAM que mandar el archivo crudo completo varias veces en memoria).
     *
     * @return array{0: string, 1: string} [base64, mime]
     */
    private function encodeRasterPathForOpenAi(string $path, string $sourceMime): array
    {
        if (extension_loaded('imagick')) {
            $imagick = new \Imagick();
            $imagick->readImage($path);
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(self::OPEN_AI_JPEG_QUALITY);
            $this->downscaleImagick($imagick);
            $blob = $imagick->getImageBlob();
            $imagick->clear();
            $imagick->destroy();

            return [base64_encode((string) $blob), 'image/jpeg'];
        }

        $binary = file_get_contents($path);
        if (! function_exists('imagecreatefromstring')) {
            return [base64_encode($binary), $this->openAiRasterMimeFallback($sourceMime)];
        }

        $im = @imagecreatefromstring($binary);
        if ($im === false) {
            return [base64_encode($binary), $this->openAiRasterMimeFallback($sourceMime)];
        }

        $w = imagesx($im);
        $h = imagesy($im);
        $max = self::OPEN_AI_IMAGE_MAX_SIDE;

        if ($w > $max || $h > $max) {
            $ratio  = min($max / $w, $max / $h);
            $nw     = max(1, (int) round($w * $ratio));
            $nh     = max(1, (int) round($h * $ratio));
            $scaled = imagescale($im, $nw, $nh);
            if ($scaled !== false) {
                imagedestroy($im);
                $im = $scaled;
            }
        }

        ob_start();
        imagejpeg($im, null, self::OPEN_AI_JPEG_QUALITY);
        $jpeg = ob_get_clean();
        imagedestroy($im);

        return [base64_encode((string) $jpeg), 'image/jpeg'];
    }

    private function downscaleImagick(\Imagick $imagick): void
    {
        $w = $imagick->getImageWidth();
        $h = $imagick->getImageHeight();
        $max = self::OPEN_AI_IMAGE_MAX_SIDE;

        if ($w <= $max && $h <= $max) {
            return;
        }

        $imagick->thumbnailImage($max, $max, true);
    }

    private function openAiRasterMimeFallback(string $sourceMime): string
    {
        $m = strtolower(trim($sourceMime));
        if (in_array($m, ['image/png', 'image/x-png'], true)) {
            return 'image/png';
        }
        if (in_array($m, ['image/webp'], true)) {
            return 'image/webp';
        }

        return 'image/jpeg';
    }

    /**
     * Aplana y sanea lo que devuelve la API (tipos inconsistentes rompen la vista o el snapshot de Livewire).
     *
     * @param  array<int, mixed>  $categories
     * @return array<int, array{name: string, products: array<int, array{name: string, description: string, price: float|null, allergens: array<int, string>}>}>
     */
    private function normalizeImportedCategories(array $categories): array
    {
        $out = [];

        foreach ($categories as $cat) {
            if (! is_array($cat)) {
                continue;
            }

            $catName = isset($cat['name']) ? trim((string) $cat['name']) : '';

            $productsRaw = $cat['products'] ?? [];
            if (is_object($productsRaw)) {
                $productsRaw = (array) $productsRaw;
            }
            if (! is_array($productsRaw)) {
                $productsRaw = [];
            }

            $productsList = [];

            foreach ($productsRaw as $prod) {
                if (! is_array($prod)) {
                    continue;
                }

                $allergensRaw = $prod['allergens'] ?? [];
                $allergens    = $this->normalizeAllergenList($allergensRaw);

                $productsList[] = [
                    'name'        => trim((string) ($prod['name'] ?? '')) ?: 'Producto sin nombre',
                    'description' => trim((string) ($prod['description'] ?? '')),
                    'price'       => $this->normalizePrice($prod['price'] ?? null),
                    'allergens'   => $allergens,
                ];
            }

            $out[] = [
                'name'     => $catName !== '' ? $catName : 'Sin categoría',
                'products' => $productsList,
            ];
        }

        return array_values(array_filter($out, fn ($row) => count($row['products']) > 0));
    }

    /**
     * @param  mixed  $raw
     * @return array<int, string>
     */
    private function normalizeAllergenList($raw): array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return [];
        }

        if (is_string($raw)) {
            $parts = preg_split('/[,;|]/', $raw) ?: [];

            return array_values(array_filter(array_map('trim', $parts)));
        }

        if (is_object($raw)) {
            $raw = (array) $raw;
        }

        if (! is_array($raw)) {
            return [];
        }

        $list = [];
        foreach ($raw as $item) {
            if (is_scalar($item) && (string) $item !== '') {
                $list[] = trim((string) $item);
            }
        }

        return $list;
    }

    /**
     * @param  mixed  $price
     */
    private function normalizePrice($price): ?float
    {
        if ($price === null || $price === '') {
            return null;
        }

        if (is_numeric($price)) {
            return round((float) $price, 2);
        }

        if (is_string($price)) {
            $s = preg_replace('/[^\d,.\-]/', '', $price);
            $s = str_replace(',', '.', $s);
            if ($s !== '' && is_numeric($s)) {
                return round((float) $s, 2);
            }
        }

        return null;
    }

    private function countExtractedProducts(): int
    {
        $n = 0;
        foreach ($this->extracted as $cat) {
            $n += count($cat['products'] ?? []);
        }

        return $n;
    }

    private function buildAllergenMap(): array
    {
        // Mapa: "gluten" => id, "lácteos" => id, …
        $map = [];
        foreach (Allergen::all() as $a) {
            $key = mb_strtolower(trim($a->name));
            $map[$key] = $a->id;
            // Alias comunes
            $aliases = [
                'cereales con gluten' => ['gluten', 'cereales'],
                'lácteos'             => ['lacteos', 'leche'],
                'frutos de cáscara'   => ['frutos secos', 'nueces'],
                'sulfitos'            => ['sulfitos', 'dióxido de azufre'],
            ];
            foreach ($aliases as $canonical => $alts) {
                if (str_contains($key, $canonical) || $key === $canonical) {
                    foreach ($alts as $alt) {
                        $map[$alt] = $a->id;
                    }
                }
            }
        }
        return $map;
    }

    private function flash(string $msg, string $type = 'success'): void
    {
        $this->flashMessage = $msg;
        $this->flashType    = $type;
    }

    // ──────────────────────────────────────────────────────
    // RENDER
    // ──────────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.admin.import-ai', [
            'totalSelected' => $this->totalSelected(),
            'configured'    => $this->openAi()->isConfigured(),
            'aiCredits'     => $this->aiCredits()->summary(),
            'importCost'    => $this->aiCredits()->cost(AiCreditService::ACTION_IMPORT_MENU),
            'imageCost'     => $this->aiCredits()->cost(AiCreditService::ACTION_IMPORT_MENU_PRODUCT_IMAGE),
        ]);
    }
}
