<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Support\AdminAccounts;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Who can sign in to this panel.
 *
 * Owners only, because an editor who could reach this screen could make
 * themselves an owner, and an owner can see every family's email address. That
 * is the one piece of access the editor role exists to withhold.
 *
 * The rules about what would lock everybody out live in App\Support\
 * AdminAccounts rather than here. A screen is an awkward place to test them,
 * and they are the part that matters.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Admin accounts';

    protected static ?string $modelLabel = 'admin account';

    protected static ?string $pluralModelLabel = 'admin accounts';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return AdminAccounts::mayManage(Filament::auth()->user());
    }

    public static function canCreate(): bool
    {
        return AdminAccounts::mayManage(Filament::auth()->user());
    }

    public static function canEdit(Model $record): bool
    {
        return AdminAccounts::mayManage(Filament::auth()->user());
    }

    public static function canDelete(Model $record): bool
    {
        return AdminAccounts::mayDelete(Filament::auth()->user(), $record);
    }

    /** Never, for anybody. See UsersTable on why there is no bulk action. */
    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
