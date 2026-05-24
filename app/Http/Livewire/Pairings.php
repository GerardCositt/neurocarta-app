<?php

namespace App\Http\Livewire;

use App\Exceptions\InsufficientAiCreditsException;
use App\Models\Pairing;
use App\Models\Product;
use App\Models\Setting;
use App\Services\AiCreditService;
use App\Services\OpenAiService;
use App\Support\PlanFeatureGate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Pairings extends Component
{
    public $name;

    public $description;

    public $pairing_id;

    /** @var bool Ocultar en carta (columna active en BD). */
    public $hidden = false;

    public $isOpen = 0;

    public $confirmingPairingDeletion = false;

    public $confirmingPairingAiDescription = false;

    /** @var int|null Fila cuyo listado de productos vinculados está desplegado. */
    public $expandedLinkedProductsPairingId = null;

    private function getRestaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    private function openAi(): OpenAiService
    {
        return app(OpenAiService::class);
    }

    private function aiCredits(): AiCreditService
    {
        return app(AiCreditService::class);
    }

    public function render()
    {
        $restaurantId = $this->getRestaurantId();

        $navIds = ($this->isOpen && $this->pairing_id)
            ? Pairing::where('restaurant_id', $restaurantId)->orderBy('name')->orderBy('id')->pluck('id')->toArray()
            : [];
        $navIdx = $navIds ? array_search((int) $this->pairing_id, $navIds, true) : false;

        $query = Pairing::query()->withCount('products');
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        $expandedLinkedProducts = collect();
        if ($this->expandedLinkedProductsPairingId) {
            $expandedPairing = Pairing::query()
                ->when($restaurantId, fn ($q) => $q->where('restaurant_id', $restaurantId))
                ->whereKey($this->expandedLinkedProductsPairingId)
                ->first();

            if ($expandedPairing) {
                $expandedLinkedProducts = Product::query()
                    ->with('category')
                    ->when($restaurantId, fn ($q) => $q->where('restaurant_id', $restaurantId))
                    ->where('pairing_id', $expandedPairing->id)
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('livewire.pairings', [
            'pairings' => $query->orderBy('name')->get(),
            'expandedLinkedProducts' => $expandedLinkedProducts,
            'aiCredits' => $this->aiCredits()->summary(),
            'aiPairingDescriptionCost' => $this->aiCredits()->cost(AiCreditService::ACTION_GENERATE_PAIRING_DESCRIPTION),
            'aiWritingGuideConnected' => $this->hasAiWritingGuide(),
            'canUseAi'                => PlanFeatureGate::allows('ai'),
            'navPosition'             => ($navIdx !== false) ? $navIdx + 1 : null,
            'navTotal'                => count($navIds),
        ]);
    }

    public function toggleLinkedProductsList(int $id): void
    {
        if ($this->expandedLinkedProductsPairingId === $id) {
            $this->expandedLinkedProductsPairingId = null;

            return;
        }

        $this->expandedLinkedProductsPairingId = $id;
    }

    public function closeLinkedProductsList(): void
    {
        $this->expandedLinkedProductsPairingId = null;
    }

    private function guardAiPlan(): bool
    {
        if (PlanFeatureGate::allows('ai')) {
            return true;
        }

        session()->flash('message', __('admin.plan.feature_not_available'));

        return false;
    }

    private function guardAiExecution(): bool
    {
        if (! $this->guardAiPlan()) {
            return false;
        }

        if (! PlanFeatureGate::attemptAiAction()) {
            session()->flash('message', __('admin.plan.ai_rate_limited'));

            return false;
        }

        return true;
    }

    private function hasAiWritingGuide(): bool
    {
        $restaurantId = $this->getRestaurantId();
        foreach (['ai_writing_guide', 'openai_writing_guide', 'writing_guide', 'brand_guide', 'style_guide'] as $key) {
            if (trim((string) Setting::get($key, '', $restaurantId)) !== '') {
                return true;
            }
            if (trim((string) Setting::get($key, '', null)) !== '') {
                return true;
            }
        }

        return false;
    }

    public function toggleState(int $id): void
    {
        $pairing = Pairing::findOrFail($id);
        $restaurantId = $this->getRestaurantId();
        if ($restaurantId && (int) $pairing->restaurant_id !== (int) $restaurantId) {
            return;
        }
        $pairing->hidden = ! $pairing->hidden;
        $pairing->save();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->description = '';
        $this->pairing_id = '';
        $this->hidden = false;
    }

    public function confirmGeneratePairingDescriptionWithAi(): void
    {
        if (! $this->guardAiPlan()) {
            return;
        }

        $this->confirmingPairingAiDescription = true;
    }

    public function cancelPairingAiDescription(): void
    {
        $this->confirmingPairingAiDescription = false;
    }

    public function confirmPairingAiDescription(): void
    {
        if (! $this->guardAiExecution()) {
            $this->confirmingPairingAiDescription = false;

            return;
        }

        $this->confirmingPairingAiDescription = false;
        $this->generatePairingDescriptionWithAi();
    }

    public function generatePairingDescriptionWithAi(): void
    {
        if (! $this->guardAiExecution()) {
            return;
        }

        try {
            if (! $this->openAi()->isConfigured()) {
                session()->flash('message', __('admin.pairing_page.flash_ai_no_openai'));

                return;
            }

            if (trim((string) $this->name) === '') {
                session()->flash('message', __('admin.pairing_page.flash_ai_no_name'));

                return;
            }

            $this->aiCredits()->ensureCanAfford(AiCreditService::ACTION_GENERATE_PAIRING_DESCRIPTION);

            $this->description = $this->openAi()->generatePairingDescription([
                'name' => $this->name,
                'existing_description' => $this->description,
            ]);

            $this->aiCredits()->spend(
                AiCreditService::ACTION_GENERATE_PAIRING_DESCRIPTION,
                1,
                ['pairing_name' => $this->name],
                null
            );
            $this->dispatch('aiCreditsUpdated');

            session()->flash('message', __('admin.pairing_page.flash_ai_generated'));
        } catch (InsufficientAiCreditsException $e) {
            session()->flash('message', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('No se pudo generar la descripcion de maridaje con IA', [
                'message' => $e->getMessage(),
            ]);
            session()->flash('message', __('admin.pairing_page.flash_ai_error'));
        }
    }

    public function store()
    {
        $this->persistPairing();
    }

    public function storeAndClose()
    {
        $this->persistPairing();
        $this->closeModal();
        $this->resetInputFields();
    }

    public function saveAndNext(): void
    {
        $this->persistPairing();
        $next = $this->adjacentPairingId(1);
        if ($next) $this->edit($next);
    }

    public function saveAndPrev(): void
    {
        $this->persistPairing();
        $prev = $this->adjacentPairingId(-1);
        if ($prev) $this->edit($prev);
    }

    private function adjacentPairingId(int $dir): ?int
    {
        $rid = $this->getRestaurantId();
        $ids = \App\Models\Pairing::where('restaurant_id', $rid)
            ->orderBy('name')->orderBy('id')->pluck('id')->toArray();
        $pos = array_search((int) $this->pairing_id, $ids, true);
        if ($pos === false) return null;
        return $ids[$pos + $dir] ?? null;
    }

    private function persistPairing(): void
    {
        $this->validate([
            'name' => 'required',
            'description' => 'required',
        ]);

        $restaurantId = $this->getRestaurantId();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'restaurant_id' => $restaurantId,
            'hidden' => (bool) $this->hidden,
        ];

        $wasNew = ! $this->pairing_id;

        if ($this->pairing_id) {
            $pairing = Pairing::query()
                ->where('id', $this->pairing_id)
                ->when($restaurantId, fn ($q) => $q->where('restaurant_id', $restaurantId))
                ->firstOrFail();
            $pairing->update($data);
        } else {
            $pairing = Pairing::create($data);
            $this->pairing_id = $pairing->id;
        }

        session()->flash('message',
            $wasNew ? __('admin.pairing_ui.flash_saved_create') : __('admin.pairing_ui.flash_saved_update'));
    }

    public function edit($id)
    {
        $pairing = Pairing::findOrFail($id);
        $this->pairing_id = $id;
        $this->name = $pairing->name;
        $this->description = $pairing->description;
        $this->hidden = (bool) $pairing->hidden;
        $this->openModal();
    }

    public function confirmDeleteCurrentPairing(): void
    {
        if (! $this->pairing_id) {
            return;
        }
        $this->confirmingPairingDeletion = (int) $this->pairing_id;
    }

    public function deletePairingConfirmed(): void
    {
        $id = $this->confirmingPairingDeletion;
        $this->confirmingPairingDeletion = false;
        if (! $id) {
            return;
        }
        Pairing::find($id)?->delete();
        session()->flash('message', __('admin.pairing_ui.flash_deleted'));
        $this->closeModal();
        $this->resetInputFields();
    }
}
