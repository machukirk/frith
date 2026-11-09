<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One family asking another whether they would like to talk.
 *
 * Pending until it is answered. A decline is kept rather than deleted, so the
 * same family is not put in front of somebody again after they have said no.
 */
class Connection extends Model
{
    protected $guarded = [];

    public const PENDING = 'pending';
    public const ACCEPTED = 'accepted';
    public const DECLINED = 'declined';

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'requester_id');
    }

    public function addressee(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'addressee_id');
    }

    public function journey(): BelongsTo
    {
        return $this->belongsTo(Journey::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function isAccepted(): bool
    {
        return $this->status === self::ACCEPTED;
    }

    /** The family on the other end of this, whoever is asking. */
    public function otherThan(Registration $family): Registration
    {
        return $this->requester_id === $family->id ? $this->addressee : $this->requester;
    }

    public function involves(Registration $family): bool
    {
        return in_array($family->id, [$this->requester_id, $this->addressee_id], true);
    }

    /** Every connection a family is either end of. */
    public function scopeFor(Builder $query, Registration $family): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('requester_id', $family->id)
            ->orWhere('addressee_id', $family->id));
    }

    /**
     * The one between two families, in whichever direction it was made.
     */
    public static function between(Registration $a, Registration $b): ?self
    {
        return self::query()
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $p) => $p->where('requester_id', $a->id)->where('addressee_id', $b->id))
                ->orWhere(fn (Builder $p) => $p->where('requester_id', $b->id)->where('addressee_id', $a->id)))
            ->first();
    }
}
