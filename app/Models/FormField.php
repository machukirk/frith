<?php

namespace App\Models;

use App\Support\FormDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn (FormField $f) => FormDefinition::forget($f->step?->form?->slug));
        static::deleted(fn (FormField $f) => FormDefinition::forget($f->step?->form?->slug));
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(FormStep::class, 'form_step_id');
    }
}
