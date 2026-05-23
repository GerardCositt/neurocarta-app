<?php

namespace App\Http\Livewire\Category;

use App\Models\Category;
use App\Models\Product;
use App\Support\CaseInsensitiveLike;
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

    public $hidden = false;

    public $confirmingCategoryDeletion = false;

    /** @var int|null Categoría cuyo listado de productos está desplegado. */
    public $expandedCategoryId = null;

    private function getRestaurantId(): ?int
    {
        $restaurantId = session('admin_restaurant_id');
        if ($restaurantId) {
            return (int) $restaurantId;
        }

        $cookieId = (int) request()->cookie('preview_restaurant_id');
        if ($cookieId <= 0) {
            return null;
        }

        $user = auth()->user();
        $account = $user ? $user->accounts()->first() : null;
        if (! $account) {
            return null;
        }

        if (! $account->restaurants()->where('id', $cookieId)->exists()) {
            return null;
        }

        session(['admin_restaurant_id' => $cookieId]);
        session()->save();

        return $cookieId;
    }

    public function render()
    {
        $restaurantId = $this->getRestaurantId();

        $query = Category::withCount('products')->orderBy('order');

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        $search = trim((string) ($this->q ?? ''));
        if ($search !== '') {
            CaseInsensitiveLike::applyWhere($query, 'categories.name', $search);
        }

        $expandedProducts = collect();
        if ($this->expandedCategoryId) {
            $expandedProducts = Product::where('category_id', $this->expandedCategoryId)
                ->when($restaurantId, fn ($q) => $q->where('restaurant_id', $restaurantId))
                ->orderBy('order')
                ->get();
        }

        $navIds = ($this->isOpen && $this->category_id)
            ? \App\Models\Category::where('restaurant_id', $restaurantId)->orderBy('order')->orderBy('id')->pluck('id')->toArray()
            : [];
        $navIdx = $navIds ? array_search((int) $this->category_id, $navIds, true) : false;

        return view('livewire.category.show', [
            'categories'  => $query->get(),
            'expandedProducts' => $expandedProducts,
            'navPosition' => ($navIdx !== false) ? $navIdx + 1 : null,
            'navTotal'    => count($navIds),
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
        $this->msgError = null;
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
        $this->hidden = (bool) $category->hidden;
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
        $this->hidden = false;
    }

    public function save(): void
    {
        $this->persistCategory();
    }

    public function storeAndClose(): void
    {
        if ($this->persistCategory()) {
            $this->closeForm();
        }
    }

    public function saveAndNext(): void
    {
        if ($this->persistCategory()) {
            $next = $this->adjacentCategoryId(1);
            if ($next) $this->edit($next);
        }
    }

    public function saveAndPrev(): void
    {
        if ($this->persistCategory()) {
            $prev = $this->adjacentCategoryId(-1);
            if ($prev) $this->edit($prev);
        }
    }

    private function adjacentCategoryId(int $dir): ?int
    {
        $rid = $this->getRestaurantId();
        $ids = \App\Models\Category::where('restaurant_id', $rid)
            ->orderBy('order')->orderBy('id')->pluck('id')->toArray();
        $pos = array_search((int) $this->category_id, $ids, true);
        if ($pos === false) return null;
        return $ids[$pos + $dir] ?? null;
    }

    private function persistCategory(): bool
    {
        $this->msgError = null;

        $this->validate(
            ['name' => 'required|min:2'],
            [
                'name.required' => __('validation.category.name_required'),
                'name.min' => __('validation.category.name_min', ['min' => 2]),
            ]
        );

        $restaurantId = $this->getRestaurantId();
        if (! $restaurantId) {
            $this->msgError = __('admin.category_page.restaurant_required');
            return false;
        }

        if ($this->category_id) {
            $query = Category::query()->where('id', $this->category_id);
            $query->where('restaurant_id', $restaurantId);
            $category = $query->firstOrFail();
            $category->update([
                'name' => $this->name,
                'hidden' => (bool) $this->hidden,
            ]);
            session()->flash('message', __('admin.category.flash_updated'));
            $this->emit('navigationMenuRefresh');
            return true;
        } else {
            $svc = app(PlanEntitlementService::class);
            $restaurant = \App\Models\Restaurant::withoutGlobalScopes()->find($restaurantId);
            $account = $svc->accountForRestaurant($restaurant);
            if (! $account) {
                $this->msgError = __('admin.category_page.account_required');
                return false;
            }

            try {
                $svc->assertCanCreateCategory($account);
            } catch (\RuntimeException $e) {
                $this->msgError = $e->getMessage();
                return false;
            }

            Category::create([
                'name' => $this->name,
                'hidden' => (bool) $this->hidden,
                'restaurant_id' => $restaurantId,
            ]);
            session()->flash('message', __('admin.category.flash_created'));
            $this->emit('navigationMenuRefresh');
            return true;
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
        $category->hidden = ! $category->hidden;
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
            $this->emit('navigationMenuRefresh');
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
