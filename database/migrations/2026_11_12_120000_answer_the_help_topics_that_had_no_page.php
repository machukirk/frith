<?php

use App\Support\PageContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Puts an answer behind the sixteen help topics that had no page.
 *
 * Twenty-five links on the help centre, and sixteen of them carried
 * `route => null`, which App\Support\SiteNavigation turns into '#'. A family
 * looking for how to block someone, or why nothing is showing near them,
 * pressed the link and the page did nothing. The search returned the same
 * sixteen as results, so searching led to the same place.
 *
 * There is no CMS for help articles and there is not going to be one for this
 * release, so the fix is an answer shown on the help page itself, as a
 * <details> under the topic's own label. config/content/help.php carries the
 * words and this moves them into the stored page.
 *
 * Which it has to, or none of it appears on screen. config/content is only
 * the FLOOR: App\Support\PageContent layers the stored row over it, and
 * `topics` is a list, so the stored list replaces the file's outright rather
 * than merging into it. Editing the config alone would leave every visitor on
 * the broken version.
 *
 * The answers are repeated here rather than read from config on purpose. A
 * migration has to say the same thing in a year's time as it says today, and
 * reading the file would make it write whatever the copy has become by then.
 *
 * Conditional, like every copy migration here. A link is only touched while
 * it is still exactly what shipped: our label, no route, and nothing written
 * in the answer already. An editor who has pointed one of these at a real
 * page, or renamed it, keeps what they did.
 *
 * Worth knowing: pages.content is JSON, so the apostrophes below are stored
 * as ’ escapes. Grepping the column as text finds nothing and reads as a
 * clean result. Decode before you search, as this does.
 */
