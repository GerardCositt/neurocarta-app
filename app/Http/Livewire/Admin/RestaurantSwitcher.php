<?php

namespace App\Http\Livewire\Admin;

use App\Models\Restaurant;
use App\Models\Translation;
use App\Services\PlanEntitlementService;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class RestaurantSwitcher extends Component
{
    public string $mode          = 'sidebar';
    public $restaurants;
    public $currentId;

    public bool   $showForm      = false;
    public string $newName       = '';
    public string $newSubdomain  = '';
    public ?int   $pendingDelete = null;   // fila en estado pre-confirmación

    protected $listeners = ['confirmDeleteRestaurant' => 'deleteRestaurant'];

    public function mount(): void
    {
        $this->restaurants = $this->userRestaurants();
        $this->currentId   = session('admin_restaurant_id') ?? optional($this->restaurants->first())->id;
    }

    public function switchTo(int $id): void
    {
        session(['admin_restaurant_id' => $id]);
        session()->save();
        $this->currentId = $id;
        Cookie::queue('preview_restaurant_id', (string) $id, 60 * 24 * 365);
        $this->redirect(request()->header('Referer') ?: route('product'));
    }

    // Paso 1: muestra botón "Eliminar" inline en la fila
    public function askDelete(int $id): void
    {
        if ($id === $this->currentId) return;
        $this->pendingDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->pendingDelete = null;
    }

    // Paso 2: abre el modal con confirmación por nombre
    public function askDeleteConfirm(int $id): void
    {
        if ($id === $this->currentId) return;
        $r = Restaurant::find($id);
        if (!$r) return;
        $this->pendingDelete = null;
        $this->dispatchBrowserEvent('show-delete-restaurant', [
            'id'   => $r->id,
            'name' => $r->name,
        ]);
    }

    public function deleteRestaurant(int $id): void
    {
        if ($id === $this->currentId) return;

        DB::transaction(function () use ($id) {
            $restaurant = Restaurant::with(['products', 'categories', 'pairings', 'advices', 'orders', 'settings'])->find($id);
            if (! $restaurant) {
                return;
            }

            Translation::query()
                ->where(function ($query) use ($restaurant) {
                    $query->where('translatable_type', \App\Models\Product::class)
                        ->whereIn('translatable_id', $restaurant->products->pluck('id'));
                })
                ->orWhere(function ($query) use ($restaurant) {
                    $query->where('translatable_type', \App\Models\Category::class)
                        ->whereIn('translatable_id', $restaurant->categories->pluck('id'));
                })
                ->orWhere(function ($query) use ($restaurant) {
                    $query->where('translatable_type', \App\Models\Pairing::class)
                        ->whereIn('translatable_id', $restaurant->pairings->pluck('id'));
                })
                ->orWhere(function ($query) use ($restaurant) {
                    $query->where('translatable_type', \App\Models\Advice::class)
                        ->whereIn('translatable_id', $restaurant->advices->pluck('id'));
                })
                ->delete();

            foreach ($restaurant->products as $product) {
                $product->allergens()->detach();
                $product->delete();
            }

            $restaurant->categories()->delete();
            $restaurant->pairings()->delete();
            $restaurant->advices()->delete();
            $restaurant->orders()->delete();
            $restaurant->settings()->delete();
            $restaurant->delete();
        });

        $this->restaurants = $this->userRestaurants();
    }

    public function createRestaurant(): void
    {
        $svc = app(PlanEntitlementService::class);
        $currentRestaurantId = session('admin_restaurant_id');
        $currentRestaurant = $currentRestaurantId ? Restaurant::find($currentRestaurantId) : Restaurant::first();
        $account = $currentRestaurant ? $svc->accountForRestaurant($currentRestaurant) : null;
        if ($account) {
            try {
                $svc->assertCanCreateRestaurant($account);
            } catch (\RuntimeException $e) {
                session()->flash('message', $e->getMessage());
                return;
            }
        }

        $this->validate([
            'newName'      => 'required|string|min:2|max:100',
            'newSubdomain' => 'required|string|min:2|max:50|alpha_dash|unique:restaurants,subdomain',
        ], [
            'newName.required'        => __('validation.restaurant_create.name_required'),
            'newName.min'             => __('validation.restaurant_create.name_min', ['min' => 2]),
            'newName.max'             => __('validation.restaurant_create.name_max', ['max' => 100]),
            'newSubdomain.required'   => __('validation.restaurant_create.subdomain_required'),
            'newSubdomain.min'        => __('validation.restaurant_create.subdomain_min', ['min' => 2]),
            'newSubdomain.max'        => __('validation.restaurant_create.subdomain_max', ['max' => 50]),
            'newSubdomain.alpha_dash' => __('validation.restaurant_create.subdomain_alpha_dash'),
            'newSubdomain.unique'     => __('validation.restaurant_create.subdomain_unique'),
        ]);

        $restaurant = Restaurant::create([
            'name'      => trim($this->newName),
            'subdomain' => strtolower(trim($this->newSubdomain)),
            'account_id' => $account ? $account->id : null,
        ]);

        session(['admin_restaurant_id' => $restaurant->id]);
        Cookie::queue('preview_restaurant_id', (string) $restaurant->id, 60 * 24 * 365);
        $this->currentId    = $restaurant->id;
        $this->showForm     = false;
        $this->newName      = '';
        $this->newSubdomain = '';
        $this->restaurants  = $this->userRestaurants();

        $this->redirect(request()->header('Referer') ?: route('dashboard'));
    }

    public function render()
    {
        return view('livewire.admin.restaurant-switcher');
    }

    private function userRestaurants()
    {
        $user = auth()->user();
        $accountIds = $user->accounts()->pluck('accounts.id');

        if ($accountIds->isEmpty()) {
            // Usuario legacy sin account (ej. test@test.com) — ve todos
            return Restaurant::orderBy('name')->get();
        }

        return Restaurant::whereIn('account_id', $accountIds)->orderBy('name')->get();
    }
}
