<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SubscriptionResource;
use App\Filament\Resources\UserResource;
use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConversionFunnelWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers        = User::count();
        $sinVerificar      = User::whereNull('email_verified_at')->count();
        $trialing          = Subscription::where('status', 'trialing')->count();
        $expiradosSinPagar = Subscription::where('status', 'inactive')->count();
        $activos           = Subscription::where('status', 'active')->count();

        $tasaConversion = $totalUsers > 0
            ? round(($activos / $totalUsers) * 100, 1)
            : 0;

        $subsUrl       = SubscriptionResource::getUrl('index');
        $usersUrl      = UserResource::getUrl('index');

        return [
            Stat::make('Registrados', $totalUsers)
                ->description('Total usuarios creados')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->url($usersUrl),

            Stat::make('Sin verificar email', $sinVerificar)
                ->description('Link caducado o no clicado — clic para ver lista')
                ->icon('heroicon-o-envelope-open')
                ->color('warning')
                ->url($subsUrl . '?tableFilters[sin_verificar][isActive]=1'),

            Stat::make('Trial activo', $trialing)
                ->description('Verificados, en periodo de prueba')
                ->icon('heroicon-o-clock')
                ->color('primary')
                ->url($subsUrl . '?tableFilters[status][value]=trialing'),

            Stat::make('Expirados sin pagar', $expiradosSinPagar)
                ->description('Trial acabó, no contrataron — clic para ver lista')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->url($subsUrl . '?tableFilters[expirados_sin_pagar][isActive]=1'),

            Stat::make('Activos / Pagando', $activos)
                ->description('Suscripción vigente')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url($subsUrl . '?tableFilters[status][value]=active'),

            Stat::make('Tasa de conversión', $tasaConversion . '%')
                ->description('Activos sobre total registrados')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),
        ];
    }
}
