<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One family deciding not to be found by another.
 *
 * One-sided and silent. The guidelines are explicit that nobody is ever told
 * they have been blocked, and nothing about this row is shown to the family
 * it names.
 */
class Block extends Model
{
    protected $guarded = [];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'blocker_id');
    }

    public function blocked(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'blocked_id');
    }
}
