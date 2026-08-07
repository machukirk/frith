<?php

namespace App\Filament\Widgets;

use App\Models\WaitlistSignup;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WaitlistOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $confirmed = WaitlistSignup::query()->mailable()->count();
        $pending = WaitlistSignup::query()->whereNull('confirmed_at')->whereNull('unsubscribed_at')->count();
        $unsubscribed = WaitlistSignup::query()->whereNotNull('unsubscribed_at')->count();
        $thisWeek = WaitlistSignup::query()->where('created_at', '>=', now()->subWeek())->count();

        return [
            Stat::make('On the list', $confirmed)
                ->description('Confirmed their email')
                ->color('success'),

            Stat::make('Waiting to confirm', $pending)
                ->description($pending > 0 ? 'Signed up but haven’t clicked the link yet' : 'Everyone has confirmed')
                ->color($pending > 0 ? 'warning' : 'gray'),

            Stat::make('New this week', $thisWeek)
                ->description('Signed up in the last seven days')
                ->color('gray'),

            Stat::make('Unsubscribed', $unsubscribed)
                ->description('Asked not to be emailed')
                ->color('gray'),
        ];
    }
}
