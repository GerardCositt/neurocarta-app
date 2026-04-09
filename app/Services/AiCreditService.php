<?php

namespace App\Services;

use App\Exceptions\InsufficientAiCreditsException;
use App\Models\AiUsageLog;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class AiCreditService
{
    public const EUR_CENTS_PER_CREDIT = 5;

    public const ACTION_GENERATE_PRODUCT_IMAGE = 'generate_product_image';
    public const ACTION_IMPROVE_PRODUCT_IMAGE = 'improve_product_image';
    public const ACTION_IMPORT_MENU = 'import_menu';
    public const ACTION_IMPORT_MENU_PRODUCT_IMAGE = 'import_menu_product_image';
    public const ACTION_BULK_GENERATE_PRODUCT_IMAGES = 'bulk_generate_product_images';
    public const ACTION_GENERATE_PRODUCT_DESCRIPTION = 'generate_product_description';
    public const ACTION_GENERATE_PRODUCT_ALLERGEN_TEXT = 'generate_product_allergen_text';
    public const ACTION_GENERATE_PAIRING_DESCRIPTION = 'generate_pairing_description';

    private const COSTS = [
        self::ACTION_GENERATE_PRODUCT_IMAGE => 10,
        self::ACTION_IMPROVE_PRODUCT_IMAGE => 5,
        self::ACTION_IMPORT_MENU => 15,
        self::ACTION_IMPORT_MENU_PRODUCT_IMAGE => 10,
        self::ACTION_BULK_GENERATE_PRODUCT_IMAGES => 10,
        self::ACTION_GENERATE_PRODUCT_DESCRIPTION => 3,
        self::ACTION_GENERATE_PRODUCT_ALLERGEN_TEXT => 2,
        self::ACTION_GENERATE_PAIRING_DESCRIPTION => 3,
    ];

    public const BILLING_MODE_PLATFORM = 'platform';
    public const BILLING_MODE_CLIENT_KEY = 'client_key';
    public const BILLING_MODE_NONE = 'none';

    public function currentRestaurant(): ?Restaurant
    {
        $restaurantId = session('admin_restaurant_id');
        if (! $restaurantId) {
            return null;
        }

        return Restaurant::find($restaurantId);
    }

    public function summary(): array
    {
        $restaurant = $this->currentRestaurant();
        $isDemo = $restaurant ? $this->isDemoUnlimited($restaurant) : app()->environment('demo');
        $credits = (int) ($restaurant?->ai_credits ?? 0);
        $billingMode = $this->billingMode();

        if ($billingMode === self::BILLING_MODE_CLIENT_KEY) {
            $label = 'Tu API key';
        } elseif ($isDemo) {
            $label = 'Ilimitado (demo)';
        } else {
            $label = number_format($credits, 0, ',', '.') . ' créditos';
        }

        $chargesPlatform = $this->chargesPlatformCredits();

        return [
            'credits' => $credits,
            'is_demo_unlimited' => $isDemo,
            'billing_mode' => $billingMode,
            'uses_client_key' => $billingMode === self::BILLING_MODE_CLIENT_KEY,
            'charges_platform_credits' => $chargesPlatform,
            'needs_credit_topup' => $chargesPlatform && $credits <= 0,
            'label' => $label,
        ];
    }

    public function cost(string $action, int $units = 1): int
    {
        $base = self::COSTS[$action] ?? 0;

        return max(0, $base * max(1, $units));
    }

    public function euroCentsForCredits(int $credits): int
    {
        return max(0, $credits) * self::EUR_CENTS_PER_CREDIT;
    }

    public function formatEurosFromCredits(int $credits): string
    {
        return number_format($this->euroCentsForCredits($credits) / 100, 2, ',', '.') . ' €';
    }

    public function ensureCanAfford(string $action, int $units = 1): void
    {
        $restaurant = $this->currentRestaurant();
        if (! $restaurant) {
            throw new RuntimeException('No se encontró el restaurante activo.');
        }

        if (! $this->chargesPlatformCredits()) {
            return;
        }

        $cost = $this->cost($action, $units);
        $currentCredits = (int) $restaurant->ai_credits;
        if ($currentCredits < $cost) {
            throw new InsufficientAiCreditsException(
                'Créditos IA insuficientes. Tienes '
                . number_format($currentCredits, 0, ',', '.')
                . ' y necesitas '
                . number_format($cost, 0, ',', '.')
                . ' para hacer esta acción. Recarga créditos para continuar.'
            );
        }
    }

    public function spend(string $action, int $units = 1, array $meta = [], ?int $productId = null): void
    {
        $restaurant = $this->currentRestaurant();
        if (! $restaurant) {
            return;
        }

        $cost = $this->cost($action, $units);
        if ($this->chargesPlatformCredits()) {
            $restaurant->decrement('ai_credits', $cost);
            $restaurant->refresh();
        }

        $status = 'completed';
        if ($this->isDemoUnlimited($restaurant)) {
            $status = 'demo';
        } elseif ($this->billingMode() === self::BILLING_MODE_CLIENT_KEY) {
            $status = 'client_key';
        }

        AiUsageLog::create([
            'restaurant_id' => $restaurant->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'credits' => $this->chargesPlatformCredits() ? $cost : 0,
            'status' => $status,
            'product_id' => $productId,
            'meta' => $meta,
        ]);
    }

    public function billingMode(): string
    {
        $openAi = app(OpenAiService::class);
        if ($openAi->usesClientKey()) {
            return self::BILLING_MODE_CLIENT_KEY;
        }

        if ($openAi->isConfigured()) {
            return self::BILLING_MODE_PLATFORM;
        }

        return self::BILLING_MODE_NONE;
    }

    public function chargesPlatformCredits(): bool
    {
        $restaurant = $this->currentRestaurant();
        if (! $restaurant) {
            return false;
        }

        if ($this->isDemoUnlimited($restaurant)) {
            return false;
        }

        return $this->billingMode() === self::BILLING_MODE_PLATFORM;
    }

    private function isDemoUnlimited(Restaurant $restaurant): bool
    {
        return (bool) $restaurant->ai_demo_unlimited || app()->environment('demo');
    }
}
