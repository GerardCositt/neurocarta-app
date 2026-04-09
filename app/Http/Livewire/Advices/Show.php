<?php

namespace App\Http\Livewire\Advices;

use App\Models\Advice;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public $active;
    public $q;
    public $sortBy = 'id';
    public $sortAsc = true;
    public $item;

    public $confirmingItemDeletion = false;
    public $confirmingItemAdd = false;
    public $pendingDeleteRowId = null;
    public $editingItemId = null;

    protected $queryString = [
        'active' => ['except' => false],
        'q' => ['except' => ''],
        'sortBy' => ['except' => 'id'],
        'sortAsc' => ['except' => true],
    ];

    protected $rules = [
        'item.title' => 'required|string|min:4',
        'item.advice' => 'required|string|min:4',
        'item.status' => 'boolean',
        'item.starts_at' => 'nullable|date',
        'item.ends_at' => 'nullable|date',
    ];

    private function restaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    public function render()
    {
        $items = Advice::query()
            ->where('restaurant_id', $this->restaurantId())
            ->when($this->q, function ($query) {
                return $query->where(function ($query) {
                    $query->where('title', 'like', '%'.$this->q.'%');
                });
            })
            ->when($this->active, function ($query) {
                return $query->active();
            })
            ->orderBy($this->sortBy, $this->sortAsc ? 'ASC' : 'DESC');

        $items = $items->paginate(10);

        return view('livewire.advices.show', [
            'items' => $items,
        ]);
    }

    public function updatingActive()
    {
        $this->resetPage();
    }

    public function updatingQ()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($field == $this->sortBy) {
            $this->sortAsc = ! $this->sortAsc;
        }
        $this->sortBy = $field;
    }

    public function showDeleteRow(int $id): void
    {
        $this->pendingDeleteRowId = $id;
    }

    public function cancelDeleteRow(): void
    {
        $this->pendingDeleteRowId = null;
    }

    public function confirmItemDeletion($id)
    {
        $this->pendingDeleteRowId = null;
        $this->confirmingItemDeletion = $id;
    }

    public function deleteItem(int $id): void
    {
        $item = Advice::query()->findOrFail($id);

        if ((int) $item->restaurant_id !== (int) $this->restaurantId()) {
            return;
        }

        $item->delete();
        $this->confirmingItemDeletion = false;
        $this->editingItemId = null;
        $this->confirmingItemAdd = false;
        $this->item = null;
        session()->flash('message', __('admin.advice_ui.flash_deleted'));
    }

    public function confirmItemAdd()
    {
        $this->reset(['item']);
        $this->resetErrorBag();
        $this->editingItemId = null;
        $this->item = [
            'title' => '',
            'advice' => '',
            'status' => false,
            'starts_at' => null,
            'ends_at' => null,
        ];
        $this->confirmingItemAdd = true;
    }

    public function confirmItemEdit(int $id): void
    {
        $item = Advice::query()->findOrFail($id);

        if ((int) $item->restaurant_id !== (int) $this->restaurantId()) {
            return;
        }

        $this->resetErrorBag();
        $this->editingItemId = (int) $item->id;
        $this->item = [
            'id' => $item->id,
            'title' => $item->title,
            'advice' => $item->advice,
            'status' => (bool) $item->status,
            'starts_at' => $item->starts_at ? $item->starts_at->format('Y-m-d H:i') : null,
            'ends_at' => $item->ends_at ? $item->ends_at->format('Y-m-d H:i') : null,
            'restaurant_id' => $item->restaurant_id,
        ];
        $this->confirmingItemAdd = true;
    }

    public function saveItem(bool $closeAfterSave = true)
    {
        $this->validate();

        $starts = $this->item['starts_at'] ?? null;
        $ends = $this->item['ends_at'] ?? null;

        if (filled($starts) && filled($ends) && Carbon::parse($ends)->lt(Carbon::parse($starts))) {
            $this->addError('item.ends_at', __('admin.advice_page.validation_end_after_start'));

            return;
        }
        $startsCarbon = filled($starts) ? Carbon::parse($starts) : null;
        $endsCarbon = filled($ends) ? Carbon::parse($ends) : null;

        $payload = [
            'title' => $this->item['title'],
            'advice' => $this->item['advice'],
            'status' => (bool) ($this->item['status'] ?? false),
            'starts_at' => $startsCarbon,
            'ends_at' => $endsCarbon,
        ];

        if (isset($this->item['id'])) {
            if ((int) ($this->item['restaurant_id'] ?? 0) !== (int) $this->restaurantId()) {
                return;
            }

            $advice = Advice::query()
                ->where('id', $this->item['id'])
                ->where('restaurant_id', $this->restaurantId())
                ->firstOrFail();

            $advice->update($payload);
            $this->editingItemId = (int) $advice->id;
            session()->flash('message', __('admin.advice_ui.flash_saved'));
        } else {
            $advice = Advice::create(array_merge($payload, [
                'restaurant_id' => $this->restaurantId(),
            ]));
            $this->editingItemId = (int) $advice->id;
            $this->item['id'] = $advice->id;
            $this->item['restaurant_id'] = $advice->restaurant_id;
            session()->flash('message', __('admin.advice_ui.flash_added'));
        }

        if ($closeAfterSave) {
            $this->confirmingItemAdd = false;
            $this->editingItemId = null;
        }
    }

    public function saveItemAndClose(): void
    {
        $this->saveItem(true);
    }

    public function saveItemKeepOpen(): void
    {
        $this->saveItem(false);
    }

    public function toggleState(int $id): void
    {
        $item = Advice::query()->findOrFail($id);

        if ((int) $item->restaurant_id !== (int) $this->restaurantId()) {
            return;
        }

        $item->status = ! $item->status;
        $item->save();
    }
}
