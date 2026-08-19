<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FounderOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $total = Registration::query()->count();
        $complete = Registration::query()->completed()->count();
        $partial = $total - $complete;

        // Frith only works where families are close together, so where they
        // cluster matters more than the headline number.
        $topArea = Registration::query()
            ->whereNotNull('postcode_outcode')
            ->selectRaw('postcode_outcode, COUNT(*) as n')
            ->groupBy('postcode_outcode')
            ->orderByDesc('n')
            ->first();

        return [
            Stat::make('Founders', $total)
                ->description('Registered so far')
                ->color('success'),

            Stat::make('Finished section one', $complete)
                ->description($total > 0 ? round($complete / $total * 100).'% of everyone who started' : 'None yet')
                ->color('gray'),

            Stat::make('Stopped part-way', $partial)
                ->description($partial > 0 ? 'Still registered, just fewer answers' : 'Everyone finished')
                ->color($partial > 0 ? 'warning' : 'gray'),

            Stat::make('Busiest area', $topArea?->postcode_outcode ?? '—')
                ->description($topArea ? $topArea->n.' families' : 'No postcodes yet')
                ->color('gray'),
        ];
    }
}
