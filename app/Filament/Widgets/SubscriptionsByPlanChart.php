<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SubscriptionsByPlanChart extends ChartWidget
{
    protected static ?string $heading = 'Suscripciones por plan';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Subscription::select('plan_code', DB::raw('count(*) as total'))
            ->whereIn('status', ['active', 'trialing'])
            ->groupBy('plan_code')
            ->pluck('total', 'plan_code')
            ->toArray();

        $plans  = ['trial', 'basico', 'pro', 'premium'];
        $labels = ['Trial', 'Básico', 'Pro', 'Premium'];
        $colors = ['#F59E0B', '#3B82F6', '#10B981', '#8B5CF6'];
        $values = array_map(fn ($p) => $data[$p] ?? 0, $plans);

        return [
            'datasets' => [
                [
                    'label'           => 'Suscripciones activas',
                    'data'            => $values,
                    'backgroundColor' => $colors,
                    'borderColor'     => $colors,
                    'borderWidth'     => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
