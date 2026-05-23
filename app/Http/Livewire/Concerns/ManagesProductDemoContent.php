<?php

namespace App\Http\Livewire\Concerns;

use App\Models\Category;
use App\Models\Product;
use App\Support\DemoContent;
use App\Support\DemoProductPhotoResolver;
use App\Support\OfficialAllergens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Carga y borrado de contenido de plantilla/demo en la carta.
 * Requiere: $this->getRestaurantId() y $this->notifyNavigationMenuRefresh() (protected en Products).
 */
trait ManagesProductDemoContent
{
    protected function restaurantCanBulkDeleteTemplate(int $restaurantId): bool
    {
        $products = Product::withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->get(['name', 'description', 'price', 'is_template']);

        if ($products->isEmpty()) {
            return false;
        }

        $anyTemplate = $products->contains(static fn ($p) => $p->is_template === true);
        $anyNonTemplateRow = $products->contains(static fn ($p) => $p->is_template === false);

        if ($anyTemplate && $anyNonTemplateRow) {
            return false;
        }

        if ($anyTemplate) {
            return $products->every(static fn ($p) => $p->is_template === true);
        }

        $demoSigs = $this->demoProductSignatures();

        foreach ($products as $p) {
            $sig = $this->productSignature([
                'name' => $p->name,
                'description' => $p->description,
                'price' => $p->price,
            ]);
            if (! in_array($sig, $demoSigs, true)) {
                return false;
            }
        }

        return true;
    }

    private function demoProductSignatures(): array
    {
        return array_values(array_unique(array_map(
            fn ($product) => $this->productSignature($product),
            DemoContent::products()
        )));
    }

    private function productSignature(array $product): string
    {
        return implode('|', [
            trim((string) ($product['name'] ?? '')),
            trim((string) ($product['description'] ?? '')),
            trim((string) ($product['price'] ?? '')),
        ]);
    }

    private function demoCategoryNames(): array
    {
        return array_values(array_unique(array_map(
            fn ($category) => trim((string) $category['name']),
            DemoContent::categories()
        )));
    }

    public function confirmLoadDemo(): void
    {
        $this->confirmingLoadDemo = true;
    }

    public function cancelLoadDemo(): void
    {
        $this->confirmingLoadDemo = false;
    }

    public function confirmDeleteDemo(): void
    {
        $restaurantId = $this->getRestaurantId();
        if (! $restaurantId || ! $this->restaurantCanBulkDeleteTemplate((int) $restaurantId)) {
            return;
        }

        $this->confirmingDeleteDemo = true;
    }

    public function cancelDeleteDemo(): void
    {
        $this->confirmingDeleteDemo = false;
    }

    public function loadDemoContent(): void
    {
        $this->confirmingLoadDemo = false;

        $restaurantId = $this->getRestaurantId();
        if (! $restaurantId) {
            return;
        }

        if (Product::where('restaurant_id', $restaurantId)->exists()) {
            return;
        }

        $existingOfficialSlugs = \App\Models\Allergen::query()
            ->whereNotNull('slug')
            ->whereIn('slug', OfficialAllergens::slugs())
            ->pluck('slug')
            ->all();

        foreach (OfficialAllergens::list() as $row) {
            if (in_array($row['slug'], $existingOfficialSlugs, true)) {
                continue;
            }

            \App\Models\Allergen::create([
                'slug'        => $row['slug'],
                'name'        => $row['name'],
                'image'       => 'allergens/official/' . $row['file'],
                'is_official' => true,
                'sort_order'  => $row['sort'],
                'hidden'      => false,
            ]);
        }

        $allergenMap = \App\Models\Allergen::query()
            ->whereNotNull('slug')
            ->pluck('id', 'slug')
            ->all();

        $categoryMap = [];
        foreach (DemoContent::categories() as $cat) {
            $category = Category::create([
                'name'          => $cat['name'],
                'hidden'        => false,
                'order'         => $cat['order'],
                'restaurant_id' => $restaurantId,
            ]);
            $categoryMap[$cat['name']] = $category->id;
        }

        foreach (DemoContent::products() as $data) {
            $product = Product::create([
                'name'          => $data['name'],
                'description'   => $data['description'],
                'price'         => $data['price'],
                'category_id'   => $categoryMap[$data['category']] ?? null,
                'restaurant_id' => $restaurantId,
                'photo'         => DemoProductPhotoResolver::resolveForTemplateProduct($data['photo']),
                'featured'      => $data['featured'],
                'recommended'   => $data['recommended'],
                'hidden'        => false,
                'offer'         => false,
                'order'         => $data['order'],
                'is_template'   => true,
            ]);

            $allergenIds = array_values(array_filter(
                array_map(fn ($slug) => $allergenMap[$slug] ?? null, $data['allergens'])
            ));
            if ($allergenIds) {
                $product->allergens()->sync($allergenIds);
            }
        }

        session()->flash('message', __('admin.products.demo_loaded_flash'));
        $this->notifyNavigationMenuRefresh();
    }

    public function deleteDemoContent(): void
    {
        $this->confirmingDeleteDemo = false;

        $restaurantId = $this->getRestaurantId();
        if (! $restaurantId || ! $this->restaurantCanBulkDeleteTemplate((int) $restaurantId)) {
            return;
        }

        $demoCategoryNames = $this->demoCategoryNames();

        DB::transaction(function () use ($restaurantId, $demoCategoryNames) {
            $products = Product::query()
                ->where('restaurant_id', $restaurantId)
                ->get();

            foreach ($products as $product) {
                $product->allergens()->detach();
                if ($product->photo && ! Str::startsWith($product->photo, ['http://', 'https://'])) {
                    Storage::disk('public')->delete($product->photo);
                }
                $product->delete();
            }

            Category::query()
                ->where('restaurant_id', $restaurantId)
                ->whereIn('name', $demoCategoryNames)
                ->delete();
        });

        $this->selectedProducts = [];
        $this->resetPage();

        session()->flash('message', __('admin.products.demo_deleted_flash'));
        $this->notifyNavigationMenuRefresh();
    }
}
