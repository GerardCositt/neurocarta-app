<?php

namespace App\Http\Livewire\Admin;

use App\Models\Restaurant;
use App\Models\RestaurantApiKey;
use App\Support\PlanFeatureGate;
use Livewire\Component;

class ApiSettings extends Component
{
    public ?RestaurantApiKey $activeKey = null;
    public ?string $newRawKey = null;
    public bool $confirmingRevoke = false;

    private function restaurant(): ?Restaurant
    {
        $id = session('admin_restaurant_id');
        return $id ? Restaurant::find($id) : null;
    }

    public function mount(): void
    {
        $restaurant = $this->restaurant();
        if ($restaurant) {
            $this->activeKey = $restaurant->apiKeys()->whereNull('revoked_at')->latest()->first();
        }
    }

    public function generate(): void
    {
        $restaurant = $this->restaurant();
        if (! $restaurant) return;

        // Revoke any existing active key first
        $restaurant->apiKeys()->whereNull('revoked_at')->update(['revoked_at' => now()]);

        ['model' => $model, 'raw_key' => $rawKey] = RestaurantApiKey::generate($restaurant->id);

        $this->activeKey  = $model;
        $this->newRawKey  = $rawKey;
        $this->confirmingRevoke = false;
    }

    public function confirmRevoke(): void
    {
        $this->confirmingRevoke = true;
    }

    public function revoke(): void
    {
        $restaurant = $this->restaurant();
        if (! $restaurant) return;

        $restaurant->apiKeys()->whereNull('revoked_at')->update(['revoked_at' => now()]);

        $this->activeKey        = null;
        $this->newRawKey        = null;
        $this->confirmingRevoke = false;

        session()->flash('status', 'API key revocada correctamente.');
    }

    public function cancelRevoke(): void
    {
        $this->confirmingRevoke = false;
    }

    public function render()
    {
        $restaurant = $this->restaurant();
        $hasApi     = $restaurant ? PlanFeatureGate::check('api', $restaurant->account) : false;

        return view('livewire.admin.api-settings', [
            'hasApi'     => $hasApi,
            'restaurant' => $restaurant,
        ])->layout('layouts.app');
    }
}
