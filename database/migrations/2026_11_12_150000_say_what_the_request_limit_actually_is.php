<?php

use App\Support\PageContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Two corrections to the help centre's copy.
 *
 * The first matters. The topic "The 10-request limit" was answered with "There
 * is no limit. Nothing counts how many families you ask and nothing stops you
 * at ten." That was true when it was written. It stopped being true in the
 * same week, when App\Http\Controllers\ConnectionController::PENDING_LIMIT was
 * built and store() began refusing an eleventh ask. So a family who hits the
 * cap, reads the message naming it, and goes to the help centre to find out
 * why is told the cap does not exist.
 *
 * The second is small. The search box offered "postcode" as an example word,
 * and App\Support\HelpSearch reads topic labels and card titles only. Nothing
 * on the page says postcode, so the one example a family is most likely to try
 * first landed on the empty state.
 *
 * This is a second migration rather than an edit to 2026_11_12_120000 on
 * purpose. That one has already run where the answers are stored, so editing
 * it would correct a fresh install and leave every existing one wrong, and a
 * migration that has shipped should keep saying what it said. 120000 writes
 * the old words and this one replaces them, which is the right answer on a
 * server that has run neither and on a server that has run the first.
 *
 * Conditional, like every reword here: the exact string that shipped, or
 * nothing. An editor who has already rewritten either of these keeps it.
 */
return new class extends Migration
{
    /** @var array<int, array{path: string, from: string, to: string}> */
    private const COPY = [
        [
            'path' => 'head.search_placeholder',
            'from' => 'Search help, try "block", "postcode" or "cancel"',
            'to' => 'Search help, try "block", "badges" or "cancel"',
        ],
    ];

    private const LABEL = 'The 10-request limit';

    private const ANSWER_FROM = 'There is no limit. Nothing counts how many families you ask and nothing stops you at ten. What does happen is that a family you have asked, or who has asked you, drops out of your suggestions, so the same name does not keep coming round.';

    private const ANSWER_TO = 'Ten. It counts the asks still waiting for a reply, not the asks you have ever made. When one is answered, or you take it back from your Requests screen, a place frees up and you can ask somebody else. If you are already at ten, we name the families you are waiting on, so you know which ask to take back. A family you have asked, or who has asked you, also drops out of your suggestions, so the same name does not keep coming round.';

    public function up(): void
    {
        $this->apply('from', 'to', self::ANSWER_FROM, self::ANSWER_TO);
    }

    /**
     * Puts the old words back, which includes putting the wrong answer back.
     *
     * That is what down() is for and it is still the right shape, but it is
     * worth saying out loud: rolling this one back on its own leaves the help
     * centre denying a cap the code is still enforcing. If the cap itself is
     * ever removed, correct the answer forwards rather than rolling back to
     * it, because the sentence that was true then is not the sentence to use.
     */
    public function down(): void
    {
        $this->apply('to', 'from', self::ANSWER_TO, self::ANSWER_FROM);
    }

    private function apply(string $was, string $becomes, string $answerWas, string $answerBecomes): void
    {
        $row = DB::table('pages')->where('slug', 'help')->first();

        if ($row === null) {
            return;
        }

        $content = json_decode($row->content ?? '{}', true);

        if (! is_array($content)) {
            return;
        }

        $touched = false;

        foreach (self::COPY as $change) {
            if (data_get($content, $change['path']) !== $change[$was]) {
                continue;
            }

            data_set($content, $change['path'], $change[$becomes]);
            $touched = true;
        }

        foreach ($content['topics'] ?? [] as $card => $topic) {
            foreach ($topic['links'] ?? [] as $i => $link) {
                if (! is_array($link)
                    || ($link['label'] ?? null) !== self::LABEL
                    || ($link['answer'] ?? null) !== $answerWas) {
                    continue;
                }

                $content['topics'][$card]['links'][$i]['answer'] = $answerBecomes;
                $touched = true;
            }
        }

        if (! $touched) {
            return;
        }

        DB::table('pages')->where('id', $row->id)->update([
            'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);

        // DB::table skips the model event that clears this, and PageContent
        // caches for ever. Without it the site keeps serving the old answer.
        rescue(fn () => PageContent::forget('help'), report: false);
    }
};
