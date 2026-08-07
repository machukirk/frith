<?php

namespace App\Filament\Resources\Pages\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Page')
                    ->weight('medium')
                    ->searchable(),

                TextColumn::make('updated_at')
                    ->label('Last changed')
                    ->since()
                    ->sortable(),

                TextColumn::make('updatedBy.name')
                    ->label('By')
                    ->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make()->label('Edit content'),
            ])
            ->paginated(false);
    }
}
