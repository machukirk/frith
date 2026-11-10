<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Exceptions\LastOwnerStands;
use App\Support\AdminAccounts;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * There is no public registration. Accounts are created with
     * `php artisan frith:admin`, so anyone with one belongs in the panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    /**
     * The panel must always have an owner, and this is where that is true.
     *
     * The Users screen hides the controls that would break it and says why,
     * which is the right place to explain it but the wrong place to enforce
     * it. A screen only governs the paths it knows about: it says nothing
     * about tinker, a console command, a relation manager, a bulk action
     * somebody adds next year, or two owners pressing at the same moment.
     *
     * Here it holds for every path, and the count is locked, so the second of
     * two simultaneous requests waits and then sees that it would be the one
     * to empty the panel.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $user): void {
            if ($user->isOwner() && AdminAccounts::ownerCountForUpdate() <= 1) {
                throw LastOwnerStands::deleted();
            }
        });

        static::saving(function (self $user): void {
            // getOriginal returns the cast value, so this is the enum rather
            // than the string it is stored as. Comparing it to ->value is the
            // kind of thing that passes review and silently never fires.
            $wasOwner = $user->exists
                && $user->isDirty('role')
                && $user->getOriginal('role') === UserRole::Owner;

            if ($wasOwner && AdminAccounts::ownerCountForUpdate() <= 1) {
                throw LastOwnerStands::demoted();
            }
        });
    }
}
