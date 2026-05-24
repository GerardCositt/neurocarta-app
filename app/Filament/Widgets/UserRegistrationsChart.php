<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class UserRegistrationsChart extends ChartWidget
{
    protected static ?string $heading = 'Registros por mes';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $rows = User::select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $labels[] = now()->subMonths($i)->translatedFormat('M Y');
            $values[] = $rows[$key] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label'       => 'Nuevos usuarios',
                    'data'        => $values,
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245,158,11,0.1)',
                    'fill'        => true,
                    'tension'     => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
