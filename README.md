# Frith — coming soon

Waiting-list page for [Frith](https://frith.community), a community app connecting
parents and carers of children with SEND. Laravel 13, no JavaScript, no third-party
requests.

## Local

```bash
ddev start && ddev artisan migrate && ddev npm install && ddev npm run build
```

The site is at https://frith.ddev.site and mail lands in Mailpit at
https://frith.ddev.site:8026 (`ddev mailpit`).

Confirmation emails go through the queue, so run a worker while testing:

```bash
ddev artisan queue:work
```

Tests:

```bash
ddev artisan test
```

## How a signup works

1. Someone submits their email. The row is stored **unconfirmed**, with the exact
   consent wording, version, timestamp and IP snapshotted onto it.
2. A confirmation email goes out via the queue, with a signed link valid for 14 days.
3. Clicking the link sets `confirmed_at`. Only confirmed, non-unsubscribed rows are
   mailable — that's the `mailable` scope on the model.

Three decisions in there are deliberate and worth not undoing by accident:

**The response never changes.** New address, already waiting, already confirmed — the
browser sees one identical redirect. Membership of a waiting list for parents of
disabled children is itself sensitive, and an endpoint that answers differently for a
known address answers "is this family on the list?" for anyone who asks. What differs
is which email gets sent, and only the mailbox owner sees that.

**Consent wording is copied onto the row, not looked up.** `config/frith.php` holds the
current wording and a version string. Both are written to each signup at the moment of
consent, so editing the config later can't rewrite what somebody actually agreed to.
Bump `frith.consent.version` whenever the wording changes.

**Leaving is one tap, and so is coming back.** Unsubscribe links are signed and never
expire, and work over GET (link in the email) and POST (RFC 8058 one-click, which is
what Gmail and Outlook call). Because mail filters follow links, the unsubscribed page
offers a one-tap undo rather than making unsubscribing harder to guard against it.

Spam is handled by `spatie/laravel-honeypot`, not a CAPTCHA — a CAPTCHA on a page aimed
at exhausted and often neurodivergent parents fails the accessibility bar the brand sets
as a release blocker. A tripped honeypot returns the ordinary success page
(`App\Support\Honeypot\SilentSuccessResponder`) so bots get no signal to tune against.

## The admin panel

Filament, at `/admin`. Two sections: **Website content** and **Waiting list**.

Create an account:

```bash
php artisan frith:admin
```

Two roles, and the split is deliberate. An **owner** sees everything. A **content
editor** can change every word and picture on the site but cannot see the waiting list
at all — the section does not appear in their navigation and the route returns 403.
Whoever looks after the words has no reason to hold the email addresses of families who
signed up. New accounts default to editor.

### How the content works

Page copy lives in two places and the layering matters:

- `config/frith.php` is the floor — the words the page falls back to.
- The `pages` table holds what an editor has actually written, as structured JSON.

`App\Support\PageContent` merges the second over the first and caches the result. If the
table is empty, a key has never been filled in, or a deploy adds a field before anyone
opens the panel, the page still renders sensible words instead of blanks. Lists (the
cards, the bullet points) are replaced outright rather than merged element by element —
otherwise deleting the third card would silently put the default third card back.

The cache clears whenever a page is saved, so an edit shows up immediately.

The consent line under the sign-up form is **not** editable in the panel, and the panel
says so. It is stored word for word against every signup as the record of what was
agreed, so changing it needs a matching version bump in `config/frith.php`.

Seed the initial content on a fresh install with `php artisan db:seed`. It uses
`firstOrCreate`, so re-running it can never overwrite an editor's work.

## Brand

Tokens are transcribed from Brand Guidelines v1.1 §10 into `resources/css/frith.css`.
Primitives are the ramps and should never be referenced from a component; components
use the semantic layer, so a brand colour changes in one place.

Rules that are easy to break and are already correct here:

- Text on coral is **Ink `#1B2940`**, never white — white on Coral 400 is 3.23:1 and
  fails at every size. Coral 400 is never a text colour; coral type is Coral 600.
- Focus ring is 3px Violet 500 with a 3px background-coloured offset.
- Everything in `rem`; body copy never below 16px; targets at least 48×48.

Measured on the built page: Ink on Linen 13.33:1, Linen on Eucalyptus 8.81:1, Coral 600
eyebrow 5.11:1, Coral 200 tagline on Eucalyptus 5.53:1. No horizontal scroll down to a
326px viewport.

Poppins is self-hosted from `public/fonts` (400/500/600, latin + latin-ext). Optima is
display-only and never live text — the wordmark is always placed as vector artwork.

PNG logo exports for email and print live in `public/brand/logo/png` at @1x/@2x/@3x,
generated from the supplied SVG masters.

## Production (Cloudways)

Document root must point at `public/`. Beyond a normal Laravel deploy:

- **`APP_URL` must be correct and https.** Confirmation links are generated in a queue
  worker, which has no request to infer the scheme from. `AppServiceProvider` forces
  https in production; `APP_URL` supplies the host. Get this wrong and every signed link
  breaks.
- **`APP_KEY` must not change** once real signups exist. Rotating it invalidates every
  outstanding confirmation and unsubscribe link.
- **Run a queue worker.** Confirmation emails are queued (`QUEUE_CONNECTION=database`).
  Cloudways has Supervisor under Application Settings — point it at
  `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`. Without a worker,
  nobody's signup is ever confirmed.
- **Set real mail credentials.** `MAIL_MAILER=resend` with `RESEND_API_KEY`, or SMTP.
  `MAIL_FROM_ADDRESS=hello@frith.community`.
- **SPF, DKIM and DMARC on frith.community** before any volume. Gmail and Outlook
  require authenticated bulk mail, and a waiting list is the worst place to discover a
  deliverability problem.
- `php artisan config:cache route:cache view:cache` and `npm run build` on deploy, plus
  `php artisan storage:link` once so uploaded images resolve.
- Trusted proxies are set to `*` in `bootstrap/app.php` so the client IP behind
  Cloudways' load balancer is the real one — rate limiting and consent evidence both
  depend on it.

## MailerLite

The waiting list is mirrored into MailerLite, which is where the launch email
gets written and sent from. Set `MAILERLITE_API_KEY` and `MAILERLITE_GROUP_ID`;
leave them empty and the sync quietly does nothing.

**This database stays the source of truth.** MailerLite is a mirror. A signup that
can't reach MailerLite still succeeds — the job retries, and
`php artisan frith:mailerlite-backfill` catches up any drift. If MailerLite is ever
swapped for something else, one class changes.

Only **confirmed** addresses are ever pushed. An unconfirmed signup hasn't proved the
address belongs to whoever typed it, and unverified addresses must never reach the
thing that does the actual sending. Unsubscribes are marked, not deleted — a deleted
subscriber can be re-added by a later import; an unsubscribed one is a standing
instruction not to.

## Data protection

`waitlist_signups` holds email, consent evidence (wording, version, timestamp, IP, user
agent) and the confirm/unsubscribe state. No other personal data is collected here.

The waiting list is owner-only in the panel, read-only, and deletable — deletion is how
an erasure request gets honoured. The CSV export carries the consent wording and version
alongside each address, so the file is evidence on its own rather than a bare list.

Two things to decide before launch: a retention period for `consent_ip` and
`consent_user_agent` (they're evidence, not analytics — twelve months is a common
choice), and whether unsubscribed rows are deleted or kept as a suppression list. Keeping
them is usually the right call, since deleting means a re-import could email someone who
asked you not to.
