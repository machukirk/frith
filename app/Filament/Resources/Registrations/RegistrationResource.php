<?php

namespace App\Filament\Resources\Registrations;

use App\Filament\Resources\Registrations\Pages\ListRegistrations;
use App\Filament\Resources\Registrations\Schemas\RegistrationInfolist;
use App\Filament\Resources\Registrations\Tables\RegistrationsTable;
use App\Models\Registration;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The Frith Founders, read-only.
 *
 * Owners only, and never editable. What a family said about their own life is
 * theirs; an admin quietly changing it would make the consent record we keep
 * beside it meaningless. Deletion stays, because that is how an erasure
 * request gets honoured.
 */
class RegistrationResource extends Resource
{
    protected static ?string $model = Registration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Founders';

    protected static ?string $modelLabel = 'founder';

    protected static ?string $pluralModelLabel = 'founders';

    protected static ?int $navigationSort = 2;

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

    public static function canDelete(Model $record): bool
    {
        return Filament::auth()->user()?->isOwner() ?? false;
    }

    public static function table(Table $table): Table
    {
        return RegistrationsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RegistrationInfolist::configure($schema);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::canViewAny()
            ? (string) Registration::query()->completed()->count()
            : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Finished section one';
    }

    public static function getPages(): array
    {
        return ['index' => ListRegistrations::route('/')];
    }
}
