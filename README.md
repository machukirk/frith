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

## Editing the copy

All page text lives in `config/frith.php` under `coming_soon`. That block is shaped to
map onto CMS fields later — nothing in the Blade templates is hard-coded prose.

After editing on production, clear the config cache:

```bash
php artisan config:clear && php artisan config:cache
```

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
- `php artisan config:cache route:cache view:cache` and `npm run build` on deploy.
- Trusted proxies are set to `*` in `bootstrap/app.php` so the client IP behind
  Cloudways' load balancer is the real one — rate limiting and consent evidence both
  depend on it.

## Data protection

`waitlist_signups` holds email, consent evidence (wording, version, timestamp, IP, user
agent) and the confirm/unsubscribe state. No other personal data is collected here.

Two things to decide before launch: a retention period for `consent_ip` and
`consent_user_agent` (they're evidence, not analytics — twelve months is a common
choice), and whether unsubscribed rows are deleted or kept as a suppression list. Keeping
them is usually the right call, since deleting means a re-import could email someone who
asked you not to.
