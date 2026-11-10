<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\AdminAccounts;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Password;

/**
 * Adding and editing an admin account.
 *
 * Two roles, and the difference is not cosmetic: an editor cannot see anyone's
 * email address, which is most of why the role exists. So the role field says
 * what each one means rather than offering two words.
 */
class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(fn (?User $record): array => [
            Callout::make('These people can sign in to this panel')
                ->description('There is no public sign-up. An account exists because somebody here made it.')
                ->icon(Heroicon::OutlinedKey)
                ->color('info')
                ->visible(fn (): bool => $record === null || ! $record->is(Filament::auth()->user()))
                ->columnSpanFull(),

            // Editing yourself is the one case where a slip locks you out of
            // the panel you are standing in. There is no password reset and no
            // emailed link for admins, so the way back is a shell on the
            // server. Worth saying before, not after.
            Callout::make('This is your own account')
                ->description('The address and the password here are the ones you sign in with. Change either and you will need the new one next time. There is no reset email for admin accounts.')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('warning')
                ->visible(fn (): bool => $record !== null && $record->is(Filament::auth()->user()))
                ->columnSpanFull(),

            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email address')
                ->email()
                ->required()
                ->maxLength(254)
                ->unique(ignoreRecord: true)
                ->helperText('What they sign in with. It is not shown to any family.'),

            Select::make('role')
                ->label('What can they do?')
                ->options(collect(UserRole::cases())
                    ->mapWithKeys(fn (UserRole $r) => [$r->value => $r->label().'. '.$r->description()])
                    ->all())
                ->default(UserRole::Editor->value)
                ->required()
                ->native(false)
                // Required, and it has a default, so a clear button would only
                // ever put the form into a state it refuses to save.
                ->selectablePlaceholder(false)
                // The last owner cannot be demoted, or there is no way back
                // into the panel. Said on the field rather than refused on save.
                ->disabled(fn (): bool => $record !== null && ! AdminAccounts::mayChangeRole(Filament::auth()->user(), $record))
                ->dehydrated(fn (): bool => $record === null || AdminAccounts::mayChangeRole(Filament::auth()->user(), $record))
                ->helperText(fn (): ?string => $record !== null && AdminAccounts::isLastOwner($record)
                    ? 'This is the only owner, so their role is fixed. Add another owner first.'
                    : null)
                ->columnSpanFull(),

            TextInput::make('password')
                ->label($record === null ? 'Password' : 'New password')
                ->password()
                ->revealable()
                ->autocomplete('new-password')
                // Required when creating, optional when editing: leaving it
                // blank on an edit has to mean "leave it alone", or every
                // change of somebody's name resets their password.
                ->required(fn (): bool => $record === null)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->rule(Password::min(12)->uncompromised())
                ->helperText($record === null
                    ? 'At least twelve characters, and not one that has appeared in a known breach.'
                    : 'Leave this blank to keep their current password.')
                // Typed once and never seen again is how somebody locks
                // themselves out with a typo they cannot look up.
                ->confirmed()
                ->live(onBlur: true)
                ->columnSpanFull(),

            TextInput::make('password_confirmation')
                ->label('Type it again')
                ->password()
                ->revealable()
                ->autocomplete('new-password')
                ->required(fn (?string $state, $get): bool => filled($get('password')))
                ->dehydrated(false)
                ->visible(fn ($get): bool => $record === null || filled($get('password')))
                ->columnSpanFull(),
        ]);
    }
}
