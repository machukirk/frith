<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\AdminAccounts;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    // Said on the row, because "why can I not remove this
                    // one?" is the first question the screen raises.
                    ->description(fn (User $record): ?string => match (true) {
                        $record->is(Filament::auth()->user()) => 'This is you',
                        AdminAccounts::isLastOwner($record) => 'The only owner',
                        default => null,
                    }),

                TextColumn::make('email')
                    ->label('Signs in with')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('role')
                    ->label('Can do')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => $state === UserRole::Owner ? 'warning' : 'gray')
                    ->description(fn (UserRole $state): string => $state->description()),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => AdminAccounts::mayDelete(Filament::auth()->user(), $record))
                    // Re-checked when pressed, not only when drawn. A page
                    // left open while another owner was removed would
                    // otherwise offer a control that is no longer safe.
                    ->before(function (DeleteAction $action, User $record): void {
                        if (AdminAccounts::mayDelete(Filament::auth()->user(), $record)) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Not removed')
                            ->body(AdminAccounts::whyLocked(Filament::auth()->user(), $record))
                            ->send();

                        $action->halt();
                    })
                    ->modalDescription(fn (User $record): string => $record->name
                        .' will not be able to sign in again. Nothing they wrote is removed.'),
            ])
            // No bulk delete. The two things this screen must never allow are
            // removing the last owner and removing yourself, and a checkbox
            // column is the easiest way to do both by accident.
            ->toolbarActions([])
            ->emptyStateHeading('No admin accounts')
            ->emptyStateDescription('That should not be possible while you are reading this.');
    }
}
