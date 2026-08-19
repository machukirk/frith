<?php

namespace App\Filament\Resources\Forms\Pages;

use App\Filament\Resources\Forms\FormResource;
use Filament\Resources\Pages\ListRecords;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;

    public function getHeading(): string
    {
        return 'Forms';
    }

    public function getSubheading(): ?string
    {
        return 'The questions people are asked, and the choices they pick from.';
    }
}
