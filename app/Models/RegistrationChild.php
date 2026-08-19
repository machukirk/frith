<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Month and year of birth only — never a full date.
 *
 * It is enough to keep a child's age current and to match families at a
 * similar stage, and it is markedly less than a date of birth to hold about
 * somebody else's child.
 */
class RegistrationChild extends Model
{
    protected $guarded = [];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /** Age in whole years, from the first of the birth month. */
    public function age(): int
    {
        // Birth date first: Carbon's diff is signed and directional, so the
        // operands the other way round give a negative age.
        return (int) $this->bornOn()->diffInYears(now());
    }

    public function bornOn(): Carbon
    {
        return now()->setDate($this->birth_year, $this->birth_month, 1)->startOfDay();
    }

    public function bornLabel(): string
    {
        return $this->bornOn()->format('F Y');
    }
}
