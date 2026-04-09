<?php

namespace App\Http\Livewire;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class OrderList extends Component
{
    use WithPagination;

    public $q = '';
    public $statusFilter = '';

    protected $queryString = [
        'q'            => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatus(int $orderId, string $status): void
    {
        if (! in_array($status, Order::allowedStatuses(), true)) {
            return;
        }
        $order = Order::find($orderId);
        if ($order) {
            $order->status = $status;
            $order->save();
            session()->flash('message', __('admin.order_list.flash_status'));
        }
    }

    private function getRestaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    public function render()
    {
        $restaurantId = $this->getRestaurantId();

        $orders = Order::query()
            ->with('items')
            ->when($restaurantId, function ($query) use ($restaurantId) {
                $query->where('restaurant_id', $restaurantId);
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->q, function ($query) {
                $raw = trim($this->q);
                $term = '%' . $raw . '%';
                $query->where(function ($q) use ($term, $raw) {
                    $q->where('customer_name', 'like', $term)
                        ->orWhere('customer_phone', 'like', $term)
                        ->orWhere('customer_notes', 'like', $term);
                    if (preg_match('/^#?(\d+)$/', $raw, $m)) {
                        $q->orWhere('id', (int) $m[1]);
                    }
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.order-list', [
            'orders'        => $orders,
            'statusLabels'  => Order::statusLabels(),
            'allStatuses'   => Order::allowedStatuses(),
        ]);
    }
}
