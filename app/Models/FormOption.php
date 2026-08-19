<?php

namespace App\Models;

use App\Support\FormDefinition;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One choice on a form.
 *
 * The slug is what gets written against a family, so it is fixed once created.
 * Archiving is the way to retire an option: it stops being offered and stops
 * being accepted, while every answer already given still resolves to a label.
 */
class FormOption extends Model
{
    /**
     * The lists of choices a form offers, and what they are called in the
     * admin panel. One place, because the options screen names them in the
     * select, the badge, the filter and the help text, and four copies of the
     * same map is four chances to add a list to three of them.
     *
     * The key is also the column on `registrations` that stores the answer,
     * which is what makes usageCount() work for any of them.
     */
    public const GROUPS = [
        'support_areas' => 'Areas of family life',
        'family_structures' => 'Who is part of your family',
        'interests' => 'Interests & activities',
        'activity_supports' => 'What helps them enjoy activities',
        'hopes' => 'What they are hoping to find',
        'connection_styles' => 'How they would prefer to connect',
        'family_preferences' => 'Who they would like to connect with',
    ];

    /** Short forms, for the table column that has no room for the long ones. */
    public const GROUP_BADGES = [
        'support_areas' => 'Family life',
        'family_structures' => 'Family',
        'interests' => 'Interests',
        'activity_supports' => 'What helps',
        'hopes' => 'Hoping to find',
        'connection_styles' => 'How to connect',
        'family_preferences' => 'Who to meet',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saved(fn (FormOption $o) => FormDefinition::forget($o->form?->slug));
        static::deleted(fn (FormOption $o) => FormDefinition::forget($o->form?->slug));
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FormOption::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FormOption::class, 'parent_id')->orderBy('position');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    #[Scope]
    protected function live(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * How many families have chosen this.
     *
     * Shown before anybody archives or deletes, because "31 families selected
     * this" is the only thing that makes the consequence visible.
     */
    public function usageCount(): int
    {
        if ($this->parent_id !== null) {
            return RegistrationExperience::query()
                ->where('category', $this->parent->slug)
                ->where('item', $this->slug)
                ->count();
        }

        // Every top-level group is stored as a JSON array on the registration
        // in a column of the same name, so the group *is* the column. An
        // unrecognised group counts nothing rather than guessing at one.
        if (! array_key_exists($this->group, self::GROUPS)) {
            return 0;
        }

        return Registration::query()->whereJsonContains($this->group, $this->slug)->count();
    }
}
