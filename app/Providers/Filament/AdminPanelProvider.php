<?php

namespace App\Providers\Filament;

use App\Support\AdminPalette;
use App\Support\BrandAsset;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
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
            ->brandName('Frith')
            ->brandLogo(BrandAsset::url('brand/logo/frith-logo-horizontal-fullcolour.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(BrandAsset::url('brand/logo/frith-logo-horizontal-fullcolour.svg'))
            // Brand ramps, supplied outright rather than generated. See
            // App\Support\AdminPalette for why.
            ->colors([
                'primary' => AdminPalette::PRIMARY,
                'danger' => AdminPalette::DANGER,
                'success' => AdminPalette::SUCCESS,
                'warning' => AdminPalette::WARNING,
                'info' => AdminPalette::INFO,
                'gray' => Color::Slate,
            ])
            // The guidelines define one palette, for light grounds. Rather than
            // invent a dark one, the panel stays light — which also keeps the
            // dark green wordmark legible against it.
            ->darkMode(false)
            ->maxContentWidth(Width::SevenExtraLarge)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            // No dashboard: with two sections, a landing page of empty widgets
            // is a click in the way rather than an overview.
            ->pages([])
            ->widgets([])
            ->databaseNotifications(false)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
