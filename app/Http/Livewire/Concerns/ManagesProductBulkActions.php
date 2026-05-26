<?php

namespace App\Http\Livewire\Concerns;

use App\Models\Product;
use App\Support\PlanFeatureGate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Acciones masivas sobre productos seleccionados en la tabla.
 * Requiere: $this->buildFilteredProductQuery() y $this->getRestaurantId() (protected en Products).
 */
trait ManagesProductBulkActions
{
    public bool $confirmingBulkDelete = false;

    public function confirmBulkDelete(): void
    {
        $this->confirmingBulkDelete = true;
    }

    public function cancelBulkDelete(): void
    {
        $this->confirmingBulkDelete = false;
    }

    public function bulkDelete(): void
    {
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            $this->confirmingBulkDelete = false;
            return;
        }

        $rid = $this->getRestaurantId();
        $products = Product::query()
            ->whereIn('id', $ids)
            ->where('restaurant_id', $rid)
            ->get();

        foreach ($products as $product) {
            if ($product->photo && ! Str::startsWith($product->photo, ['http://', 'https://'])) {
                Storage::disk('public')->delete($product->photo);
            }
            $product->delete();
        }

        $count = count($ids);
        $this->selectedProducts = [];
        $this->confirmingBulkDelete = false;
        session()->flash('message', "Se han borrado {$count} productos.");
    }

    private function currentListPage(): int
    {
        $p = (int) (data_get($this->paginators, 'page', $this->page ?? 1) ?: 1);

        return $p >= 1 ? $p : 1;
    }

    private function validatedSelectedIds(): array
    {
        $ids = array_values(array_filter(array_map('intval', $this->selectedProducts ?? [])));
        if ($ids === []) {
            return [];
        }

        $rid = $this->getRestaurantId();
        if (! $rid) {
            return [];
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->where('restaurant_id', $rid)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    public function toggleSelectCurrentPage(): void
    {
        $query = $this->buildFilteredProductQuery()->orderBy('order');

        if ($this->perPageOption === 'all') {
            $ids = $query->pluck('id')->map(static fn ($id) => (int) $id)->all();
        } else {
            $perPage = (int) $this->perPageOption;
            $currentPage = $this->currentListPage();
            $paginator = $query->paginate($perPage, ['*'], 'page', $currentPage);
            $ids = $paginator->getCollection()->pluck('id')->map(static fn ($id) => (int) $id)->all();
        }

        if ($ids === []) {
            return;
        }

        $selectedInts = array_map('intval', $this->selectedProducts ?? []);
        $allOnPageSelected = count(array_intersect($ids, $selectedInts)) === count($ids);

        if ($allOnPageSelected) {
            $this->selectedProducts = array_values(array_diff($selectedInts, $ids));
        } else {
            $this->selectedProducts = array_values(array_unique(array_merge($selectedInts, $ids)));
        }
    }

    public function clearBulkSelection(): void
    {
        $this->selectedProducts = [];
    }

    public function bulkSetFeatured(bool $on): void
    {
        if (! PlanFeatureGate::allows('offers')) {
            session()->flash('plan_error', __('admin.plan.offers_required'));
            return;
        }
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            return;
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }
        $q->update(['featured' => $on]);

        $this->selectedProducts = [];
        session()->flash('message', $on ? __('admin.products.flash_bulk_featured_on') : __('admin.products.flash_bulk_featured_off'));
    }

    public function bulkSetRecommended(bool $on): void
    {
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            return;
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }
        $q->update(['recommended' => $on]);

        $this->selectedProducts = [];
        session()->flash('message', $on ? __('admin.products.flash_bulk_recommended_on') : __('admin.products.flash_bulk_recommended_off'));
    }

    public function bulkShowOnMenu(): void
    {
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            return;
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }
        $q->update(['hidden' => false]);

        $this->selectedProducts = [];
        session()->flash('message', __('admin.products.flash_bulk_visible'));
    }

    public function bulkHideFromMenu(): void
    {
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            return;
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }
        $q->update(['hidden' => true]);

        $this->selectedProducts = [];
        session()->flash('message', __('admin.products.flash_bulk_hidden'));
    }

    /** Quita solo el flag de oferta; no borra precios por si quieres reactivarlas. */
    public function bulkClearOfferFlag(): void
    {
        $ids = $this->validatedSelectedIds();
        if ($ids === []) {
            return;
        }

        $q = Product::query()->whereIn('id', $ids);
        if ($rid = $this->getRestaurantId()) {
            $q->where('restaurant_id', $rid);
        }
        $q->update(['offer' => false]);

        $this->selectedProducts = [];
        session()->flash('message', __('admin.products.flash_bulk_offer_cleared'));
    }
}
