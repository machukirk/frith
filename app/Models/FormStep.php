<?php

namespace App\Models;

use App\Support\FormDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormStep extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_private' => 'boolean'];
    }

    protected static function booted(): void
    {
        // The cache is keyed by form, so a step change has to clear its parent.
        static::saved(fn (FormStep $s) => FormDefinition::forget($s->form?->slug));
        static::deleted(fn (FormStep $s) => FormDefinition::forget($s->form?->slug));
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('position');
    }
}
