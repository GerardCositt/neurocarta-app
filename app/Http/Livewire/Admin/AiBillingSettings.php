<?php

namespace App\Http\Livewire\Admin;

use App\Models\AiUsageLog;
use App\Models\Setting;
use App\Services\AiCreditService;
use App\Support\PlanFeatureGate;
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

    public function buyCredits(string $packageKey): void
    {
        if (! in_array($packageKey, ['starter', 'pro', 'max'], true)) {
            return;
        }

        $this->redirect(route('checkout.credits.get', ['package' => $packageKey]));
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
            ['key' => 'starter', 'label' => 'Pack Starter',  'credits' => 300,   'euros' => '5,00 €',  'price_eur_cents' => 500],
            ['key' => 'pro',     'label' => 'Pack Pro',      'credits' => 1000,  'euros' => '15,00 €', 'price_eur_cents' => 1500],
            ['key' => 'max',     'label' => 'Pack Max',      'credits' => 3000,  'euros' => '39,00 €', 'price_eur_cents' => 3900],
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
            'aiCredits'          => $this->aiCredits()->summary(),
            'creditPackages'     => $creditPackages,
            'priceTariff'        => $priceTariff,
            'usageLogs'          => $logs,
            'displayCreditsUsed' => $displayCreditsUsed,
            'displayEurosUsed'   => $displayEurosUsed,
            'canBuyCredits'      => PlanFeatureGate::allows('ai'),
        ]);
    }
}
