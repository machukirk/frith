<?php

namespace App\Filament\Resources\Journeys\Pages;

use App\Filament\Resources\Journeys\JourneyResource;
use Filament\Resources\Pages\EditRecord;

class EditJourney extends EditRecord
{
    protected static string $resource = JourneyResource::class;

    public function getHeading(): string
    {
        return $this->record->title;
    }

    public function getSubheading(): ?string
    {
        $count = $this->record->members()->count();

        return $count === 0
            ? 'Nobody has joined this one yet.'
            : $count.' '.str('family')->plural($count).' '.($count === 1 ? 'is' : 'are').' in it.';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
