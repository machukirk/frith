<?php

namespace App\Models;

use Database\Factories\WaitlistSignupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitlistSignup extends Model
{
    /** @use HasFactory<WaitlistSignupFactory> */
    use HasFactory;

    use HasUlids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * HasUlids would otherwise try to fill the primary key. We only want a ULID
     * on public_id, which is what appears in confirm and unsubscribe URLs.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * Lowercased and trimmed, so "Sam@Example.com " and "sam@example.com" are
     * one person and the unique index means it.
     */
    public static function normaliseEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function hasUnsubscribed(): bool
    {
        return $this->unsubscribed_at !== null;
    }

    /**
     * Whether enough time has passed to email this address again. Anyone can
     * type anyone's address into the form, so without a cooldown the endpoint
     * is a way to repeatedly mail a stranger.
     */
    public function canSendMail(): bool
    {
        if ($this->confirmation_sent_at === null) {
            return true;
        }

        $cooldown = (int) config('frith.waitlist.resend_cooldown_minutes');

        return $this->confirmation_sent_at->addMinutes($cooldown)->isPast();
    }

    /** Everyone we may legitimately email when Frith launches. */
    protected function scopeMailable(Builder $query): void
    {
        $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }
}
