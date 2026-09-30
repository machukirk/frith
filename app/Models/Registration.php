<?php

namespace App\Models;

use App\Support\Taxonomy;
use Database\Factories\RegistrationFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Collection;

/**
 * A family registering as a Frith Founder.
 *
 * Saved on every step of the form rather than at the end, so somebody who gets
 * as far as their postcode and then has to go and deal with something is still
 * registered. That is the whole point of the design: no drop-off.
 */
class Registration extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use Authorizable;

    /** @use HasFactory<RegistrationFactory> */
    use HasFactory;

    use HasUlids;

    protected $guarded = [];

    /** Never in an array, a log line or a JSON response. */
    protected $hidden = ['password', 'remember_token', 'login_token'];

    protected function casts(): array
    {
        return [
            'family_structures' => 'array',
            'support_areas' => 'array',
            'interests' => 'array',
            'activity_supports' => 'array',
            'hopes' => 'array',
            'connection_styles' => 'array',
            'family_preferences' => 'array',
            'consented_at' => 'datetime',
            'completed_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'verification_sent_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'login_token_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function children(): HasMany
    {
        return $this->hasMany(RegistrationChild::class)->orderBy('position');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(RegistrationExperience::class);
    }

    public static function normaliseEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Outcodes are written every which way. Stored uppercase and unspaced so
     * "ss9", "SS9 " and "Ss9" are one place rather than three.
     */
    public static function normaliseOutcode(string $outcode): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', trim($outcode)));
    }

    /**
     * Whether they can log in with a password at all.
     *
     * Registration never sets one, so this is false for almost everybody. The
     * login screen says so rather than failing with "wrong password", which
     * would send somebody looking for a password that does not exist.
     */
    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * One of the first hundred. What a number means, in one place, so nothing
     * downstream has to know that it is the number that decides.
     */
    public function isFounder(): bool
    {
        return $this->founder_number !== null;
    }

    public function displayName(): string
    {
        return trim((string) $this->first_name);
    }

    /**
     * The categories they chose, in taxonomy order rather than submission
     * order, so section two always reads the same way round.
     *
     * @return array<int, string>
     */
    public function orderedSupportAreas(): array
    {
        return Taxonomy::orderCategories($this->support_areas ?? []);
    }

    /**
     * Their interests in taxonomy order rather than submission order.
     *
     * @return array<int, string>
     */
    public function orderedInterests(): array
    {
        return Taxonomy::orderInterests($this->interests ?? []);
    }

    /** Detailed selections grouped by category slug. */
    public function experiencesByCategory(): Collection
    {
        return $this->experiences->groupBy('category');
    }

    /**
     * Records how far they got, only ever forwards.
     *
     * Someone stepping back to fix their postcode has not un-answered the
     * questions after it, so the furthest point reached is what gets kept.
     */
    public function recordProgress(int $step): void
    {
        if ($step > $this->furthest_step) {
            $this->forceFill(['furthest_step' => $step])->save();
        }
    }

    /** Everyone we may email about the launch. */
    #[Scope]
    protected function contactable(Builder $query): void
    {
        $query->whereNull('unsubscribed_at');
    }

    #[Scope]
    protected function completed(Builder $query): void
    {
        $query->whereNotNull('completed_at');
    }
}
