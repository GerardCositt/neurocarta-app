<?php

namespace App\Http\Livewire\Admin;

use App\Models\AiUsageLog;
use App\Models\Setting;
use App\Services\AiCreditService;
use Livewire\Component;

class AiBillingSettings extends Component
{
    public string $openAiApiKey = '';
    public string $deepLApiKey = '';

    private function aiCredits(): AiCreditService
    {
        return app(AiCreditService::class);
    }

    private function restaurantId(): ?int
    {
        return session('admin_restaurant_id');
    }

    public function mount(): void
    {
        $restaurantId = $this->restaurantId();
        $this->openAiApiKey = (string) Setting::get('openai_api_key', '', $restaurantId);
        $this->deepLApiKey = (string) Setting::get('deepl_api_key', '', $restaurantId);
    }

    public function saveOpenAiApiKey(): void
    {
        $this->validate([
            'openAiApiKey' => 'nullable|string|min:10',
        ], [
            'openAiApiKey.min' => __('validation.api_key.min', ['min' => 10]),
        ]);

        Setting::put('openai_api_key', trim($this->openAiApiKey), $this->restaurantId());
        session()->flash('message', 'API key de OpenAI guardada.');
    }

    public function saveDeepLApiKey(): void
    {
        $this->validate([
            'deepLApiKey' => 'nullable|string|min:10',
        ], [
            'deepLApiKey.min' => __('validation.api_key.min', ['min' => 10]),
        ]);

        Setting::put('deepl_api_key', trim($this->deepLApiKey), $this->restaurantId());
        session()->flash('message', 'API key de traducción guardada.');
    }

    public function buyCredits(): void
    {
        session()->flash('message', 'La compra de créditos se conectará aquí con Stripe.');
    }

    private function estimatedCreditsForLog(AiUsageLog $log): int
    {
        if ((int) $log->credits > 0) {
            return (int) $log->credits;
        }

        $units = (int) data_get($log->meta, 'units', 0);
        if ($units <= 0) {
            $units = 1;
        }

        return $this->aiCredits()->cost($log->action, $units);
    }

    public function render()
    {
        $priceTariff = [
            [
                'label' => 'Generar imagen',
                'credits' => $this->aiCredits()->cost(AiCreditService::ACTION_GENERATE_PRODUCT_IMAGE),
            ],
            [
                'label' => 'Arreglar imagen',
                'credits' => $this->aiCredits()->cost(AiCreditService::ACTION_IMPROVE_PRODUCT_IMAGE),
            ],
            [
                'label' => 'Generar descripción',
                'credits' => $this->aiCredits()->cost(AiCreditService::ACTION_GENERATE_PRODUCT_DESCRIPTION),
            ],
            [
                'label' => 'Texto alérgenos',
                'credits' => $this->aiCredits()->cost(AiCreditService::ACTION_GENERATE_PRODUCT_ALLERGEN_TEXT),
            ],
            [
                'label' => 'Importar carta',
                'credits' => $this->aiCredits()->cost(AiCreditService::ACTION_IMPORT_MENU),
            ],
            [
                'label' => 'Imagen en importación',
                'credits' => $this->aiCredits()->cost(AiCreditService::ACTION_IMPORT_MENU_PRODUCT_IMAGE),
            ],
        ];

        $priceTariff = array_map(function (array $item): array {
            $item['euros'] = $this->aiCredits()->formatEurosFromCredits((int) $item['credits']);

            return $item;
        }, $priceTariff);

        $creditPackages = [
            ['label' => 'Recarga básica', 'credits' => 100, 'euros' => '5,00 €'],
            ['label' => 'Recarga media', 'credits' => 250, 'euros' => '10,00 €'],
            ['label' => 'Recarga amplia', 'credits' => 700, 'euros' => '25,00 €'],
        ];

        $logs = AiUsageLog::query()
            ->where('restaurant_id', $this->restaurantId())
            ->latest()
            ->limit(30)
            ->get();

        $logs->each(function (AiUsageLog $log): void {
            $log->display_credits = $this->estimatedCreditsForLog($log);
            $log->display_euros = $this->aiCredits()->formatEurosFromCredits((int) $log->display_credits);
        });

        $displayCreditsUsed = $logs->sum(fn (AiUsageLog $log) => $this->estimatedCreditsForLog($log));
        $displayEurosUsed = $this->aiCredits()->formatEurosFromCredits($displayCreditsUsed);

        return view('livewire.admin.ai-billing-settings', [
            'aiCredits' => $this->aiCredits()->summary(),
            'creditPackages' => $creditPackages,
            'priceTariff' => $priceTariff,
            'usageLogs' => $logs,
            'displayCreditsUsed' => $displayCreditsUsed,
            'displayEurosUsed' => $displayEurosUsed,
        ]);
    }
}
