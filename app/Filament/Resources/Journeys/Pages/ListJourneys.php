<?php

namespace App\Filament\Resources\Journeys\Pages;

use App\Filament\Resources\Journeys\JourneyResource;
use Filament\Resources\Pages\ListRecords;

class ListJourneys extends ListRecords
{
    protected static string $resource = JourneyResource::class;

    public function getHeading(): string
    {
        return 'Journeys';
    }

    public function getSubheading(): ?string
    {
        return 'The groups families join. Drag to reorder: this is the order they appear in on the home page and in the directory.';
    }
}
