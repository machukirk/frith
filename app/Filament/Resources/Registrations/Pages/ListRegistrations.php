<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Filament\Resources\Registrations\RegistrationResource;
use App\Filament\Widgets\FounderOverview;
use Filament\Resources\Pages\ListRecords;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    public function getHeading(): string
    {
        return 'Frith Founders';
    }

    public function getSubheading(): ?string
    {
        return 'Families who registered before launch.';
    }

    protected function getHeaderWidgets(): array
    {
        return [FounderOverview::class];
    }
}
