<?php

namespace App\Models;

use App\Support\Taxonomy;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A family registering as a Frith Founder.
 *
 * Saved on every step of the form rather than at the end, so somebody who gets
 * as far as their postcode and then has to go and deal with something is still
 * registered. That is the whole point of the design: no drop-off.
 */
class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory;

    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'family_structures' => 'array',
            'support_areas' => 'array',
            'consented_at' => 'datetime',
            'completed_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'verification_sent_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
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

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
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
