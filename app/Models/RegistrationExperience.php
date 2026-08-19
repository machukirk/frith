<?php

namespace App\Models;

use App\Support\Taxonomy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One detailed statement a family selected, as a pair of taxonomy slugs.
 *
 * Private throughout. The data doc is explicit that this is used to match
 * families and is never shown on a profile.
 */
class RegistrationExperience extends Model
{
    protected $guarded = [];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function label(): string
    {
        return Taxonomy::itemLabel($this->category, $this->item);
    }

    public function categoryLabel(): string
    {
        return Taxonomy::categoryLabel($this->category);
    }
}
