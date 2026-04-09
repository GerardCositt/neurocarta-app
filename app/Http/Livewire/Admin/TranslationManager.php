<?php

namespace App\Http\Livewire\Admin;

use App\Models\Advice;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Pairing;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Translation;
use App\Services\DeepLService;
use Livewire\Component;

class TranslationManager extends Component
{
    // ── Estado UI ──────────────────────────────────────────
    public string  $targetLocale  = 'EN-US';
    public string  $apiKey        = '';
    public bool    $showApiForm   = false;
    public ?string $flashMessage  = null;
    public ?string $flashType     = 'success';   // success | error

    // ── Edición inline ────────────────────────────────────
    public ?string $editingModel  = null;
    public ?int    $editingId     = null;
    public array   $editingValues = [];

    // ── Progreso traducción masiva ─────────────────────────
    public bool    $translating   = false;
    public int     $progressTotal = 0;
    public int     $progressDone  = 0;

    private function deepL(): DeepLService
    {
        return app(DeepLService::class);
    }

    private function restaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    public function mount(): void
    {
        $this->apiKey = (string) Setting::get('deepl_api_key', '', $this->restaurantId());
    }

    // ──────────────────────────────────────────────────────
    // API KEY
    // ──────────────────────────────────────────────────────

    public function saveApiKey(): void
    {
        $this->validate(['apiKey' => 'required|string|min:10'], [
            'apiKey.required' => __('validation.api_key.required'),
            'apiKey.min' => __('validation.api_key.min', ['min' => 10]),
        ]);
        Setting::put('deepl_api_key', trim($this->apiKey), $this->restaurantId());
        $this->showApiForm  = false;
        $this->flash(__('admin.translation_ui.flash_api_saved'));
    }

    // ──────────────────────────────────────────────────────
    // TRADUCCIÓN MASIVA
    // ──────────────────────────────────────────────────────

