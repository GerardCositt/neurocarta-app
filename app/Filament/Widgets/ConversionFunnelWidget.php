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

        $subsUrl  = SubscriptionResource::getUrl('index');
        $usersUrl = UserResource::getUrl('index');

        $sinVerificarStat = Stat::make('Sin verificar email', $sinVerificar)
            ->icon('heroicon-o-envelope-open')
            ->color('warning');

        if ($sinVerificar > 0) {
            $sinVerificarStat
                ->description('Clic para ver lista')
                ->url($subsUrl . '?tableFilters[sin_verificar][isActive]=1');
        } else {
            $sinVerificarStat->description('No hay registros en este estado');
        }

        $trialingStat = Stat::make('Trial activo', $trialing)
            ->icon('heroicon-o-clock')
            ->color('primary');

        if ($trialing > 0) {
            $trialingStat
                ->description('Clic para ver lista')
                ->url($subsUrl . '?tableFilters[status][value]=trialing');
        } else {
            $trialingStat->description('No hay registros en este estado');
        }

        $expiradosStat = Stat::make('Expirados sin pagar', $expiradosSinPagar)
            ->icon('heroicon-o-x-circle')
            ->color('danger');

        if ($expiradosSinPagar > 0) {
            $expiradosStat
                ->description('Clic para ver lista')
                ->url($subsUrl . '?tableFilters[expirados_sin_pagar][isActive]=1');
        } else {
            $expiradosStat->description('No hay registros en este estado');
        }

        $activosStat = Stat::make('Activos / Pagando', $activos)
            ->icon('heroicon-o-check-circle')
            ->color('success');

        if ($activos > 0) {
            $activosStat
                ->description('Clic para ver lista')
                ->url($subsUrl . '?tableFilters[status][value]=active');
        } else {
            $activosStat->description('No hay registros en este estado');
        }

        return [
            Stat::make('Registrados', $totalUsers)
                ->description($totalUsers > 0 ? 'Clic para ver usuarios' : 'No hay registros aún')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->url($totalUsers > 0 ? $usersUrl : null),

            $sinVerificarStat,
            $trialingStat,
            $expiradosStat,
            $activosStat,

            Stat::make('Tasa de conversión', $tasaConversion . '%')
                ->description('Activos sobre total registrados')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),
        ];
    }
}
