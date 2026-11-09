<?php

namespace App\Console\Commands;

use App\Models\Block;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Removes what we hold about the families who left, once the grace is up.
 *
 * The account screen makes a promise in two halves: the profile goes straight
 * away, and everything else goes within thirty days. Pressing the button does
 * the first half. This is the second, and without it the screen is lying.
 *
 * The grace period is not an implementation detail. It is there so a family
 * who left in a bad week, or whose child pressed the button, can be put back
 * by a person before anything is actually gone.
 *
 * WHAT GOES: the email address, the name, the postcode area, the children's
 * months and years of birth, every answer they gave, the login token, the
 * consent record's IP and user agent, their journeys and their blocks.
 * Everything that is about them.
 *
 * WHAT STAYS, and why:
 *
 *   The row itself, scrubbed to a tombstone with no personal data on it. It
 *   is what keeps the other half of somebody else's conversation readable,
 *   which is the thing the leaving screen says we cannot undo. Deleting the
 *   row instead would take their messages out of another family's inbox, and
 *   we do not get to do that to a third party.
 *
 *   Messages they sent, in the conversations they sent them to. Same reason.
 *
 *   Safeguarding reports. A record that disappears when its subject deletes
 *   their account is not a safeguarding record, and the reported name is
 *   already kept as text precisely so the row still reads.
 */
class PurgeDepartedFamilies extends Command
{
    protected $signature = 'frith:purge-departed {--dry-run : List who would go, and change nothing}';

    protected $description = 'Remove what Frith holds about families who left, once the grace period is up';

    public function handle(): int
    {
        $graceDays = (int) config('frith.deletion.grace_days');

        $departed = Registration::query()
            ->whereNotNull('deletion_requested_at')
            ->where('deletion_requested_at', '<=', now()->subDays($graceDays))
            // A tombstone has no email, so it is never picked up twice.
            ->whereNotNull('email')
            ->get();

        if ($departed->isEmpty()) {
            $this->info('Nobody to remove.');

            return self::SUCCESS;
        }

        foreach ($departed as $family) {
            $this->line(($this->option('dry-run') ? 'Would scrub: ' : 'Scrubbing: ')
                .$family->public_id.' (left '.$family->deletion_requested_at->diffForHumans().')');

            if (! $this->option('dry-run')) {
                $this->scrub($family);
            }
        }

        $this->info($departed->count().($this->option('dry-run') ? ' would be removed.' : ' removed.'));

        return self::SUCCESS;
    }

    private function scrub(Registration $family): void
    {
        DB::transaction(function () use ($family) {
            $family->children()->delete();
            $family->experiences()->delete();
            $family->journeys()->detach();

            // Their blocks go with them. A block exists to keep two people
            // apart, and one of them is no longer here.
            Block::query()
                ->where('blocker_id', $family->id)
                ->orWhere('blocked_id', $family->id)
                ->delete();

            $family->forceFill([
                // Null rather than a placeholder, so the unique index does
                // not collide the moment a second family leaves.
                'email' => null,
                'password' => null,
                'login_token' => null,
                'login_token_expires_at' => null,
                'first_name' => null,
                'display_name' => null,
                'postcode_outcode' => null,
                'family_structures' => null,
                'support_areas' => null,
                'interests' => null,
                'interests_other' => null,
                'activity_supports' => null,
                'hopes' => null,
                'connection_styles' => null,
                'family_preferences' => null,
                'notification_prefs' => null,
                'hidden_badges' => null,
                'helping_hand' => false,
                // The consent record is evidence about a person. With the
                // person gone there is nobody for it to be evidence about.
                'consent_ip' => null,
                'consent_user_agent' => null,
                'remember_token' => Str::random(60),
            ])->save();
        });
    }
}
