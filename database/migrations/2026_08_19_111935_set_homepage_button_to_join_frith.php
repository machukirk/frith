<?php

use App\Models\Page;
use App\Support\PageContent;
use Illuminate\Database\Migrations\Migration;

/**
 * The homepage button now opens a registration, not an email sign-up.
 *
 * "Tell me when Frith launches" described the old form. "Join Frith" is what
 * the guidelines' own applied homepage uses, and it says what the button
 * actually does.
 *
 * Applied only where the wording is still the old default. If anybody has
 * already rewritten it in the admin panel, theirs stands — a migration quietly
 * overwriting an editor is the thing the seeder is careful never to do.
 */
return new class extends Migration
{
    private const WAS = 'Tell me when Frith launches';

    private const NOW = 'Join Frith';

    public function up(): void
    {
        $this->rename(self::WAS, self::NOW);
    }

    public function down(): void
    {
        $this->rename(self::NOW, self::WAS);
    }

    private function rename(string $from, string $to): void
    {
        $page = Page::query()->where('slug', 'coming-soon')->first();

        if ($page === null || ($page->content['form']['button'] ?? null) !== $from) {
            return;
        }

        $content = $page->content;
        $content['form']['button'] = $to;

        // Saving through the model rather than the query builder, so the
        // cached page content is cleared the same way an edit would clear it.
        $page->update(['content' => $content]);

        PageContent::forget('coming-soon');
    }
};
