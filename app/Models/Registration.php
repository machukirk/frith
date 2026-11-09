<?php

namespace App\Models;

use App\Support\Postcode;
use App\Support\Taxonomy;
use Database\Factories\RegistrationFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Carbon;
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
            'experiences_reviewed_at' => 'datetime',
            'experiences_snoozed_at' => 'datetime',
            'data_requested_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'hidden_badges' => 'array',
            'notification_prefs' => 'array',
            'helping_hand' => 'boolean',
            'quiet_hours' => 'boolean',
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

    /** Every connection this family is either end of. */
    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class, 'requester_id');
    }

    public function blocksMade(): HasMany
    {
        return $this->hasMany(Block::class, 'blocker_id');
    }

    /**
     * Whether these two should be kept apart. Deliberately symmetrical: a
     * block hides each family from the other, so the one who was blocked
     * stops seeing them too and is never told why.
     */
    public function isSeparatedFrom(self $other): bool
    {
        return Block::query()
            ->where(fn ($q) => $q->where('blocker_id', $this->id)->where('blocked_id', $other->id))
            ->orWhere(fn ($q) => $q->where('blocker_id', $other->id)->where('blocked_id', $this->id))
            ->exists();
    }

    /** @return array<int, int> ids this family must never be shown. */
    public function separatedIds(): array
    {
        return Block::query()
            ->where('blocker_id', $this->id)
            ->pluck('blocked_id')
            ->merge(Block::query()->where('blocked_id', $this->id)->pluck('blocker_id'))
            ->unique()
            ->values()
            ->all();
    }

    /** The Journeys this family has joined. */
    public function journeys(): BelongsToMany
    {
        return $this->belongsToMany(Journey::class)
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    /**
     * One of the first hundred. What a number means, in one place, so nothing
     * downstream has to know that it is the number that decides.
     */
    public function isFounder(): bool
    {
        return $this->founder_number !== null;
    }

    /**
     * What other families see. The name they chose, or their first name until
     * they have chosen one.
     *
     * A family who has left and been scrubbed has neither, and still appears
     * at the top of a conversation somebody else can still read. So they get
     * a name that says what happened rather than a blank line.
     */
    public function displayName(): string
    {
        $name = trim((string) ($this->display_name ?: $this->first_name));

        return $name !== '' ? $name : ($this->hasLeft() ? 'A family who has left' : '');
    }

    /** Whether they have asked to leave. Gone from the product either way. */
    public function hasLeft(): bool
    {
        return $this->deletion_requested_at !== null;
    }

    /** Whether they have been through the village's one-off setup. */
    public function hasProfile(): bool
    {
        return filled($this->display_name);
    }

    /**
     * The rough area, which is as much of a location as anybody ever sees.
     *
     * The post town where we are sure of it, the outcode where we are not.
     * See App\Support\Postcode, which holds the reason that table is only
     * partly filled in.
     */
    public function area(): string
    {
        return Postcode::label($this->postcode_outcode);
    }

    /**
     * Whether Frith+ is theirs.
     *
     * Founders only, for now, which is exactly what the Founders cap means:
     * the first hundred families never pay for Frith+, and until there is
     * something to pay with, nobody else has it. One method, so the day
     * subscriptions arrive there is one place to change.
     */
    public function hasPlus(): bool
    {
        return $this->isFounder();
    }

    /**
     * Whether to ask "does this still sound like your family?".
     *
     * Asked again every few months, because what a family is navigating is
     * the thing most likely to have changed and the thing matching leans on
     * hardest. Saying "not now" counts as an answer and buys the same quiet
     * as updating it, so the nudge cannot be made into a nag by ignoring it.
     */
    public function needsExperienceReview(): bool
    {
        if ($this->experiences->isEmpty()) {
            return false;
        }

        $last = $this->experiences_reviewed_at
            ?? $this->experiences_snoozed_at
            ?? $this->completed_at
            ?? $this->created_at;

        if ($this->experiences_snoozed_at?->isAfter($last)) {
            $last = $this->experiences_snoozed_at;
        }

        return $last !== null
            && $last->addMonths((int) config('frith.review.months', 3))->isPast();
    }

    /** When they last said anything about their experiences, either way. */
    public function experiencesLastTouched(): ?Carbon
    {
        return $this->experiences_reviewed_at ?? $this->completed_at ?? $this->created_at;
    }

    /** The letter in the circle beside their name. */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->displayName(), 0, 1));
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

    /**
     * Families who are still here.
     *
     * Somebody who has asked to leave is gone from everything the product
     * shows the moment they press the button, whatever is still waiting to be
     * deleted behind the scenes. Every query that puts one family in front of
     * another goes through this.
     */
    #[Scope]
    protected function present(Builder $query): void
    {
        $query->whereNull('deletion_requested_at');
    }
}
