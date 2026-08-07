<?php

namespace App\Filament\Resources\WaitlistSignups\Pages;

use App\Filament\Resources\WaitlistSignups\WaitlistSignupResource;
use App\Filament\Widgets\WaitlistOverview;
use Filament\Resources\Pages\ListRecords;

class ListWaitlistSignups extends ListRecords
{
    protected static string $resource = WaitlistSignupResource::class;

    public function getHeading(): string
    {
        return 'Waiting list';
    }

    public function getSubheading(): ?string
    {
        return 'People who asked to be told when Frith launches.';
    }

    protected function getHeaderWidgets(): array
    {
        return [WaitlistOverview::class];
    }
}
