<?php

namespace App\Http\Livewire\Admin;

use App\Models\Setting;
use App\Services\AiCreditService;
use Livewire\Component;

class Settings extends Component
{
    public $ordersEnabled = true;

    private function getRestaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    private function aiCredits(): AiCreditService
    {
        return app(AiCreditService::class);
    }

    public function mount(): void
    {
        $this->ordersEnabled = Setting::getBool('orders_enabled', true, $this->getRestaurantId());
    }

    public function updatedOrdersEnabled($value): void
    {
        $val = (bool) $value;
        Setting::put('orders_enabled', $val, $this->getRestaurantId());
        $this->ordersEnabled = $val;
        session()->flash('message', $val ? __('admin.settings.orders_on') : __('admin.settings.orders_off'));
    }

    public function render()
    {
        return view('livewire.admin.settings', [
            'aiCredits' => $this->aiCredits()->summary(),
        ]);
    }
}
