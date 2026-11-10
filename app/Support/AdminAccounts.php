<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Who may change an admin account, and what would lock everybody out.
 *
 * Separate from the Filament resource on purpose. These are the rules that
 * matter, they are easy to get subtly wrong, and a screen is an awkward place
 * to test them. Every gate on the Users area asks this class.
 *
 * Two things it exists to prevent:
 *
 *   An editor making themselves an owner. Editors cannot see anyone's email
 *   address, which is the whole point of the role; one promotion undoes it.
 *
 *   The last owner going away. Panel accounts are created from the command
 *   line, so an empty owner list is not an inconvenience, it is an SSH session
 *   and a server. Nobody can delete the last owner, and nobody can demote
 *   themselves out of being it.
 */
class AdminAccounts
{
    /** Only an owner may see or touch the Users area at all. */
    public static function mayManage(?User $actor): bool
    {
        return $actor?->isOwner() ?? false;
    }

    /**
     * Whether this account can be removed.
     *
     * Not yourself: deleting the account you are signed in as logs you out
     * mid-action and reads as a bug. Not the last owner, ever.
     */
    public static function mayDelete(?User $actor, User $target): bool
    {
        if (! self::mayManage($actor)) {
            return false;
        }

        if ($actor->is($target)) {
            return false;
        }

        return ! self::isLastOwner($target);
    }

    /**
     * Whether this account's role can still be changed.
     *
     * The last owner cannot be demoted, whoever is doing it. A second owner
     * can, which is the normal way to hand the panel over: promote, then
     * demote, in that order.
     */
    public static function mayChangeRole(?User $actor, User $target): bool
    {
        return self::mayManage($actor) && ! self::isLastOwner($target);
    }

    /** The only owner left, so the panel depends on them. */
    public static function isLastOwner(User $user): bool
    {
        return $user->isOwner() && self::ownerCount() <= 1;
    }

    public static function ownerCount(): int
    {
        return User::query()->where('role', UserRole::Owner->value)->count();
    }

    /**
     * The same count, held until the surrounding transaction commits.
     *
     * ownerCount() is fine for drawing a screen: it decides which controls to
     * show and what to say about them. It is not enough to decide a write by,
     * because two owners acting in the same instant both read 2, both pass,
     * and the panel ends up with none. The lock makes the second one wait and
     * then see the truth.
     *
     * Only the model's own guards use this. See App\Models\User::booted().
     */
    public static function ownerCountForUpdate(): int
    {
        return User::query()->where('role', UserRole::Owner->value)->lockForUpdate()->count();
    }

    /** Said on screen, so somebody knows why a control is not there. */
    public static function whyLocked(?User $actor, User $target): ?string
    {
        if (! self::mayManage($actor)) {
            return 'Only an owner can change admin accounts.';
        }

        if (self::isLastOwner($target)) {
            return 'This is the only owner. Add another owner first, or there would be no way back into the panel.';
        }

        if ($actor->is($target)) {
            return 'This is you. Ask another owner to remove your account.';
        }

        return null;
    }
}
