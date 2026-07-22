<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\ExecutiveDecisionSupportWidget;
use App\Filament\Widgets\Kpi\KpiHomeMrrTrendChart;
use App\Filament\Widgets\Kpi\KpiHomeOverviewWidget;
use App\Filament\Widgets\SaaSCountryAnalyticsWidget;
use App\Filament\Widgets\SystemOverviewWidget;
use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('hasbni-super-secure-panel-99')
            ->login()
            ->font('Cairo')
            ->maxContentWidth('full')
            ->colors([
                'primary' => Color::Teal,
            ])
            ->navigationGroups([
                NavigationGroup::make(__('kpi.navigation.group'))
                    ->icon('heroicon-o-presentation-chart-line'),
                NavigationGroup::make(__('SaaS Management')),
                NavigationGroup::make(__('Billing & Revenue')),
                NavigationGroup::make(__('Support & Help')),
                NavigationGroup::make(__('System Settings')),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                KpiHomeOverviewWidget::class,
                KpiHomeMrrTrendChart::class,
                ExecutiveDecisionSupportWidget::class,
                SaaSCountryAnalyticsWidget::class,
                SystemOverviewWidget::class,
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
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render('@include("filament.language-switch")'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => Blade::render('@vite("resources/css/app.css")'),
            );
    }
}
