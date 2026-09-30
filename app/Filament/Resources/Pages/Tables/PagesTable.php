<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use App\Support\SiteNavigation;
use App\Support\SqlOrder;
use Database\Seeders\PageSeeder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Only pages the site actually has. A row left behind by a page
            // that has been removed is not something to offer somebody as
            // editable — they would be writing into nothing. The row is hidden
            // rather than deleted, so the old copy is still there to read.
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('slug', array_keys(PageSeeder::pages())))
            // In the order somebody meets them on the site, not alphabetically.
            ->defaultSort(fn (Builder $query) => SqlOrder::byList($query, 'slug', array_keys(PageSeeder::pages())))
            ->columns([
                TextColumn::make('name')
                    ->label('Page')
                    ->weight('medium')
                    ->description(fn (Page $record) => '/'.ltrim($record->slug === 'home' ? '' : $record->slug, '/'))
                    ->searchable(),

                TextColumn::make('sections')
                    ->label('Sections')
                    ->state(fn (Page $record) => count($record->content ?? []))
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Last changed')
                    ->since()
                    ->sortable(),

                TextColumn::make('updatedBy.name')
                    ->label('By')
                    ->placeholder('Never edited'),
            ])
            ->recordActions([
                EditAction::make()->label('Edit content'),

                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Page $record) => SiteNavigation::url($record->slug), shouldOpenInNewTab: true),
            ])
            ->paginated(false)
            ->emptyStateHeading('No pages yet')
            ->emptyStateDescription('Run: php artisan db:seed --class=PageSeeder');
    }
}