    public function translateAll(): void
    {
        if (!$this->deepL()->isConfigured()) {
            $this->flash(__('admin.translation_ui.flash_deepl_required'), 'error');
            return;
        }

        $locale = DeepLService::deepLToLocale($this->targetLocale);
        $rid    = $this->restaurantId();

        try {
            set_time_limit(120);

            $count = $this->translateBatch([
                Product::with('translations')->where('restaurant_id', $rid)->get(),
                Category::with('translations')->where('restaurant_id', $rid)->get(),
                Pairing::with('translations')->where('restaurant_id', $rid)->get(),
                Allergen::with('translations')->get(),   // global
                Advice::with('translations')->where('restaurant_id', $rid)->get(),
            ], $locale, false);

            $this->flash("Traducción completada. {$count} campos traducidos. Caracteres restantes: " . number_format($this->deepL()->getMonthlyRemaining()));
        } catch (\RuntimeException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    private function translateBatch(array $collections, string $locale, bool $overwrite = false): int
    {
        $pending = [];

        foreach ($collections as $models) {
            foreach ($models as $model) {
                foreach ($model->getTranslatableFields() as $field) {
                    $original = $model->getAttribute($field);
                    if (!$original || trim($original) === '') continue;

                    if (!$overwrite) {
                        $exists = $model->translations
                            ->where('locale', $locale)
                            ->where('key', $field)
                            ->first();
                        if ($exists) continue;
                    }

                    $pending[] = ['model' => $model, 'field' => $field, 'text' => $original];
                }
            }
        }

        if (empty($pending)) return 0;

        $texts      = array_column($pending, 'text');
        $translated = $this->deepL()->translate($texts, $this->targetLocale);

        foreach ($pending as $i => $item) {
            $item['model']->setTranslation($locale, $item['field'], $translated[$i] ?? $item['text']);
        }

        return count($pending);
    }

    public function retranslateAll(): void
    {
        if (!$this->deepL()->isConfigured()) {
            $this->flash(__('admin.translation_ui.flash_deepl_required'), 'error');
            return;
        }

        $locale = DeepLService::deepLToLocale($this->targetLocale);
        $rid    = $this->restaurantId();

        try {
            // Borrar solo traducciones de los modelos de ESTE restaurante
            $this->deleteRestaurantTranslations($locale, $rid);

            $count = $this->translateBatch([
                Product::with('translations')->where('restaurant_id', $rid)->get(),
                Category::with('translations')->where('restaurant_id', $rid)->get(),
                Pairing::with('translations')->where('restaurant_id', $rid)->get(),
                Allergen::with('translations')->get(),
                Advice::with('translations')->where('restaurant_id', $rid)->get(),
            ], $locale, true);

            $this->flash(__('admin.translation_ui.flash_recompleted', [
                'count' => $count,
                'remaining' => number_format($this->deepL()->getMonthlyRemaining()),
            ]));
        } catch (\RuntimeException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    public function clearLocale(): void
    {
        $locale = DeepLService::deepLToLocale($this->targetLocale);
        $rid    = $this->restaurantId();

        $this->deleteRestaurantTranslations($locale, $rid);
        $this->flash(__('admin.translation_ui.flash_locale_cleared', ['locale' => $locale]));
    }

    /**
     * Borra traducciones de locale solo para los modelos del restaurante dado.
     * Allergens y Advice son globales, no se tocan.
     */
    private function deleteRestaurantTranslations(string $locale, ?int $rid): void
    {
        $map = [
            Product::class  => Product::where('restaurant_id', $rid)->pluck('id'),
            Category::class => Category::where('restaurant_id', $rid)->pluck('id'),
            Pairing::class  => Pairing::where('restaurant_id', $rid)->pluck('id'),
            Advice::class   => Advice::where('restaurant_id', $rid)->pluck('id'),
        ];

        foreach ($map as $type => $ids) {
            Translation::where('locale', $locale)
                ->where('translatable_type', $type)
                ->whereIn('translatable_id', $ids)
                ->delete();
        }
    }

    // ──────────────────────────────────────────────────────
    // EDICIÓN INLINE
    // ──────────────────────────────────────────────────────

    public function startEdit(string $modelType, int $id): void
    {
        $model = $this->resolveModel($modelType, $id);
        if (!$model) return;

        $locale = DeepLService::deepLToLocale($this->targetLocale);
        $this->editingModel  = $modelType;
        $this->editingId     = $id;
        $this->editingValues = [];

        foreach ($model->getTranslatableFields() as $field) {
            $this->editingValues[$field] = $model->translate($locale, $field) ?? '';
        }
    }

    public function saveEdit(): void
    {
        if (!$this->editingModel || !$this->editingId) return;

        $model  = $this->resolveModel($this->editingModel, $this->editingId);
        if (! $model) return;

        $locale = DeepLService::deepLToLocale($this->targetLocale);

        foreach ($this->editingValues as $field => $value) {
            $model->setTranslation($locale, $field, $value);
        }

        $this->cancelEdit();
        $this->flash(__('admin.translation_ui.flash_saved'));
    }

    public function cancelEdit(): void
    {
        $this->editingModel  = null;
        $this->editingId     = null;
        $this->editingValues = [];
    }

    public function translateOne(string $modelType, int $id): void
    {
        if (!$this->deepL()->isConfigured()) {
            $this->flash(__('admin.translation_ui.flash_deepl_required'), 'error');
            return;
        }

        $model  = $this->resolveModel($modelType, $id);
        if (! $model) return;

        $locale = DeepLService::deepLToLocale($this->targetLocale);

        try {
            foreach ($model->getTranslatableFields() as $field) {
                $original = $model->getAttribute($field);
                if (!$original) continue;
                $translated = $this->deepL()->translateOne($original, $this->targetLocale);
                $model->setTranslation($locale, $field, $translated);
            }
            $this->flash(__('admin.translation_ui.flash_one_done'));
        } catch (\RuntimeException $e) {
            $this->flash($e->getMessage(), 'error');
        }
    }

    // ──────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────

    private function resolveModel(string $type, int $id): mixed
    {
        $rid = $this->restaurantId();

        return match ($type) {
            'product'  => Product::with('translations')->where('restaurant_id', $rid)->find($id),
            'category' => Category::with('translations')->where('restaurant_id', $rid)->find($id),
            'allergen' => Allergen::with('translations')->find($id),
            'advice'   => Advice::with('translations')->where('restaurant_id', $rid)->find($id),
            'pairing'  => Pairing::with('translations')->where('restaurant_id', $rid)->find($id),
            default    => null,
        };
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType    = $type;
    }

    // ──────────────────────────────────────────────────────
    // RENDER
    // ──────────────────────────────────────────────────────

    public function render()
    {
        $locale = DeepLService::deepLToLocale($this->targetLocale);
        $rid    = $this->restaurantId();

        $products   = Product::with('translations')->where('restaurant_id', $rid)->orderBy('name')->get();
        $categories = Category::with('translations')->where('restaurant_id', $rid)->orderBy('name')->get();
        $allergens  = Allergen::with('translations')->orderBy('name')->get();
        $advices    = Advice::with('translations')->where('restaurant_id', $rid)->orderBy('title')->get();
        $pairings   = Pairing::with('translations')->where('restaurant_id', $rid)->orderBy('name')->get();

        // Resumen de idiomas: solo modelos de ESTE restaurante
        $restaurantModelIds = [
            Product::class  => Product::where('restaurant_id', $rid)->pluck('id'),
            Category::class => Category::where('restaurant_id', $rid)->pluck('id'),
            Pairing::class  => Pairing::where('restaurant_id', $rid)->pluck('id'),
            Advice::class   => Advice::where('restaurant_id', $rid)->pluck('id'),
        ];

        $summaryData = [];
        foreach ($restaurantModelIds as $type => $ids) {
            $rows = Translation::where('translatable_type', $type)
                ->whereIn('translatable_id', $ids)
                ->selectRaw('locale, count(distinct translatable_id) as items, count(*) as fields')
                ->groupBy('locale')
                ->get();

            foreach ($rows as $row) {
                if (!isset($summaryData[$row->locale])) {
                    $summaryData[$row->locale] = ['items' => 0, 'fields' => 0];
                }
                $summaryData[$row->locale]['items']  += $row->items;
                $summaryData[$row->locale]['fields'] += $row->fields;
            }
        }
        ksort($summaryData);
        $translationSummary = collect($summaryData);

        return view('livewire.admin.translation-manager', [
            'locale'             => $locale,
            'languages'          => DeepLService::SUPPORTED_LANGUAGES,
            'used'               => $this->deepL()->getMonthlyUsed(),
            'remaining'          => $this->deepL()->getMonthlyRemaining(),
            'usagePercent'       => $this->deepL()->getUsagePercent(),
            'products'           => $products,
            'categories'         => $categories,
            'allergens'          => $allergens,
            'advices'            => $advices,
            'pairings'           => $pairings,
            'translationSummary' => $translationSummary,
        ]);
    }
}
