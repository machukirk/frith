<?php

namespace App\Models;

use App\Support\FormDefinition;
use App\Support\RegistrationFlow;
use App\Support\StepContent;
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
        // In the order somebody meets them filling the form in. The Options
        // screen groups by this, so the order is what an editor reads.
        'family_structures' => 'Who is part of your family',
        'support_areas' => 'Areas of family life',
        'interests' => 'Interests & activities',
        'activity_supports' => 'What helps them enjoy activities',
        'hopes' => 'What they are hoping to find',
        'connection_styles' => 'How they would prefer to connect',
        'family_preferences' => 'Who they would like to connect with',
    ];

    /**
     * Which screen each list is offered on.
     *
     * The Options screen is one table of a hundred-odd rows; without this an
     * editor can read every word on it and still not know which question they
     * are looking at.
     */
    public const GROUP_STEPS = [
        'family_structures' => 'family',
        'support_areas' => 'support',
        'interests' => 'interests',
        'activity_supports' => 'interests',
        'hopes' => 'finding',
        'connection_styles' => 'finding',
        'family_preferences' => 'finding',
    ];

    /** Short forms, for the table column that has no room for the long ones. */
    public const GROUP_BADGES = [
        'family_structures' => 'Family',
        'support_areas' => 'Family life',
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

    /**
     * The block of the Options screen this row belongs in.
     *
     * Top-level options group by their list. The detailed statements group by
     * the area they sit under instead, because sixty-three of them under one
     * heading is the pile this is meant to break up.
     */
    public function listTitle(): string
    {
        return $this->parent_id === null
            ? (self::GROUPS[$this->group] ?? $this->group)
            : ($this->parent?->label ?? 'Follow-up questions');
    }

    /** Where in the form that block is shown. */
    public function listDescription(): string
    {
        if ($this->parent_id !== null) {
            return 'Shown on the follow-up screen for this area';
        }

        $step = self::GROUP_STEPS[$this->group] ?? null;

        if ($step === null || ! RegistrationFlow::exists($step)) {
            return '';
        }

        return 'Step '.RegistrationFlow::number($step).' — '.StepContent::for($step)->heading();
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
