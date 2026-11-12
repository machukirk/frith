<?php

use App\Support\PageContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives the Cookies page a stored row, and takes the claims Frith+ cannot
 * meet out of the copy that is already in the database.
 *
 * Two jobs, one release, because they are the same job: config/content is only
 * the FLOOR. App\Support\PageContent layers the stored row over it key by key,
 * so editing a config file changes nothing a visitor sees once a row exists.
 * The Frith+ rewrite below is therefore not optional tidying. Without it the
 * site keeps selling "specialist Journeys", which the journeys table has no
 * concept of, and a "things in common" count every family already sees free.
 *
 * Worth knowing: pages.content is JSON, so a middle dot is stored as a ·
 * escape. Grepping the column as text finds nothing and reads as a clean
 * result. Decode before you search it.
 *
 * Every rewrite here is CONDITIONAL. It applies only where the stored text is
 * still the exact wording that shipped, so anything an editor has rewritten in
 * the admin panel is left as they wrote it. All four matched when this was
 * written. By the time it runs on a server some may not, and those are meant
 * to be skipped rather than forced.
 */
return new class extends Migration
{
    /** The page the footer has been linking to since before it existed. */
    private const NEW_PAGE = ['slug' => 'cookies', 'name' => 'Cookies'];

    /**
     * Plain string swaps, by dot path.
     *
     * @var array<int, array{slug: string, path: string, from: string, to: string}>
     */
    private const COPY = [
        [
            // The search snippet carried the same claim as the page and was
            // missed first time round. A stored row keeps it in front of
            // Google and in every shared link until this swaps it.
            'slug' => 'frith-plus',
            'path' => 'meta.description',
            'from' => 'Frith+ is an optional membership that gives you more ways to find and connect with families who feel right for you. Mutual connections are always free.',
            'to' => 'Frith+ is an optional membership, and it is not open yet. Finding families, asking to connect and messaging them are free, and they stay free.',
        ],
        [
            'slug' => 'frith-plus',
            'path' => 'intro.standfirst',
            'from' => 'You can still make mutual connections for free. Frith+ simply gives you more ways to find and connect with families who feel right for you.',
            'to' => 'Finding families, asking to connect and messaging them are free, and they stay free. Frith+ adds more ways to hear who wants to meet you.',
        ],
        [
            'slug' => 'frith-plus',
            'path' => 'benefits.label',
            'from' => 'With Frith+ you can:',
            'to' => 'What Frith+ will add when it opens:',
        ],
        [
            'slug' => 'frith-plus',
            'path' => 'cta.button',
            'from' => 'Explore Frith+',
            'to' => 'Register with Frith',
        ],
    ];

    /**
     * The benefits list, which changes shape rather than wording.
     *
     * Four items become three, so this cannot go through COPY above: a list is
     * replaced outright by PageContent::merge rather than merged item by item,
     * and a stored list of four would keep overriding a config list of three
     * for ever. Compared whole and replaced whole, for the same reason the
     * string swaps are compared whole.
     *
     * @var array<int, array{title: string, detail: string}>
     */
    private const BENEFITS_FROM = [
        ['title' => 'Explore more families', 'detail' => '- look beyond your Frith suggestions.'],
        ['title' => 'Spot stronger matches', 'detail' => '- see how many things you have in common as you browse.'],
        ['title' => 'Join more Journeys', 'detail' => '- access specialist Journeys.'],
        ['title' => 'See who wants to connect', 'detail' => '- view requests and choose who to connect with.'],
    ];

    /** @var array<int, array{title: string, detail: string}> */
    private const BENEFITS_TO = [
        ['title' => 'Hear about every ask', 'detail' => 'The free plan tells you when a Frith+ family asks to connect. Frith+ tells you about everyone who asks.'],
        ['title' => 'Set your own pace', 'detail' => 'Choose how often those emails arrive, from one a week to as many as come, or pause them.'],
        ['title' => 'News from your Journeys', 'detail' => 'Now and then, from the Journeys you have joined.'],
    ];

    public function up(): void
    {
        $this->addTheCookiesPage();
        $this->apply('from', 'to', self::BENEFITS_FROM, self::BENEFITS_TO);
    }

    public function down(): void
    {
        $this->apply('to', 'from', self::BENEFITS_TO, self::BENEFITS_FROM);
        $this->removeTheCookiesPage();
    }

    /**
     * The stored row for /cookies.
     *
     * The page renders without it, because PageContent falls back to the
     * config floor. The row is what makes it editable, and it is created here
     * rather than left to the seeder because seeding is opt in on a deploy and
     * most releases do not run it.
     *
     * database/seeders/PageSeeder.php still wants 'cookies' adding to its
     * list. Until it has it, the page is missing from the sitemap and from the
     * admin panel's Pages table, both of which read that list.
     */
    private function addTheCookiesPage(): void
    {
        if (DB::table('pages')->where('slug', self::NEW_PAGE['slug'])->exists()) {
            return;
        }

        $content = config('frith-content.'.self::NEW_PAGE['slug']);

        // If the config file is not readable for any reason, leave the table
        // alone. An empty content row would override the floor with nothing
        // and render a page of blanks, which is worse than no row at all.
        if (! is_array($content) || $content === []) {
            return;
        }

        DB::table('pages')->insert([
            'slug' => self::NEW_PAGE['slug'],
            'name' => self::NEW_PAGE['name'],
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->forget(self::NEW_PAGE['slug']);
    }

    /**
     * Only if nobody has touched it.
     *
     * Rolling back should undo what this migration did, not delete somebody's
     * writing. An edited row is left behind, where it is harmless: the admin
     * panel's Pages table lists the seeder's slugs, so an orphan does not
     * show up, and it is waiting if this is rolled forward again.
     */
    private function removeTheCookiesPage(): void
    {
        $content = config('frith-content.'.self::NEW_PAGE['slug']);
        $row = DB::table('pages')->where('slug', self::NEW_PAGE['slug'])->first();

        if ($row === null || ! is_array($content)) {
            return;
        }

        if (json_decode($row->content ?? '{}', true) !== $content) {
            return;
        }

        DB::table('pages')->where('id', $row->id)->delete();

        $this->forget(self::NEW_PAGE['slug']);
    }

    /**
     * @param  array<int, array{title: string, detail: string}>  $benefitsWas
     * @param  array<int, array{title: string, detail: string}>  $benefitsBecomes
     */
    private function apply(string $was, string $becomes, array $benefitsWas, array $benefitsBecomes): void
    {
        // Grouped by page so each row is read, rewritten and saved once,
        // rather than once per string.
        foreach (collect(self::COPY)->groupBy('slug') as $slug => $changes) {
            $row = DB::table('pages')->where('slug', $slug)->first();

            if ($row === null) {
                continue;
            }

            $content = json_decode($row->content ?? '{}', true);

            if (! is_array($content)) {
                continue;
            }

            $touched = false;

            foreach ($changes as $change) {
                if ($this->get($content, $change['path']) !== $change[$was]) {
                    continue;
                }

                data_set($content, $change['path'], $change[$becomes]);
                $touched = true;
            }

            if ($slug === 'frith-plus' && data_get($content, 'benefits.items') === $benefitsWas) {
                data_set($content, 'benefits.items', $benefitsBecomes);
                $touched = true;
            }

            if ($touched) {
                DB::table('pages')->where('id', $row->id)->update([
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);

                $this->forget($slug);
            }
        }
    }

    /** @param array<string, mixed> $content */
    private function get(array $content, string $path): ?string
    {
        $value = data_get($content, $path);

        return is_string($value) ? $value : null;
    }

    /**
     * PageContent caches for ever and nothing here goes through the model, so
     * no saved() event fires to clear it. A deploy runs optimize:clear a few
     * steps later and would cover it, but anybody running migrate on its own
     * would otherwise keep being served the old copy and think this did not
     * work. Rescued because the cache table may not exist yet on a fresh
     * install.
     */
    private function forget(string $slug): void
    {
        rescue(fn () => PageContent::forget($slug), report: false);
    }
};
