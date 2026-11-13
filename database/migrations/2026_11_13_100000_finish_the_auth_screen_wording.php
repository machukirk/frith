<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Two bits of wording the log-in screens were missing.
 *
 * "Send it again" on /link-sent had no key at all, so the button fell back to
 * a string in the template that no editor could reach. And /verified ended on
 * "Back to the homepage", sending a family who had just confirmed their email,
 * and who is signed in by that point, away from the product rather than into
 * it. The design says "Go to your village".
 *
 * config/content/auth.php is only the floor, and the auth row now exists, so
 * the config change alone would have shown nothing. Conditional as always:
 * only where the stored text is still what shipped.
 */
return new class extends Migration
{
    private const BUTTON_WAS = 'Back to the homepage';

    private const BUTTON_NOW = 'Go to your village';

    private const RESEND = 'Send it again';

    public function up(): void
    {
        $this->edit(function (array $content): array {
            if (($content['verified']['button'] ?? null) === self::BUTTON_WAS) {
                $content['verified']['button'] = self::BUTTON_NOW;
            }

            // Added rather than swapped: there was never a key here, so there
            // is nothing an editor could have rewritten.
            $content['link_sent']['resend'] ??= self::RESEND;

            return $content;
        });
    }

    public function down(): void
    {
        $this->edit(function (array $content): array {
            if (($content['verified']['button'] ?? null) === self::BUTTON_NOW) {
                $content['verified']['button'] = self::BUTTON_WAS;
            }

            if (($content['link_sent']['resend'] ?? null) === self::RESEND) {
                unset($content['link_sent']['resend']);
            }

            return $content;
        });
    }

    private function edit(callable $change): void
    {
        $row = DB::table('pages')->where('slug', 'auth')->first();

        if ($row === null) {
            return;
        }

        $content = json_decode($row->content ?? '{}', true);

        if (! is_array($content)) {
            return;
        }

        DB::table('pages')->where('id', $row->id)->update([
            'content' => json_encode($change($content), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }
};
