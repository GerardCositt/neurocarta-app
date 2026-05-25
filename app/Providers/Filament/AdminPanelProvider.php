<?php

namespace App\Providers\Filament;

use App\Filament\Resources\RestaurantResource;
use App\Filament\Resources\SubscriptionResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\ConversionFunnelWidget;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SubscriptionsByPlanChart;
use App\Filament\Widgets\UserRegistrationsChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('NeuroCarta.ai')
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->brandLogoHeight('2rem')
            ->favicon('/favicon/favicon.ico?v=20260420')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                UserResource::class,
                SubscriptionResource::class,
                RestaurantResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                StatsOverview::class,
                SubscriptionsByPlanChart::class,
                UserRegistrationsChart::class,
                ConversionFunnelWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
