<?php

namespace App\Http\Livewire\Admin;

use App\Services\AiCreditService;
use Livewire\Component;

class AiCreditsBanner extends Component
{
    protected $listeners = ['aiCreditsUpdated' => '$refresh'];

    public function render()
    {
        $svc = app(AiCreditService::class);

        return view('livewire.admin.ai-credits-banner', [
            'aiCredits' => $svc->summary(),
            'aiGenerateCost' => $svc->cost(AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE),
            'aiImproveCost' => $svc->cost(AiCreditService::ACTION_IMPROVE_PRODUCT_IMAGE),
            'aiBulkGenerateCost' => $svc->cost(AiCreditService::ACTION_BULK_GENERATE_PRODUCT_IMAGES),
        ]);
    }
}
