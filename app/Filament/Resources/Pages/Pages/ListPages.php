<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    public function getHeading(): string
    {
        return 'Website content';
    }

    public function getSubheading(): ?string
    {
        return 'Choose a page to change its words and pictures.';
    }
}