return new class extends Migration
{
    /**
     * Keyed by the label that shipped, which is also what the conditional
     * matches on. The anchor is a search result's destination and must not
     * change once it has been out, or a bookmarked result stops opening.
     *
     * @var array<string, array{anchor: string, answer: string}>
     */
    private const ANSWERS = [
        'Registering and verifying' => [
            'anchor' => 'registering-and-verifying',
            'answer' => 'Registering is six short screens, and each one saves on its own, so you can stop halfway and come back without losing anything. About ten minutes after you start we email you. That message says you are registered and carries a link that confirms the address is yours. You are registered either way, but until you follow that link you are not on the list the launch email goes to.',
        ],
        'Asking to connect' => [
            'anchor' => 'asking-to-connect',
            'answer' => 'You can ask any family you share a Journey with. Open their profile, press the button, and they will see it the next time they look. Nothing else happens until they answer, and while it is unanswered you can take the request back from your Requests screen.',
        ],
        'The 10-request limit' => [
            'anchor' => 'the-10-request-limit',
            'answer' => 'There is no limit. Nothing counts how many families you ask and nothing stops you at ten. What does happen is that a family you have asked, or who has asked you, drops out of your suggestions, so the same name does not keep coming round.',
        ],
        'Messaging and meeting up' => [
            'anchor' => 'messaging-and-meeting-up',
            'answer' => 'Messages open once both families have said yes, and not before. A message is plain text, up to two thousand characters, with nothing to attach. Meeting up is arranged between the two of you, and the meet-up safety guide is worth reading first.',
        ],
        'Blocking someone' => [
            'anchor' => 'blocking-someone',
            'answer' => 'Blocking is on every family’s profile. It needs no reason, the other family is never told, and from then on they cannot see you and you will not see them. Your blocks are listed under Your data in your account, where you can undo one, and unblocking is just as quiet.',
        ],
        'Editing your profile' => [
            'anchor' => 'editing-your-profile',
            'answer' => 'Everything registration asked you can be changed from your account. Each of the six sections opens the screen you filled in the first time, with the same wording and the same options. Every few months we will ask whether what your family is navigating still sounds right, and telling us it has not changed counts as an answer.',
        ],
        'Notifications and quiet hours' => [
            'anchor' => 'notifications-and-quiet-hours',
            'answer' => 'There are no quiet hours. There is no setting for the time of day an email arrives and we have not built one. What you can change is in your account under Notifications: replies, requests to connect, updates from your Journeys and news from Frith. Being told when a Frith+ member asks to connect is the one that cannot be turned off, or a request could sit unseen for weeks.',
        ],
        'Badges on your profile' => [
            'anchor' => 'badges-on-your-profile',
            'answer' => 'Badges describe, they do not reward. Nothing is counted and nothing expires. Most are worked out from what we already know, such as when you joined or whether your profile is filled in, and Helping hand is the one you choose. You can hide any of them from your profile, and nobody is told that you have.',
        ],
        'Leaving Frith' => [
            'anchor' => 'leaving-frith',
            'answer' => 'Your account has a screen under Your data that explains what leaving does before it does it. Your profile stops being visible straight away, you leave your Journeys, and any unanswered requests are closed. The rest of your record is deleted after 30 days. Messages you have already sent stay in the other family’s inbox, because we cannot take your words out of somebody else’s conversation.',
        ],
        'Prices and billing' => [
            'anchor' => 'prices-and-billing',
            'answer' => 'There is nothing to pay. Frith has no payment screen, takes no card details and bills nobody. Joining, your profile, your Journeys, connecting and messaging are free and will stay free. When Frith+ goes on sale we will say what it costs before anyone is asked for anything.',
        ],
        'Cancelling' => [
            'anchor' => 'cancelling',
            'answer' => 'There is no subscription to cancel, because nobody is paying for Frith+ yet. If you want fewer emails, every message we send carries an unsubscribe link, and you can change which ones you get in your account under Notifications. If you want to go altogether, see Leaving Frith under Your account.',
        ],
        'First Frith Family' => [
            'anchor' => 'first-frith-family',
            'answer' => 'It is the badge for the first hundred families to register, and a Founder number is what marks one. A number is given out once and never reused, so Founder #47 stays true even if number 12 later leaves. Frith+ is free for those families for good, and once the hundred are gone no more are given out.',
        ],
        'I cannot log in' => [
            'anchor' => 'i-cannot-log-in',
            'answer' => 'Registering never asked for a password, so most families do not have one. On the login screen, ask us to email you a link instead. It works once and lasts an hour, and you can ask for another whenever you need to. We answer the same way whether or not we find an account, so being told a link is on its way is not proof the address was right.',
        ],
        'Nothing is showing near me' => [
            'anchor' => 'nothing-is-showing-near-me',
            'answer' => 'You are only ever shown families you share a Journey with. If you have not joined one there is nobody to show, and joining is also what makes your own profile visible to the others in it. There is no radius to widen and no search of everybody nearby, which is deliberate, so a quiet Journey is one that is still small.',
        ],
        'I did not get an email' => [
            'anchor' => 'i-did-not-get-an-email',
            'answer' => 'The email after registering is sent about ten minutes later on purpose, so it does not arrive while you are still filling the form in. If it is not there after that, look in spam or promotions. You are registered either way, and if it still has not come, write to us using the form below and a person will look.',
        ],
        'Something looks broken' => [
            'anchor' => 'something-looks-broken',
            'answer' => 'Tell us, using the form below with Something is broken chosen. It helps to say what you were doing, what you expected and what happened instead, and whether you were on a phone or a computer. It comes to the people who build Frith, so there is no ticket number and nothing to chase.',
        ],
    ];

    public function up(): void
    {
        $this->rewrite(function (array $link): ?array {
            $answer = self::ANSWERS[$link['label'] ?? ''] ?? null;

            // Still exactly as it shipped, or leave it be: our label, no
            // route behind it, and nothing written in the answer already.
            if ($answer === null
                || ($link['route'] ?? null) !== null
                || filled($link['answer'] ?? null)) {
                return null;
            }

            return [
                'label' => $link['label'],
                'anchor' => $answer['anchor'],
                'answer' => $answer['answer'],
            ];
        });
    }

    public function down(): void
    {
        $this->rewrite(function (array $link): ?array {
            $answer = self::ANSWERS[$link['label'] ?? ''] ?? null;

            // Only our own words go back out. An answer somebody has since
            // rewritten is theirs, and rolling back must not lose it.
            if ($answer === null || ($link['answer'] ?? null) !== $answer['answer']) {
                return null;
            }

            return ['label' => $link['label'], 'route' => null];
        });
    }

    /**
     * Walks the help page's topic links and replaces the ones the closure
     * returns something for. Read, rewritten and saved once.
     */
    private function rewrite(Closure $change): void
    {
        $row = DB::table('pages')->where('slug', 'help')->first();

        if ($row === null) {
            return;
        }

        $content = json_decode($row->content ?? '{}', true);

        if (! is_array($content) || ! is_array($content['topics'] ?? null)) {
            return;
        }

        $touched = false;

        foreach ($content['topics'] as $card => $topic) {
            foreach ($topic['links'] ?? [] as $i => $link) {
                if (! is_array($link)) {
                    continue;
                }

                $replacement = $change($link);

                if ($replacement === null) {
                    continue;
                }

                $content['topics'][$card]['links'][$i] = $replacement;
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

        // DB::table skips the model event that normally clears this, and
        // PageContent caches for ever. Without this the site keeps serving
        // the broken links until somebody thinks to clear the cache by hand.
        rescue(fn () => PageContent::forget('help'), report: false);
    }
};
