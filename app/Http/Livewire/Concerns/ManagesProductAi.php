<?php

namespace App\Http\Livewire\Concerns;

use App\Exceptions\InsufficientAiCreditsException;
use App\Models\Allergen;
use App\Models\Category;
use App\Models\Pairing;
use App\Models\Setting;
use App\Services\AiCreditService;
use App\Services\OpenAiService;
use App\Services\ProductImageAiService;
use App\Support\PlanFeatureGate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Acciones de IA sobre productos: generación de descripciones, texto de alérgenos e imágenes.
 * Requiere: $this->getRestaurantId() y $this->aiCredits() (protected en Products).
 */
trait ManagesProductAi
{
    private function openAi(): OpenAiService
    {
        return app(OpenAiService::class);
    }

    private function productImageAi(): ProductImageAiService
    {
        return app(ProductImageAiService::class);
    }

    private function notifyAiCreditsChanged(): void
    {
        $this->emit('aiCreditsUpdated');
    }

    private function guardAiPlan(): bool
    {
        if (PlanFeatureGate::allows('ai')) {
            return true;
        }

        session()->flash('message', __('admin.plan.feature_not_available'));

        return false;
    }

    private function guardAiExecution(): bool
    {
        if (! $this->guardAiPlan()) {
            return false;
        }

        if (! PlanFeatureGate::attemptAiAction()) {
            session()->flash('message', __('admin.plan.ai_rate_limited'));

            return false;
        }

        return true;
    }

