# First run on Cloudways

Once only. After this, `./deploy/deploy.sh` is the whole deploy.

## In the Cloudways control panel

These cannot be done over SSH.

1. **PHP 8.4** — Server Management → Settings & Packages. Laravel 13 needs 8.3+.
2. **Add the domain** — Application → Domain Management.
3. **SSL** — Application → SSL Certificate. Let's Encrypt is fine.
4. **Webroot** — Application Settings → Webroot must be `public_html/public`.
   If it points at `public_html`, the whole codebase including `.env` is served
   over the web. Check this one twice.

## At your DNS provider

Before any real volume of email. A waiting list is a bad place to discover your
mail goes to spam.

- **SPF**, **DKIM** and **DMARC** for `frith.community`, per whatever your mail
  provider (Resend, Brevo, MailerLite) tells you to add.

## Over SSH

```bash
cd /home/master/applications/<app>/public_html
```

**1. Environment.** Copy `.env.example` to `.env` and set:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://frith.community      # the real domain, https, no trailing slash
ASSET_URL=https://frith.community    # see note below
APP_TIMEZONE=Europe/London

DB_DATABASE=...                       # from the Cloudways panel
DB_USERNAME=...
DB_PASSWORD=...

QUEUE_CONNECTION=database

MAIL_MAILER=resend                    # or smtp for Brevo/MailerLite
RESEND_API_KEY=...
MAIL_FROM_ADDRESS="hello@frith.community"
MAIL_FROM_NAME="Frith"
```

`ASSET_URL` is not optional here. Behind Cloudways' Varnish and nginx the
forwarded port leaks into generated URLs, and stylesheets come out as
`https://frith.community:443/build/...`. Pinning it produces clean absolute URLs
whatever the proxy reports.

`APP_URL` matters more than it looks. Confirmation emails are built by the queue
worker, which has no incoming request to read the domain from, so it reads this.
Get it wrong and every link in every email points somewhere unreachable.

**2. Application key.**

```bash
php artisan key:generate
```

Then back the key up somewhere safe and **never change it**. It signs every
confirmation and unsubscribe link. Rotating it after real signups exist leaves
those people permanently unconfirmed, with no way to tell you.

**3. Deploy.**

```bash
chmod +x deploy/deploy.sh
./deploy/deploy.sh
```

**4. Queue worker.** Confirmation emails will not send without one.

Cloudways offers Supervisor under Application Settings, which is the better
option if your plan has it:

```
php /home/master/applications/<app>/public_html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Otherwise a cron entry does the same job without needing root. `crontab -e`:

```
* * * * * cd /home/master/applications/<app>/public_html && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

Either way, confirm it with `php artisan frith:preflight` — it fails loudly if
jobs have been sitting in the queue for more than ten minutes.

**5. Accounts.**

```bash
php artisan frith:admin
```

Roles are `owner` (everything, including the waiting list) and `editor` (content
only, cannot see anyone's email address). Accounts default to editor.

**6. Check it end to end.** Sign up with a real address you control, confirm the
link arrives, click it, and check the signup shows as confirmed at `/admin`.
Nothing else proves the queue worker, the mail credentials and `APP_URL` are all
right at the same time.
