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

        $column = $this->group === 'family_structures' ? 'family_structures' : 'support_areas';

        return Registration::query()->whereJsonContains($column, $this->slug)->count();
    }
}
