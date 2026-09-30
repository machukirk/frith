<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The editing form for a page.
 *
 * Built from the page's own content by ContentSchema, so every page gets the
 * editor its own shape describes and no page can be saved through a form built
 * for a different one.
 */
class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        // The whole list is a closure, not one entry in it: the components
        // cannot be built until the record is known, because they are built
        // from it.
        return $schema->components(fn (?Page $record): array => [
            Callout::make('Everything here is live')
                ->description('Visitors see the new wording as soon as you save. The tabs follow the page from top to bottom, so find the bit you want on the page and pick the tab with the same name.')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('info')
                ->columnSpanFull(),

            ContentSchema::tabs($record?->content ?? []),
        ]);
    }
}
