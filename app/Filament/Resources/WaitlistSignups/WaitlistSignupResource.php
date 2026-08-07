<?php

namespace App\Filament\Resources\WaitlistSignups;

use App\Filament\Resources\WaitlistSignups\Pages\ListWaitlistSignups;
use App\Filament\Resources\WaitlistSignups\Schemas\WaitlistSignupInfolist;
use App\Filament\Resources\WaitlistSignups\Tables\WaitlistSignupsTable;
use App\Models\WaitlistSignup;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The waiting list, read-only.
 *
 * Addresses can be looked at and deleted, never edited. Whether someone is on
 * the list is a fact about them that they gave us; quietly changing it would
 * make the consent record we keep meaningless.
 */
class WaitlistSignupResource extends Resource
{
    protected static ?string $model = WaitlistSignup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Waiting list';

    protected static ?string $modelLabel = 'signup';

    protected static ?string $pluralModelLabel = 'waiting list';

    protected static ?int $navigationSort = 2;

    /**
     * Owners only. A content editor has no reason to see the email addresses of
     * families who have signed up, and the least surprising way to guarantee
     * that is for the section not to exist for them.
     */
    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->isOwner() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /** Deletion stays available: it is how a request to be erased gets honoured. */
    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()?->isOwner() ?? false;
    }

    public static function table(Table $table): Table
    {
        return WaitlistSignupsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WaitlistSignupInfolist::configure($schema);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) {
            return null;
        }

        return (string) WaitlistSignup::query()->mailable()->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Confirmed and still subscribed';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWaitlistSignups::route('/'),
        ];
    }
}
