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

    /**
     * The line under a connected family's name.
     *
     * Three different facts, and the middle one is the one worth getting
     * right: a family who said hello and has had no answer should be told
     * that, plainly, rather than shown a date they have to work out for
     * themselves. It is also the nudge that is actually useful, because the
     * other family may simply not have looked yet.
     */
    public function lastSpokeLabel(Registration $family): string
    {
        $last = $this->messages->last();

        if ($last === null) {
            return 'No messages yet';
        }

        if ($this->messages->every(fn (Message $message) => $message->sender_id === $family->id)) {
            return 'You said hello, no reply yet';
        }

        $when = $last->created_at;

        return match (true) {
            $when->isToday() => 'Last spoke today',
            $when->isYesterday() => 'Last spoke yesterday',
            $when->greaterThan(now()->subWeek()) => 'Last spoke '.$when->format('l'),
            $when->isCurrentYear() => 'Last spoke in '.$when->format('F'),
            default => 'Last spoke in '.$when->format('F Y'),
        };
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
