<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One thing a family is going through.
 *
 * Journeys link people by experience rather than by diagnosis, which is the
 * whole point of them: two families waiting on the same assessment have far
 * more to say to each other than two who share a label.
 */
class Journey extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** The families in it. */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Registration::class)
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    /**
     * On the form. An archived Journey keeps its members and its page, so
     * nobody's membership silently disappears, but nobody new can join.
     */
    public function scopeOffered(Builder $query): Builder
    {
        return $query->whereNull('archived_at')->orderBy('position');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
