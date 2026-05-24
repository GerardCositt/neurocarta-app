<?php

namespace App\Filament\Widgets;

use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $activeSubscriptions = Subscription::whereIn('status', ['active', 'trialing'])->count();
        $totalUsers = User::count();
        $totalRestaurants = Restaurant::count();

        return [
            Stat::make('Usuarios', $totalUsers)
                ->description('Total registrados')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Restaurantes', $totalRestaurants)
                ->description('Total activos')
                ->icon('heroicon-o-building-storefront')
                ->color('success'),

            Stat::make('Suscripciones activas', $activeSubscriptions)
                ->description('Active + Trialing')
                ->icon('heroicon-o-credit-card')
                ->color('warning'),
        ];
    }
}
