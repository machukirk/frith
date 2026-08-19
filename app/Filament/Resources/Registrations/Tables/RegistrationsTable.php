<?php

namespace App\Filament\Resources\Registrations\Tables;

use App\Models\Registration;
use App\Support\Taxonomy;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('founder_number')
                    ->label('#')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? '#'.$state : '—'),

                TextColumn::make('first_name')
                    ->label('Name')
                    ->formatStateUsing(fn (Registration $r) => $r->fullName())
                    ->searchable(['first_name', 'last_name'])
                    ->weight('medium'),

                TextColumn::make('email')->searchable()->copyable()->toggleable(),

                TextColumn::make('postcode_outcode')
                    ->label('Area')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('children_count')
                    ->label('Children')
                    ->counts('children')
                    ->alignCenter(),

                TextColumn::make('progress')
                    ->label('Progress')
                    ->badge()
                    ->state(fn (Registration $r) => $r->isComplete()
                        ? 'Complete'
                        : 'Step '.$r->furthest_step.' of 5')
                    ->color(fn (string $state) => $state === 'Complete' ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('j M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('founder_number')
            ->filters([
                SelectFilter::make('state')
                    ->label('Progress')
                    ->options([
                        'complete' => 'Finished section one',
                        'partial' => 'Dropped off part-way',
                    ])
                    ->query(fn (Builder $q, array $data) => match ($data['value'] ?? null) {
                        'complete' => $q->completed(),
                        // The people worth an email: they started and stopped.
                        'partial' => $q->whereNull('completed_at'),
                        default => $q,
                    }),

                SelectFilter::make('support_area')
                    ->label('Support area')
                    ->options(fn () => collect(Taxonomy::categories())->map(fn ($c) => $c['label'])->all())
                    ->query(fn (Builder $q, array $data) => filled($data['value'] ?? null)
                        ? $q->whereJsonContains('support_areas', $data['value'])
                        : $q),

                Filter::make('has_children')
                    ->label('Told us about their children')
                    ->query(fn (Builder $q) => $q->has('children')),
            ])
            ->recordActions([
                ViewAction::make()->modalHeading('Founder'),
                DeleteAction::make()
                    ->modalHeading('Delete this registration?')
                    ->modalDescription('Use this to honour a request to be erased. It removes their answers, their children and the record of consent, and cannot be undone.'),
            ])
            ->headerActions([ExportRegistrationsAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->emptyStateHeading('No Founders yet')
            ->emptyStateDescription('Registrations appear here as soon as somebody finishes the first screen.');
    }
}
