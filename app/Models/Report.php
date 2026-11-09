<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A safeguarding record.
 *
 * It keeps the reported family's name as text as well as the link, because
 * the row has to still mean something after that account is gone.
 */
class Report extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'reporter_id');
    }

    public function reported(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'reported_id');
    }
}
