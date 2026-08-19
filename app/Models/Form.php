<?php

namespace App\Models;

use App\Support\FormDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn (Form $form) => FormDefinition::forget($form->slug));
        static::deleted(fn (Form $form) => FormDefinition::forget($form->slug));
    }

    public function steps(): HasMany
    {
        return $this->hasMany(FormStep::class)->orderBy('position');
    }

    public function options(): HasMany
    {
        return $this->hasMany(FormOption::class)->orderBy('position');
    }

    /** Top-level options in one group, in order, excluding archived ones. */
    public function optionsIn(string $group): HasMany
    {
        return $this->options()->where('group', $group)->whereNull('parent_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
