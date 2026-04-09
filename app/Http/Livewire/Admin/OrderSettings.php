<?php

namespace App\Http\Livewire\Admin;

use App\Models\Setting;
use Livewire\Component;

class OrderSettings extends Component
{
    /** @var string 'order'|'list' */
    public $ordersMode = 'list';

    private function getRestaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    public function mount(): void
    {
        $m = (string) Setting::get('orders_mode', 'list', $this->getRestaurantId());
        $this->ordersMode = in_array($m, ['order', 'list'], true) ? $m : 'list';
    }

    public function updatedOrdersMode($value): void
    {
        if ((string) $value === 'order') {
            // Funcionalidad V3: mostrar mensaje y revertir a lista
            $this->ordersMode = 'list';
            Setting::put('orders_mode', 'list', $this->getRestaurantId());
            session()->flash('message', __('admin.order_settings.v3_active'));
            return;
        }

        Setting::put('orders_mode', 'list', $this->getRestaurantId());
        $this->ordersMode = 'list';
        session()->flash('message', __('admin.order_settings.list_mode'));
    }

    public function render()
    {
        return view('livewire.admin.order-settings');
    }
}