    private function selectedAllergenNames(): array
    {
        $ids = array_values(array_unique(array_map('intval', $this->selectedAllergens ?? [])));
        if ($ids === []) {
            return [];
        }

        return Allergen::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    protected function hasAiWritingGuide(): bool
    {
        $restaurantId = $this->getRestaurantId();
        foreach (['ai_writing_guide', 'openai_writing_guide', 'writing_guide', 'brand_guide', 'style_guide'] as $key) {
            if (trim((string) Setting::get($key, '', $restaurantId)) !== '') {
                return true;
            }

            if (trim((string) Setting::get($key, '', null)) !== '') {
                return true;
            }
        }

        return false;
    }

    public function aiCreditSummary(): array
    {
        return $this->aiCredits()->summary();
    }

    public function aiCost(string $action, int $units = 1): int
    {
        return $this->aiCredits()->cost($action, $units);
    }

    public function confirmGenerateCurrentProductPhotoWithAi(): void
    {
        if (! $this->guardAiPlan()) {
            return;
        }

        $this->pendingAiAction = 'generate_current_product_photo';
        $this->confirmingAiAction = true;
    }

    public function confirmImproveCurrentProductPhotoWithAi(): void
    {
        if (! $this->guardAiPlan()) {
            return;
        }

        $this->pendingAiAction = 'improve_current_product_photo';
        $this->confirmingAiAction = true;
    }

    public function confirmGenerateMissingProductPhotos(): void
    {
        if (! $this->guardAiPlan()) {
            return;
        }

        $this->pendingAiAction = 'generate_missing_product_photos';
        $this->confirmingAiAction = true;
    }

    public function confirmGenerateDescriptionWithAi(): void
    {
        if (! $this->guardAiPlan()) {
            return;
        }

        $this->pendingAiAction = 'generate_description';
        $this->confirmingAiAction = true;
    }

    public function confirmGenerateAllergenTextWithAi(): void
    {
        if (! $this->guardAiPlan()) {
            return;
        }

        $this->pendingAiAction = 'generate_allergen_text';
        $this->confirmingAiAction = true;
    }

    public function cancelAiActionConfirmation(): void
    {
        $this->confirmingAiAction = false;
        $this->pendingAiAction = null;
    }

    public function confirmAiAction(): void
    {
        if (! $this->guardAiExecution()) {
            $this->confirmingAiAction = false;
            $this->pendingAiAction = null;

            return;
        }

        $action = $this->pendingAiAction;
        $this->confirmingAiAction = false;
        $this->pendingAiAction = null;

        if ($action === 'generate_current_product_photo') {
            $this->generateCurrentProductPhotoWithAi();
            return;
        }

        if ($action === 'improve_current_product_photo') {
            $this->improveCurrentProductPhotoWithAi();
            return;
        }

        if ($action === 'generate_missing_product_photos') {
            $this->generateMissingProductPhotos();
            return;
        }

        if ($action === 'generate_description') {
            $this->generateDescriptionWithAi();
            return;
        }

        if ($action === 'generate_allergen_text') {
            $this->generateAllergenTextWithAi();
        }
    }

    public function generateDescriptionWithAi(): void
    {
        if (! $this->guardAiExecution()) {
            return;
        }

        try {
            if (! $this->openAi()->isConfigured()) {
                session()->flash('message', 'Configura la API key de OpenAI para generar descripciones.');
                return;
            }

            if (trim((string) $this->name) === '') {
                session()->flash('message', 'Escribe primero el nombre del plato para generar la descripcion.');
                return;
            }

            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION);

            $allergens = $this->selectedAllergenNames();
            $categoryName = $this->category_id ? (Category::find($this->category_id)?->name ?? '') : '';
            $pairingName = $this->pairing_id ? (Pairing::find($this->pairing_id)?->name ?? '') : '';

            $this->description = $this->openAi()->generateProductDescription([
                'name' => $this->name,
                'category' => $categoryName,
                'pairing' => $pairingName,
                'existing_description' => $this->description,
                'allergens' => $allergens,
            ]);

            $this->aiCredits()->spend(
                AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION,
                1,
                ['product_name' => $this->name],
                $this->product_id ? (int) $this->product_id : null
            );
            $this->notifyAiCreditsChanged();

            session()->flash('message', 'Descripcion generada con IA.');
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('No se pudo generar la descripcion con IA', [
                'product_id' => $this->product_id,
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', 'No se pudo generar la descripcion con IA: ' . $e->getMessage());
        }
    }

    public function generateAllergenTextWithAi(): void
    {
        if (! $this->guardAiExecution()) {
            return;
        }

        try {
            if (! $this->openAi()->isConfigured()) {
                session()->flash('message', 'Configura la API key de OpenAI para generar el texto de alergenos.');
                return;
            }

            if (trim((string) $this->name) === '') {
                session()->flash('message', 'Escribe primero el nombre del plato para generar el texto de alergenos.');
                return;
            }

            $allergens = $this->selectedAllergenNames();
            if ($allergens === []) {
                session()->flash('message', 'Selecciona al menos un alergeno para generar el texto alternativo.');
                return;
            }

            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_GENERATE_PRODUCT_ALLERGEN_TEXT);

            $this->aller = $this->openAi()->generateProductAllergenText([
                'name' => $this->name,
                'description' => $this->description,
                'allergens' => $allergens,
            ]);

            $this->aiCredits()->spend(
                AiCreditService::ACTION_GENERATE_PRODUCT_ALLERGEN_TEXT,
                1,
                ['product_name' => $this->name, 'allergens' => $allergens],
                $this->product_id ? (int) $this->product_id : null
            );
            $this->notifyAiCreditsChanged();

            session()->flash('message', 'Texto de alergenos generado con IA.');
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('No se pudo generar el texto de alergenos con IA', [
                'product_id' => $this->product_id,
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', 'No se pudo generar el texto de alergenos con IA: ' . $e->getMessage());
        }
    }

    public function improveCurrentProductPhotoWithAi(): void
    {
        if (! $this->guardAiExecution()) {
            return;
        }

        try {
            @ini_set('max_execution_time', '180');
            @set_time_limit(180);
            @ini_set('memory_limit', '512M');

            if (! $this->product_id) {
                session()->flash('message', __('admin.products.flash_improve_save_first'));

                return;
            }

            if (! $this->productImageAi()->isConfigured()) {
                session()->flash('message', __('admin.products.flash_improve_no_key'));

                return;
            }

            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_IMPROVE_PRODUCT_IMAGE);

            $product = \App\Models\Product::find($this->product_id);
            if (! $product || ! $product->photo) {
                session()->flash('message', __('admin.products.flash_improve_no_image'));

                return;
            }

            $oldPath = $product->photo;
            $newPath = $this->productImageAi()->improveExistingProductPhoto($product);
            $product->photo = $newPath;
            $product->save();
            $this->photo = $newPath;
            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }

            $this->aiCredits()->spend(
                AiCreditService::ACTION_IMPROVE_PRODUCT_IMAGE,
                1,
                ['product_name' => $product->name],
                (int) $product->id
            );

            session()->flash('message', __('admin.products.flash_improved'));
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('No se pudo mejorar la imagen con IA', [
                'product_id' => $this->product_id,
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', __('admin.products.flash_improve_fail') . ' ' . $e->getMessage());
        }
    }

    public function generateCurrentProductPhotoWithAi(): void
    {
        if (! $this->guardAiExecution()) {
            return;
        }

        try {
            @ini_set('max_execution_time', '180');
            @set_time_limit(180);
            @ini_set('memory_limit', '512M');

            if (! $this->product_id) {
                session()->flash('message', __('admin.products.flash_gen_save_first'));

                return;
            }

            if (! $this->productImageAi()->isConfigured()) {
                session()->flash('message', __('admin.products.flash_gen_no_key'));

                return;
            }

            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE);

            $product = \App\Models\Product::find($this->product_id);
            if (! $product) {
                session()->flash('message', __('admin.products.flash_gen_not_found'));

                return;
            }

            $oldPath = $product->photo;
            $newPath = $this->productImageAi()->generateForProduct($product);
            $product->photo = $newPath;
            $product->save();
            $this->photo = $newPath;

            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }

            $this->aiCredits()->spend(
                AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE,
                1,
                ['product_name' => $product->name],
                (int) $product->id
            );
            $this->notifyAiCreditsChanged();

            session()->flash('message', __('admin.products.flash_generated'));
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('No se pudo generar la imagen con IA', [
                'product_id' => $this->product_id,
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', __('admin.products.flash_gen_fail') . ' ' . $e->getMessage());
        }
    }

    public function generateMissingProductPhotos(): void
    {
        if (! $this->guardAiExecution()) {
            $this->dispatchBrowserEvent('bulk-gen-photos-done');
            return;
        }

        try {
            if (! $this->productImageAi()->isConfigured()) {
                session()->flash('message', __('admin.products.flash_bulk_gen_no_key'));
                $this->dispatchBrowserEvent('bulk-gen-photos-done');
                return;
            }

            @ini_set('max_execution_time', '300');
            @ini_set('memory_limit', '512M');
            @set_time_limit(300);

            $query = \App\Models\Product::query()
                ->where(function ($q) {
                    $q->whereNull('photo')->orWhere('photo', '');
                });

            if ($rid = $this->getRestaurantId()) {
                $query->where('restaurant_id', $rid);
            }

            $batchSize = 10;
            $products = $query->orderBy('id')->limit($batchSize)->get();
            $remaining = $query->count() - $products->count();
            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_BULK_GENERATE_PRODUCT_IMAGES, $products->count());

            $generated = 0;

            foreach ($products as $product) {
                $path = $this->productImageAi()->safelyGenerateForProduct($product);
                if (! $path) {
                    continue;
                }

                $product->photo = $path;
                $product->save();
                $generated++;
            }

            if ($generated > 0) {
                $this->aiCredits()->spend(
                    AiCreditService::ACTION_BULK_GENERATE_PRODUCT_IMAGES,
                    $generated,
                    ['mode' => 'bulk_missing_photos']
                );
                $this->notifyAiCreditsChanged();
            }

            if ($generated > 0 && $remaining > 0) {
                session()->flash('message', __('admin.products.flash_bulk_gen_done', ['count' => $generated]) . ' ' . __('admin.products.flash_bulk_gen_remaining', ['count' => $remaining]));
            } else {
                session()->flash('message', $generated > 0
                    ? __('admin.products.flash_bulk_gen_done', ['count' => $generated])
                    : __('admin.products.flash_bulk_gen_none'));
            }
            $this->dispatchBrowserEvent('bulk-gen-photos-done');
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
            $this->dispatchBrowserEvent('bulk-gen-photos-done');
        } catch (\Throwable $e) {
            Log::error('No se pudo generar imágenes IA en lote', [
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', __('admin.products.flash_bulk_gen_fail'));
            $this->dispatchBrowserEvent('bulk-gen-photos-done');
        }
    }
}
