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

    public bool   $showForm         = false;
    public bool   $confirmingCreate = false;
    public string $newName = '';
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
        $currentRestaurant = $currentRestaurantId ? Restaurant::find($currentRestaurantId) : null;
        $account = $currentRestaurant
            ? $svc->accountForRestaurant($currentRestaurant)
            : auth()->user()->accounts()->first();
        if ($account) {
            try {
                $svc->assertCanCreateRestaurant($account);
            } catch (\RuntimeException $e) {
                session()->flash('message', $e->getMessage());
                return;
            }
        }

        $this->validate([
            'newName' => 'required|string|min:2|max:100',
        ], [
            'newName.required' => __('validation.restaurant_create.name_required'),
            'newName.min'      => __('validation.restaurant_create.name_min', ['min' => 2]),
            'newName.max'      => __('validation.restaurant_create.name_max', ['max' => 100]),
        ]);

        $subdomain = $this->generateUniqueSubdomain(trim($this->newName));

        $restaurant = Restaurant::create([
            'name'       => trim($this->newName),
            'subdomain'  => $subdomain,
            'account_id' => $account ? $account->id : null,
        ]);

        session(['admin_restaurant_id' => $restaurant->id]);
        Cookie::queue('preview_restaurant_id', (string) $restaurant->id, 60 * 24 * 365);
        $this->currentId    = $restaurant->id;
        $this->showForm         = false;
        $this->confirmingCreate = false;
        $this->newName          = '';
        $this->restaurants  = $this->userRestaurants();

        $this->redirect(request()->header('Referer') ?: route('dashboard'));
    }

    public function render()
    {
        return view('livewire.admin.restaurant-switcher');
    }

    private function generateUniqueSubdomain(string $name): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', \Illuminate\Support\Str::ascii($name)));
        $base = trim($base, '-');
        $base = substr($base, 0, 48) ?: 'restaurante';

        $slug = $base;
        $i    = 2;
        while (Restaurant::where('subdomain', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function userRestaurants()
    {
        $user = auth()->user();

        if ($user->hasPanelAdminAccess()) {
            return Restaurant::orderBy('name')->get();
        }

        $accountIds = $user->accounts()->pluck('accounts.id');

        if ($accountIds->isEmpty()) {
            return collect();
        }

        return Restaurant::whereIn('account_id', $accountIds)->orderBy('name')->get();
    }
}
