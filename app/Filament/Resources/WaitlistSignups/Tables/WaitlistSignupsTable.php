<?php

namespace App\Filament\Resources\WaitlistSignups\Tables;

use App\Models\WaitlistSignup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WaitlistSignupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->weight('medium'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (WaitlistSignup $r) => match (true) {
                        $r->hasUnsubscribed() => 'Unsubscribed',
                        $r->isConfirmed() => 'Confirmed',
                        default => 'Waiting to confirm',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Confirmed' => 'success',
                        'Unsubscribed' => 'gray',
                        default => 'warning',
                    }),

                TextColumn::make('created_at')
                    ->label('Signed up')
                    ->dateTime('j M Y, H:i')
                    ->sortable(),

                TextColumn::make('confirmed_at')
                    ->label('Confirmed')
                    ->dateTime('j M Y, H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('source')
                    ->label('Came from')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'confirmed' => 'Confirmed',
                        'pending' => 'Waiting to confirm',
                        'unsubscribed' => 'Unsubscribed',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'confirmed' => $query->mailable(),
                        'pending' => $query->whereNull('confirmed_at')->whereNull('unsubscribed_at'),
                        'unsubscribed' => $query->whereNotNull('unsubscribed_at'),
                        default => $query,
                    }),

                Filter::make('stale')
                    ->label('Never confirmed, over a week old')
                    ->query(fn (Builder $query) => $query
                        ->whereNull('confirmed_at')
                        ->whereNull('unsubscribed_at')
                        ->where('created_at', '<', now()->subWeek())),
            ])
            ->headerActions([
                ExportWaitlistAction::make(),
            ])
            ->recordActions([
                ViewAction::make()->modalHeading('Signup details'),
                DeleteAction::make()
                    ->label('Delete')
                    ->modalHeading('Delete this signup?')
                    ->modalDescription('Use this to honour a request to be erased. It removes the address and the record of consent, and cannot be undone.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No signups yet')
            ->emptyStateDescription('Addresses appear here as soon as someone uses the form on the coming soon page.');
    }
}
