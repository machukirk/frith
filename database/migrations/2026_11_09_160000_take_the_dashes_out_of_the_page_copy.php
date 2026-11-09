<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Takes the em dashes out of the page copy stored in the database.
 *
 * The house style does not allow an em dash anywhere, and a sweep of the code
 * has just removed every one. On its own that changes nothing a visitor sees:
 * config/content is only the FLOOR, and App\Support\PageContent layers the
 * stored version over the top, key by key. Forty-five strings across ten pages
 * are stored, so without this the site keeps serving the dashes.
 *
 * Worth knowing for next time: pages.content is JSON, so a dash is written as
 * a \u2014 escape. Grepping the column as text finds nothing and reads as a
 * clean result. Decode before you search.
 *
 * Conditional, like every reword here. Each change applies only where the
 * stored text is still the exact sentence that shipped, so anything an editor
 * has rewritten is left as they wrote it. Every one of the forty-five matched
 * when this was written; by the time it runs on a server, some may not, and
 * those are meant to be skipped.
 */
return new class extends Migration
{
    /** @var array<int, array{slug: string, path: string, from: string, to: string}> */
    private const COPY = [
        [
            'slug' => 'about',
            'path' => 'meta.title',
            'from' => 'About Frith — why we built it, and how we pay for it',
            'to' => 'About Frith: why we built it, and how we pay for it',
        ],
        [
            'slug' => 'about',
            'path' => 'page_head.standfirst',
            'from' => 'You are in a waiting room, or a school car park, or scrolling at 1am, and you realise you are explaining yourself again — to someone who means well and still does not get it.',
            'to' => 'You are in a waiting room, or a school car park, or scrolling at 1am, and you realise you are explaining yourself again to someone who means well and still does not get it.',
        ],
        [
            'slug' => 'about',
            'path' => 'page_head.body',
            'from' => 'Frith is an old English word for peace, refuge, the safety of belonging to a group that looks after its own. We built Frith so you can stop explaining. It connects you to families near you who already know — who have sat in that waiting room, filled in that form, had that phone call.',
            'to' => 'Frith is an old English word for peace, refuge, the safety of belonging to a group that looks after its own. We built Frith so you can stop explaining. It connects you to families near you who already know, who have sat in that waiting room, filled in that form, had that phone call.',
        ],
        [
            'slug' => 'about',
            'path' => 'purpose.vision.body',
            'from' => 'We start with families navigating SEND, because that is where the isolation is sharpest — 93% of carers of disabled children report loneliness. We build for every family, because belonging is not a niche.',
            'to' => 'We start with families navigating SEND, because that is where the isolation is sharpest: 93% of carers of disabled children report loneliness. We build for every family, because belonging is not a niche.',
        ],
        [
            'slug' => 'community-guidelines',
            'path' => 'meta.title',
            'from' => 'Community Guidelines — Frith',
            'to' => 'Community Guidelines · Frith',
        ],
        [
            'slug' => 'community-guidelines',
            'path' => 'meta.description',
            'from' => 'What we expect of families on Frith, what is not allowed, and exactly what happens when you report someone — read by a person, usually within 24 hours.',
            'to' => 'What we expect of families on Frith, what is not allowed, and exactly what happens when you report someone. A person reads every report, usually within 24 hours.',
        ],
        [
            'slug' => 'community-guidelines',
            'path' => 'intro',
            'from' => 'Most families never need this page. It exists so that the few who do know exactly where they stand — and so you know what will happen if you report someone.',
            'to' => 'Most families never need this page. It exists so that the few who do know exactly where they stand, and so you know what will happen if you report someone.',
        ],
        [
            'slug' => 'community-guidelines',
            'path' => 'not_allowed.items.2',
            'from' => 'Sharing another family’s information — including their identity, their child’s details, or their messages.',
            'to' => 'Sharing another family’s information, including their identity, their child’s details, or their messages.',
        ],
        [
            'slug' => 'community-guidelines',
            'path' => 'reporting.stages.0.body',
            'from' => 'The family is blocked. They cannot see you, find you, or message you — whatever we decide about the report, and they are never told you reported them.',
            'to' => 'The family is blocked. They cannot see you, find you, or message you. The block stays, whatever we decide about the report, and they are never told you reported them.',
        ],
        [
            'slug' => 'community-guidelines',
            'path' => 'reporting.stages.3.body',
            'from' => 'That we have read it and that we have acted. We will not share what we decided about another family — their privacy holds too, and we would protect yours the same way.',
            'to' => 'That we have read it and that we have acted. We will not share what we decided about another family. Their privacy holds too, and we would protect yours the same way.',
        ],
        [
            'slug' => 'community-guidelines',
            'path' => 'appeals.body',
            'from' => 'Tell us. We have suspended the wrong account before and we will again. Write to :email and a person — not a form — will look at it again.',
            'to' => 'Tell us. We have suspended the wrong account before and we will again. Write to :email and a person, not a form, will look at it again.',
        ],
        [
            'slug' => 'frith-plus',
            'path' => 'meta.title',
            'from' => 'Frith+ — discover more of your village',
            'to' => 'Frith+: discover more of your village',
        ],
        [
            'slug' => 'help',
            'path' => 'meta.title',
            'from' => 'Help centre — Frith',
            'to' => 'Help centre · Frith',
        ],
        [
            'slug' => 'help',
            'path' => 'head.standfirst',
            'from' => 'Most answers are here. If yours is not, a person will reply — usually the same day.',
            'to' => 'Most answers are here. If yours is not, a person will reply, usually the same day.',
        ],
        [
            'slug' => 'help',
            'path' => 'head.search_placeholder',
            'from' => 'Search help — try "block", "postcode" or "cancel"',
            'to' => 'Search help, try "block", "postcode" or "cancel"',
        ],
        [
            'slug' => 'help',
            'path' => 'search.empty',
            'from' => 'Nothing matched “:query”. Try a shorter word, or write to us and a person will reply — usually the same day.',
            'to' => 'Nothing matched “:query”. Try a shorter word, or write to us and a person will reply, usually the same day.',
        ],
        [
            'slug' => 'help',
            'path' => 'contact.form.standfirst',
            'from' => 'We read every one. There is no ticket number — a person replies.',
            'to' => 'We read every one. There is no ticket number, just a person who replies.',
        ],
        [
            'slug' => 'help',
            'path' => 'contact.form.sent',
            'from' => 'Thank you — that has gone to a person, and you will hear back. Usually the same day.',
            'to' => 'Thank you. That has gone to a person, and you will hear back. Usually the same day.',
        ],
        [
            'slug' => 'home',
            'path' => 'meta.title',
            'from' => 'Frith — find your people, find your village',
            'to' => 'Frith: find your people, find your village',
        ],
        [
            'slug' => 'home',
            'path' => 'meta.description',
            'from' => 'Frith helps those raising children connect with others who understand their experiences — matching families around what they have in common, not just where they live.',
            'to' => 'Frith helps those raising children connect with others who understand their experiences. We match families around what they have in common, not just where they live.',
        ],
        [
            'slug' => 'home',
            'path' => 'hero.standfirst',
            'from' => 'Frith helps those raising children connect with others who understand their experiences — matching families around what they have in common, not just where they live.',
            'to' => 'Frith helps those raising children connect with others who understand their experiences. We match families around what they have in common, not just where they live.',
        ],
        [
            'slug' => 'home',
            'path' => 'cta.body',
            'from' => 'Free to join. Verified adults only. Takes about a minute — and registering before we open makes Frith+ free for your family, for good.',
            'to' => 'Free to join. Verified adults only. Takes about a minute. Registering before we open makes Frith+ free for your family, for good.',
        ],
        [
            'slug' => 'home',
            'path' => 'faq.items.0.answer',
            'from' => 'Yes. Joining, building your profile, choosing your Journeys, connecting with families who ask you too, and messaging them are all free and always will be. Frith+ is an optional membership that adds more ways to look — and if you register before we open, it stays free for your family for good.',
            'to' => 'Yes. Joining, building your profile, choosing your Journeys, connecting with families who ask you too, and messaging them are all free and always will be. Frith+ is an optional membership that adds more ways to look. If you register before we open, it stays free for your family for good.',
        ],
        [
            'slug' => 'home',
            'path' => 'faq.footer',
            'from' => 'Something not answered here? Write to :email — a real person will reply, usually the same day.',
            'to' => 'Something not answered here? Write to :email. A real person will reply, usually the same day.',
        ],
        [
            'slug' => 'how-it-works',
            'path' => 'meta.title',
            'from' => 'How Frith works — from registering to your first coffee',
            'to' => 'How Frith works: from registering to your first coffee',
        ],
        [
            'slug' => 'how-it-works',
            'path' => 'steps.1.body',
            'from' => 'Before you can message a single family. It is not a character reference and we say so — but it means nobody on Frith is anonymous.',
            'to' => 'Before you can message a single family. It is not a character reference and we say so, but it means nobody on Frith is anonymous.',
        ],
        [
            'slug' => 'how-it-works',
            'path' => 'steps.2.body',
            'from' => 'A Journey is a group of families navigating one thing. Joining one is what makes your profile visible to the families in it — and nowhere else.',
            'to' => 'A Journey is a group of families navigating one thing. Joining one is what makes your profile visible to the families in it, and nowhere else.',
        ],
        [
            'slug' => 'how-it-works',
            'path' => 'cta.body',
            'from' => 'No feed, no groups, no swiping, no ranking. If it sounds small, that is because it is — the point is the people, not the software.',
            'to' => 'No feed, no groups, no swiping, no ranking. If it sounds small, that is because it is. The point is the people, not the software.',
        ],
        [
            'slug' => 'meet-up-safety',
            'path' => 'meta.title',
            'from' => 'Meeting another family for the first time — Frith',
            'to' => 'Meeting another family for the first time · Frith',
        ],
        [
            'slug' => 'meet-up-safety',
            'path' => 'meta.description',
            'from' => 'Meet somewhere public, tell someone where you’ll be, and leave if it doesn’t feel right — plus what Frith checks before an adult can message you.',
            'to' => 'Meet somewhere public, tell someone where you’ll be, and leave if it doesn’t feel right. Here is what Frith checks before an adult can message you.',
        ],
        [
            'slug' => 'meet-up-safety',
            'path' => 'short_version.body',
            'from' => 'Meet somewhere public. Tell someone where you’ll be. Bring your own transport if you can. You can change your mind at any point, right up to the moment you arrive — and you never owe anyone an explanation.',
            'to' => 'Meet somewhere public. Tell someone where you’ll be. Bring your own transport if you can. You can change your mind at any point, right up to the moment you arrive, and you never owe anyone an explanation.',
        ],
        [
            'slug' => 'privacy',
            'path' => 'meta.title',
            'from' => 'Privacy Policy — Frith',
            'to' => 'Privacy Policy · Frith',
        ],
        [
            'slug' => 'privacy',
            'path' => 'holdings.rows.2.who',
            'from' => 'Nobody — we show a place name',
            'to' => 'Nobody, we show a place name',
        ],
        [
            'slug' => 'privacy',
            'path' => 'holdings.rows.4.who',
            'from' => 'Nobody directly — but a Journey you join is visible to its families',
            'to' => 'Nobody directly, but a Journey you join is visible to its families',
        ],
        [
            'slug' => 'privacy',
            'path' => 'sections.0.paragraphs.0',
            'from' => 'What your family is navigating may say something about health or disability, which UK data protection law treats as special category data. We hold it only because you have chosen to give it to us, and we use it to find your matches. We never display your answers on your profile. Note that joining a Journey is itself visible to the families in it — a Journey named for a particular experience will tell them something about your family, which is why joining is always your choice and you can leave at any time. You can remove your answers at any point and keep your account.',
            'to' => 'What your family is navigating may say something about health or disability, which UK data protection law treats as special category data. We hold it only because you have chosen to give it to us, and we use it to find your matches. We never display your answers on your profile. Note that joining a Journey is itself visible to the families in it. A Journey named for a particular experience will tell them something about your family, which is why joining is always your choice and you can leave at any time. You can remove your answers at any point and keep your account.',
        ],
        [
            'slug' => 'privacy',
            'path' => 'sections.2.paragraphs.0',
            'from' => 'Nobody, for money, ever. We use a small number of providers to run Frith — hosting, email delivery, payment processing and identity verification — and they only ever see what they need to do their part. We will disclose information to the police or a safeguarding authority where there is a risk to a child, and we would do so without hesitation.',
            'to' => 'Nobody, for money, ever. We use a small number of providers to run Frith: hosting, email delivery, payment processing and identity verification. Those providers only ever see what they need to do their part. We will disclose information to the police or a safeguarding authority where there is a risk to a child, and we would do so without hesitation.',
        ],
        [
            'slug' => 'privacy',
            'path' => 'sections.4.paragraphs.0',
            'from' => 'You can ask for a copy of everything we hold, correct anything wrong, remove anything you have given us, or have your account deleted. Requests are answered within 30 days and usually much sooner — most of it you can do yourself from your account.',
            'to' => 'You can ask for a copy of everything we hold, correct anything wrong, remove anything you have given us, or have your account deleted. Requests are answered within 30 days and usually much sooner. Most of it you can do yourself from your account.',
        ],
        [
            'slug' => 'reporting',
            'path' => 'meta.title',
            'from' => 'Reporting & complaints — Frith',
            'to' => 'Reporting & complaints · Frith',
        ],
        [
            'slug' => 'reporting',
            'path' => 'escalation.body',
            'from' => 'For a data complaint you can go to the Information Commissioner’s Office. For anything else, tell us and we will keep talking — there is no appeal panel above us, because Frith is small enough that the person who replies is the person who decides.',
            'to' => 'For a data complaint you can go to the Information Commissioner’s Office. For anything else, tell us and we will keep talking. There is no appeal panel above us, because Frith is small enough that the person who replies is the person who decides.',
        ],
        [
            'slug' => 'terms',
            'path' => 'meta.title',
            'from' => 'Terms of Service — Frith',
            'to' => 'Terms of Service · Frith',
        ],
        [
            'slug' => 'terms',
            'path' => 'sections.0.paragraphs.0',
            'from' => 'You must be 18 or over, and you must be a parent, carer, guardian or other adult with a caring role for a child. We verify that you are a real adult before you can message anyone. Verification confirms identity — it is not a character reference, and we do not run DBS checks.',
            'to' => 'You must be 18 or over, and you must be a parent, carer, guardian or other adult with a caring role for a child. We verify that you are a real adult before you can message anyone. Verification confirms identity. It is not a character reference, and we do not run DBS checks.',
        ],
        [
            'slug' => 'terms',
            'path' => 'sections.1.items.3',
            'from' => 'Never share information about someone else’s child — not their name, their diagnosis, their school, or their photograph.',
            'to' => 'Never share information about someone else’s child: not their name, their diagnosis, their school, or their photograph.',
        ],
        [
            'slug' => 'terms',
            'path' => 'sections.3.paragraphs.0',
            'from' => 'Frith is free to join and always will be for the basics — building a profile, joining the Journeys you choose, connecting with families who ask you too, and messaging them. Frith+ is an optional membership that adds more ways to look. Prices are shown before you pay and renewals are always emailed to you in advance.',
            'to' => 'Frith is free to join and always will be for the basics: building a profile, joining the Journeys you choose, connecting with families who ask you too, and messaging them. Frith+ is an optional membership that adds more ways to look. Prices are shown before you pay and renewals are always emailed to you in advance.',
        ],
        [
            'slug' => 'terms',
            'path' => 'sections.4.paragraphs.1',
            'from' => 'You can leave at any time from your account. Your profile disappears from Journeys and search straight away. Messages you have already sent stay in the other family’s inbox — we cannot remove words from someone else’s conversation. Everything else is deleted within 30 days.',
            'to' => 'You can leave at any time from your account. Your profile disappears from Journeys and search straight away. Messages you have already sent stay in the other family’s inbox. We cannot remove words from someone else’s conversation. Everything else is deleted within 30 days.',
        ],
        [
            'slug' => 'terms',
            'path' => 'closing',
            'from' => 'Something here unclear? Write to :email and we will explain it in plainer words — and probably rewrite this page.',
            'to' => 'Something here unclear? Write to :email and we will explain it in plainer words. We will probably rewrite this page too.',
        ],
    ];

    public function up(): void
    {
        $this->apply('from', 'to');
    }

    public function down(): void
    {
        $this->apply('to', 'from');
    }

    private function apply(string $was, string $becomes): void
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

                $this->set($content, $change['path'], $change[$becomes]);
                $touched = true;
            }

            if ($touched) {
                DB::table('pages')->where('id', $row->id)->update([
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /** @param array<string, mixed> $content */
    private function get(array $content, string $path): ?string
    {
        $value = data_get($content, $path);

        return is_string($value) ? $value : null;
    }

    /** @param array<string, mixed> $content */
    private function set(array &$content, string $path, string $value): void
    {
        data_set($content, $path, $value);
    }
};
