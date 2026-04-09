<?php

namespace App\Http\Livewire\Category;

use App\Models\Category;
use App\Models\Product;
use App\Services\PlanEntitlementService;
use Livewire\Component;

class Show extends Component
{
    public $q = '';

    public $msgError;

    public $isOpen = false;

    /** @var int|string vacío = nueva categoría */
    public $category_id = '';

    public $name = '';

    /** @var bool Ocultar en carta (active=true en BD según la vista actual). */
    public $active = false;

    public $confirmingCategoryDeletion = false;

    /** @var int|null Categoría cuyo listado de productos está desplegado. */
    public $expandedCategoryId = null;

    private function getRestaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    public function render()
    {
        $restaurantId = $this->getRestaurantId();

        $query = Category::withCount('products')->orderBy('order');

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        if ($this->q) {
            $query->where('name', 'like', '%' . $this->q . '%');
        }

        $expandedProducts = collect();
        if ($this->expandedCategoryId) {
            $expandedProducts = Product::where('category_id', $this->expandedCategoryId)
                ->orderBy('order')
                ->get();
        }

        return view('livewire.category.show', [
            'categories' => $query->get(),
            'expandedProducts' => $expandedProducts,
        ]);
    }

    public function toggleProductList(int $id): void
    {
        $this->expandedCategoryId = $this->expandedCategoryId === $id ? null : $id;
    }

    public function closeExpandedProductList(): void
    {
        $this->expandedCategoryId = null;
    }

    public function openForm(): void
    {
        $this->resetFormFields();
        $this->isOpen = true;
    }

    public function edit(int $id): void
    {
        $restaurantId = $this->getRestaurantId();
        $query = Category::query()->where('id', $id);
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        $category = $query->firstOrFail();
        $this->category_id = $category->id;
        $this->name = $category->name;
        $this->active = (bool) $category->active;
        $this->isOpen = true;
    }

    public function closeForm(): void
    {
        $this->isOpen = false;
        $this->resetFormFields();
    }

    private function resetFormFields(): void
    {
        $this->category_id = '';
        $this->name = '';
        $this->active = false;
    }

    public function save(): void
    {
        $this->persistCategory();
    }

    public function storeAndClose(): void
    {
        $this->persistCategory();
        $this->closeForm();
    }

    private function persistCategory(): void
    {
        $this->validate(
            ['name' => 'required|min:2'],
            [
                'name.required' => __('validation.category.name_required'),
                'name.min' => __('validation.category.name_min', ['min' => 2]),
            ]
        );

        $restaurantId = $this->getRestaurantId();

        if ($this->category_id) {
            $query = Category::query()->where('id', $this->category_id);
            if ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            }
            $category = $query->firstOrFail();
            $category->update([
                'name' => $this->name,
                'active' => (bool) $this->active,
            ]);
            session()->flash('message', __('admin.category.flash_updated'));
        } else {
            $svc = app(PlanEntitlementService::class);
            $restaurant = $restaurantId ? \App\Models\Restaurant::find($restaurantId) : null;
            $account = $svc->accountForRestaurant($restaurant);
            if ($account) {
                try {
                    $svc->assertCanCreateCategory($account);
                } catch (\RuntimeException $e) {
                    $this->msgError = $e->getMessage();
                    return;
                }
            }

            Category::create([
                'name' => $this->name,
                'active' => (bool) $this->active,
                'restaurant_id' => $restaurantId,
            ]);
            session()->flash('message', __('admin.category.flash_created'));
        }
    }

    public function toggleState(int $id): void
    {
        $restaurantId = $this->getRestaurantId();
        $query = Category::query()->where('id', $id);
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        $category = $query->firstOrFail();
        $category->active = ! $category->active;
        $category->save();
    }

    public function confirmDeleteCurrentCategory(): void
    {
        if (! $this->category_id) {
            return;
        }
        $this->confirmingCategoryDeletion = (int) $this->category_id;
    }

    public function deleteCategoryConfirmed(): void
    {
        $id = $this->confirmingCategoryDeletion;
        $this->confirmingCategoryDeletion = false;
        if (! $id) {
            return;
        }

        $this->msgError = null;

        $restaurantId = $this->getRestaurantId();
        $query = Category::query()->where('id', $id);
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        $category = $query->first();
        if (! $category) {
            return;
        }

        if ($category->products()->count() === 0) {
            $category->delete();
            session()->flash('message', __('admin.category.flash_deleted'));
            if ((int) $this->category_id === (int) $id) {
                $this->closeForm();
            }
        } else {
            $this->msgError = __('admin.category_page.delete_blocked_has_products', ['name' => $category->name]);
            if ($this->isOpen && (int) $this->category_id === (int) $id) {
                // Mantener ficha abierta para que el usuario lea el error
            }
        }
    }
}
