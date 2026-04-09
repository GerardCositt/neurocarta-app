<?php

namespace App\Http\Livewire\Allergen;

use App\Models\Allergen;
use App\Models\Product;
use App\Services\ImageAssetService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;

    public $q = '';

    public $msgError;

    public $isOpen = false;

    /** @var int|string */
    public $allergen_id = '';

    public $name = '';

    /** @var bool Ocultar en carta (active=true en BD según la vista actual). */
    public $active = false;

    public $image;

    /** @var array<int|string> */
    public $linkedProductIds = [];

    public $linkProductQ = '';

    /** @var bool|int */
    public $confirmingAllergenDeletion = false;

    public $showRemoveAllergenImageModal = false;

    /** @var int|null */
    public $pendingRemoveImageAllergenId = null;

    /** @var int|null Fila cuyo listado de productos vinculados está desplegado. */
    public $expandedLinkedProductsAllergenId = null;

    private function getRestaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    private function imageAssets(): ImageAssetService
    {
        return app(ImageAssetService::class);
    }

    public function render()
    {
        $restaurantId = $this->getRestaurantId();

        $query = Allergen::query()
            ->withCount([
                'products as products_count' => function ($q) use ($restaurantId) {
                    if ($restaurantId) {
                        $q->where('products.restaurant_id', $restaurantId);
                    }
                },
            ])
            ->orderByDesc('is_official')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($this->q) {
            $query->where('name', 'like', '%' . $this->q . '%');
        }

        $productsForLinkQuery = Product::with('category')
            ->when($restaurantId, fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->orderBy('name');
        if ($this->isOpen && $this->linkProductQ !== '') {
            $term = '%' . addcslashes($this->linkProductQ, '%_\\') . '%';
            $productsForLinkQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $term));
            });
        }

        $expandedLinkedProducts = collect();
        $expandedAllergenName = '';
        if ($this->expandedLinkedProductsAllergenId) {
            $exp = Allergen::query()
                ->whereKey($this->expandedLinkedProductsAllergenId)
                ->with([
                    'products' => fn ($q) => $q
                        ->with('category')
                        ->when($restaurantId, fn ($q) => $q->where('products.restaurant_id', $restaurantId))
                        ->orderBy('name'),
                ])
                ->first();
            if ($exp) {
                $expandedLinkedProducts = $exp->products;
                $expandedAllergenName = $exp->name;
            }
        }

        $editingAllergenImageUrl = null;
        if ($this->isOpen && $this->allergen_id) {
            $a = Allergen::query()->find($this->allergen_id);
            $editingAllergenImageUrl = $a?->image_url;
        }

        return view('livewire.allergen.show', [
            'allergens'               => $query->get(),
            'productsForLink'         => $productsForLinkQuery->get(),
            'expandedLinkedProducts'  => $expandedLinkedProducts,
            'expandedAllergenName'    => $expandedAllergenName,
            'editingAllergenImageUrl' => $editingAllergenImageUrl,
        ]);
    }

    public function toggleLinkedProductsList(int $id): void
    {
        if ($this->expandedLinkedProductsAllergenId === $id) {
            $this->expandedLinkedProductsAllergenId = null;

            return;
        }
        $this->expandedLinkedProductsAllergenId = $id;
    }

    public function closeExpandedLinkedProductsList(): void
    {
        $this->expandedLinkedProductsAllergenId = null;
    }

    public function openForm(): void
    {
        $this->resetFormFields();
        $this->isOpen = true;
    }

    public function edit(int $id): void
    {
        $allergen = Allergen::findOrFail($id);
        $this->allergen_id = $allergen->id;
        $this->name = $allergen->name;
        $this->active = (bool) $allergen->active;
        $this->image = null;
        $restaurantId = $this->getRestaurantId();
        $linkedQuery = $allergen->products();
        if ($restaurantId) {
            $linkedQuery->where('products.restaurant_id', $restaurantId);
        }
        $this->linkedProductIds = $linkedQuery
            ->pluck('products.id')
            ->map(fn ($i) => (int) $i)
            ->all();
        $this->linkProductQ = '';
        $this->isOpen = true;
    }

    public function closeForm(): void
    {
        $this->isOpen = false;
        $this->resetFormFields();
    }

    private function resetFormFields(): void
    {
        $this->allergen_id = '';
        $this->name = '';
        $this->active = false;
        $this->image = null;
        $this->linkedProductIds = [];
        $this->linkProductQ = '';
    }

    /**
     * IDs de vínculos allergen-producto para sync: solo productos del local activo (admin),
     * manteniendo los vínculos de otros locales sin tocarlos.
     *
     * @return array<int,int>
     */
    private function syncableAllergenProductIds(Allergen $allergen): array
    {
        $requested = array_values(array_unique(array_map('intval', $this->linkedProductIds ?? [])));
        $restaurantId = $this->getRestaurantId();

        if (! $restaurantId) {
            return $requested;
        }

        $validForRestaurant = Product::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('id', $requested)
            ->pluck('id')
            ->all();

        $otherRestaurants = $allergen->products()
            ->where('products.restaurant_id', '!=', $restaurantId)
            ->pluck('products.id')
            ->all();

        return array_values(array_unique(array_merge($otherRestaurants, $validForRestaurant)));
    }

    public function save(): void
    {
        $this->persistAllergen();
    }

    public function storeAndClose(): void
    {
        $this->persistAllergen();
        $this->closeForm();
    }

    private function persistAllergen(): void
    {
        $this->validate(
            [
                'name'  => 'required|string|min:2|max:191',
                'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:1024',
            ],
            [
                'name.required' => __('validation.allergen.name_required'),
                'name.min' => __('validation.allergen.name_min', ['min' => 2]),
                'name.max' => __('validation.allergen.name_max', ['max' => 191]),
            ]
        );

        if ($this->allergen_id) {
            $allergen = Allergen::findOrFail($this->allergen_id);

            $data = [
                'name' => $this->name,
                'active' => (bool) $this->active,
            ];

            if ($this->image) {
                if ($allergen->image && ! $allergen->usesBundledOfficialAsset()) {
                    Storage::disk('public')->delete($allergen->image);
                }
                $data['image'] = $this->imageAssets()->storeUploadedImage($this->image, 'allergens', 800);
            }

            $allergen->update($data);

            $ids = $this->syncableAllergenProductIds($allergen);
            $allergen->products()->sync($ids);

            $this->image = null;
            session()->flash('message', __('admin.allergen_ui.flash_updated'));
        } else {
            $data = [
                'name' => $this->name,
                'active' => (bool) $this->active,
            ];

            if ($this->image) {
                $data['image'] = $this->imageAssets()->storeUploadedImage($this->image, 'allergens', 800);
            }

            $allergen = Allergen::create($data);
            $this->allergen_id = $allergen->id;

            $ids = $this->syncableAllergenProductIds($allergen);
            $allergen->products()->sync($ids);

            $this->image = null;
            session()->flash('message', __('admin.allergen_ui.flash_created'));
        }
    }

    public function toggleState(Allergen $allergen): void
    {
        $allergen->active = ! $allergen->active;
        $allergen->save();
    }

    public function confirmDeleteCurrentAllergen(): void
    {
        if (! $this->allergen_id) {
            return;
        }
        $this->confirmingAllergenDeletion = (int) $this->allergen_id;
    }

    public function deleteAllergenConfirmed(): void
    {
        $id = $this->confirmingAllergenDeletion;
        $this->confirmingAllergenDeletion = false;
        if (! $id) {
            return;
        }

        $this->msgError = null;

        $allergen = Allergen::find($id);
        if (! $allergen) {
            return;
        }

        $allergen->products()->detach();

        if ($allergen->image && ! $allergen->usesBundledOfficialAsset()) {
            Storage::disk('public')->delete($allergen->image);
        }

        $allergen->delete();
        session()->flash('message', __('admin.allergen_ui.flash_deleted'));

        if ((int) $this->allergen_id === (int) $id) {
            $this->closeForm();
        }
    }

    public function confirmRemoveAllergenImage(): void
    {
        if (! $this->allergen_id) {
            return;
        }
        $this->pendingRemoveImageAllergenId = (int) $this->allergen_id;
        $this->showRemoveAllergenImageModal = true;
    }

    public function cancelRemoveAllergenImage(): void
    {
        $this->showRemoveAllergenImageModal = false;
        $this->pendingRemoveImageAllergenId = null;
    }

    public function deleteImageConfirmed(): void
    {
        $id = $this->pendingRemoveImageAllergenId;
        $this->cancelRemoveAllergenImage();
        if (! $id) {
            return;
        }

        $allergen = Allergen::findOrFail($id);

        if ($allergen->image && ! $allergen->usesBundledOfficialAsset()) {
            Storage::disk('public')->delete($allergen->image);
        }

        $allergen->image = null;
        $allergen->save();

        session()->flash('message', __('admin.allergen_ui.flash_image_removed'));

        if ((int) $this->allergen_id === (int) $id) {
            $this->image = null;
        }
    }
}
