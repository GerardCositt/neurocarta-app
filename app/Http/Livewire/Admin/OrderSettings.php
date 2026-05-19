<?php

namespace App\Http\Livewire\Admin;

use App\Models\Setting;
use Livewire\Component;

class OrderSettings extends Component
{
    /** Modo UI: solo `list` persiste; `order` es vista previa con mensaje V3 y reverso automático. */
    public string $ordersMode = 'list';

    private function getRestaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    public function mount(): void
    {
        $rid = $this->getRestaurantId();
        $stored = trim((string) Setting::get('orders_mode', 'list', $rid));
        if (! in_array($stored, ['order', 'list'], true)) {
            $stored = 'list';
        }
        // Solo está soportado el modo lista en BBDD; normalizar valores antiguos.
        if ($stored !== 'list') {
            Setting::put('orders_mode', 'list', $rid);
        }
        $this->ordersMode = 'list';
    }

    public function updatedOrdersMode(string $value): void
    {
        $rid = $this->getRestaurantId();

        if ($value === 'order') {
            Setting::put('orders_mode', 'list', $rid);
            session()->flash('order_settings_v3_overlay', true);
            session()->flash('message', __('admin.order_settings.v3_active'));
            $this->dispatchBrowserEvent('orders-mode-revert-list', [
                'delayMs' => 2500,
                'componentId' => $this->id,
            ]);

            return;
        }

        if ($value === 'list') {
            Setting::put('orders_mode', 'list', $rid);
        }
    }

    /** Llamado desde JS tras el temporizador para volver a «Lista» sin mensajes extra. */
    public function revertOrdersModeToList(): void
    {
        $this->ordersMode = 'list';
    }

    public function render()
    {
        return view('livewire.admin.order-settings');
    }
}
