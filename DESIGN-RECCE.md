# Design recce, 12 November 2026

143 confirmed against the hi-fi.

======================================================================
OBVIOUS  (50)
======================================================================

[home-marketing] / — Journeys grid (the nine Journey cards)  (alignment)
  file   : resources/scss/components/_journeys.scss
  design : display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; first card wrapper grid-row: span 2, card padding 26px 28px, title 18px; the other eight padding 16px 18px, title 14px
  built  : .journeys{grid-template-columns:repeat(auto-fill,minmax(min(100%,240px),1fr));gap:12px} .journeys__item{padding:20px} .journeys__title{font-size:18px at >=1300px}. Measured live at 1440px: five tracks of 262.59px, gap 12
  FIX    : In _journeys.scss: set `.journeys` gap to 14px (was $space-3) and add `@include from(lg) { grid-template-columns: repeat(4, minmax(0, 1fr)); }` below the existing auto-fill base. Change `.journeys__item` padding from $space-5 to `16px 18px`, and `.journeys__title` from `@include type($type-h3, 1.3, 600)` to `@include type($type-small, 1.3, 600)`. Add `&__cell--featured { @include from(lg) { grid-row: span 2; display: flex; } }`, `&__item--featured { flex: 1; padding: 26px 28px; }` and `&__item--featured &__title { @include type($type-h3, 1.3, 600); }`. In /Users/matt/Local Sites/frith/resource

[home-marketing] / — Journey card edge  (missing)
  file   : resources/scss/components/_journeys.scss
  design : background: #FFFFFF; border: 1px solid #DCD8D3; border-radius: 14px
  built  : .journeys__item{background:#fff;border-radius:14px} — computed border-top-width 0px on the live page; no other rule in the compiled CSS matches .journeys__item
  FIX    : In `.journeys__item`, add `border: 1px solid $border-hairline;` directly after the `background: $bg-surface;` line. Supporting claim checks out: `.steps__item`, `.panel` and `.step-list__item` all compile to `border:1px solid #dcd8d3`.

[home-marketing] / — Journey card family markers  (size)
  file   : resources/scss/components/_journeys.scss
  design : 4-6 dots at width/height 7px; border-radius: 50%; background: #E56A4E; gap: 4px; align-items: center, then a +N label at font-size: 10.5px; font-weight: 600; color: #B3432A; margin-left: 3px
  built  : .journeys__faces>*{background:#dff1ef;border:2px solid #fff;border-radius:50%;width:20px;height:20px} .journeys__faces>*+*{margin-inline-start:-8px}; the Blade hardcodes exactly three empty spans on every card (home.blad
  FIX    : Replace the `&__faces` block with: `&__faces { display: flex; align-items: center; gap: 4px; & > * { width: 7px; height: 7px; background: $action-accent; border-radius: $radius-circle; } }` — drop the 2px white border and the -8px overlap. The 10.5px +N label is genuine (it is in the desktop frame, not a thumbnail): add `&__faces-more { margin-left: 3px; color: $coral-600; @include type((10.5px, 10.5px), 1, 600); }`. The count also needs the Blade: in home.blade.php line 100, render `min(6, $journey['families'])` dots and a `+N` span when the count is higher, instead of three fixed spans.

[home-marketing] / — Hero image plate  (radius)
  file   : resources/scss/components/_hero.scss
  design : border-radius: 999px 0 0 999px, with the plate absolutely placed at left 1.89% / top 16.46% / width 98.11% / height 83.54% inside a figure of aspect-ratio 529/407
  built  : .hero__plate{aspect-ratio:461/302;background:#e7e2da;border-radius:14px}. Confirmed visually: a plain rounded rectangle. The PNG itself is fully opaque (alpha 0 across every sampled pixel) with only a soft light vignette
  FIX    : Change `.hero__plate { border-radius: $radius-xl; }` to `border-radius: $radius-pill 0 0 $radius-pill;`. Best done as part of the figure restructure below: give `.hero__figure { aspect-ratio: 529 / 407; overflow: hidden; }` and make `.hero__plate { position: absolute; left: 1.89%; top: 16.46%; width: 98.11%; height: 83.54%; }` in place of its aspect-ratio. One correction to the finding: the aspect ratio 461/302 is right, but because the build's plate fills the whole 470px column rather than 98.11% of a taller figure, it currently renders ~470x308 against the designed ~461x302.

[home-marketing] / — Hero decorative overlay  (missing)
  file   : resources/views/pages/home.blade.php
  design : <img aria-hidden="true" style="position: absolute; left: 2.17%; top: 3.69%; width: 61.53%; height: 95.58%;"> painted over the plate
  built  : nothing — .hero__figure contains only .hero__plate and .hero__dot (home.blade.php lines 31-37)
  FIX    : Add, directly after the `.hero__plate` div and before `.hero__dot`: `<img class="hero__swoosh" src="{{ asset('brand/img/frith-swoosh-hero.svg') }}" alt="" aria-hidden="true">`, with `.hero__swoosh { position: absolute; left: 2.17%; top: 3.69%; width: 61.53%; height: 95.58%; display: block; }` in _hero.scss. The asset identification is solid, not a guess: public/brand/img/frith-swoosh-hero.svg is referenced from nowhere in resources/ or the compiled CSS, its own <desc> reads "Frith hero brushstroke, a decorative arc from the approved hero artwork", and its 326x389 viewBox is a 0.838 ratio again

[registration] /join/you, family, hopes, areas, experiences, interests — Progress bar between "Step N of 6" and the heading  (extra)
  file   : resources/views/components/register/shell.blade.php
  design : no track at all: `<p style="margin: 0 0 14px 0; font-size: 12.5px; color: #5D5A56">Step N of 6</p>` is followed directly by `<h1 style="margin: 0 0 10px 0; ...">` in all six desktop frames (verified on each)
  built  : shell.blade.php lines 17-21 emit `.wizard__track` + `.wizard__bar`; compiled `.wizard__track{background:#e7e2da;border-radius:999px;height:4px;margin-bottom:32px;overflow:hidden}`
  FIX    : Delete the `<div class="wizard__track" role="progressbar">…</div>` block (lines 17-21) from shell.blade.php, and delete the `&__track` and `&__bar` blocks from /Users/matt/Local Sites/frith/resources/scss/layout/_wizard.scss. `.wizard__count`'s own `margin-bottom: 14px` then puts the h1 14px below the count, as drawn. If the progressbar semantics are wanted, put role/aria-valuenow on the existing `.wizard__count` paragraph rather than on a 4px rail.

[registration] /join/hopes — The eight "what are you hoping to find" options  (missing)
  file   : resources/scss/components/_check-list.scss
  design : each option is a card: `background: #FFFFFF; border: 1px solid #DCD8D3; border-radius: 12px; padding: 14px 16px; margin-bottom: 8px; display: flex; gap: 12px; align-items: flex-start`, border `1px solid #274B44` when tic
  built  : `.check-list{gap:4px;display:grid}` and `.check-list__item{grid-template-columns:18px minmax(0,1fr);gap:10px;min-height:48px;padding-block:8px;display:grid}` — no background, border, radius or inline padding, and no chec
  FIX    : The component already takes a `variant` prop. Add to _check-list.scss: `&--cards { gap: $space-2; }` / `&--cards &__item { padding: 14px $space-4; gap: $space-3; background: $bg-surface; border: 1px solid $border-hairline; border-radius: $radius-lg; }` / `&--cards &__item:has(input:checked) { border-color: $action; }`. Then add `variant="cards"` to the `<x-register.check-list name="hopes" …>` call at line 15 of /Users/matt/Local Sites/frith/resources/views/register/hopes.blade.php. Do not put this on the bare `.check-list` — it is also used by `--statements` on /join/experiences (no cards ther

[registration] /join/experiences — Statement rows inside an open area group  (spacing)
  file   : resources/scss/components/_check-list.scss
  design : `display: flex; flex-direction: column; gap: 10px`, each row `font-size: 14px; line-height: 1.5` with a 17px box — row 21px, pitch 31px
  built  : `.check-list{gap:4px}` with `.check-list__item{min-height:48px;padding-block:8px}` (row content is only 37px, so the 48px floor wins) — pitch 52px
  FIX    : Add `&--statements { gap: 10px; }` and `&--statements &__item { min-height: 0; padding-block: 0; }` to _check-list.scss. Four statements then occupy 114px instead of 204px. Note this takes the statement rows below the project's `$target-min` 48px tap floor — it is the design's own 21px row, so it needs Matt's nod before it ships.

[registration] /join/experiences — Open area group: the hairline under the title, and the title-to-note gap  (extra)
  file   : resources/scss/components/_experience-groups.scss
  design : the open card is one div, `background: #FFFFFF; border: 1px solid #DCD8D3; border-radius: 14px; padding: 18px 20px`, no internal rule; the title row is `margin-bottom: 5px`, the note `margin: 0 0 14px 0`. A closed card i
  built  : `.experience-groups__body{border-top:1px solid #e7e2da;padding:0 20px 16px}` and `.experience-groups__note{padding-block:12px 14px}`, with `.experience-groups__summary{min-height:56px;padding-inline:20px}` so the title t
  FIX    : In _experience-groups.scss: delete `border-top: 1px solid $linen-200;` from `&__body` and change its padding to `0 $space-5 18px`; change `&__note` to `padding-block: 0 14px;`; change `&__summary` to `min-height: 0; padding: 17px $space-5;` (a closed card then measures 55px, the design's 53px plus the tap floor) and add `&__item[open] &__summary { padding-block: 18px 5px; }`.

[registration] /join/family — Child 1 / Child 2 cards and their "CHILD N" corner chips  (colour)
  file   : resources/scss/components/_child-rows.scss
  design : the card sets `position: relative; border: 1px solid #DCD8D3; border-radius: 12px; padding: 26px 20px 20px 20px` with NO background, so the frame's #F8F4EE shows through; the chip is `background: #F8F4EE; padding: 0 8px;
  built  : `.child-rows__item{background:#fff;border:1px solid #dcd8d3;border-radius:12px;padding:26px 20px 20px;position:relative}` with `.child-rows__label{background:#f8f4ee;…}`
  FIX    : Delete `background: $bg-surface;` from the `&__item` block in _child-rows.scss. The card then has no fill, the page's $bg-page shows through, and the chip (already $bg-page) becomes invisible against it so only the break in the border reads — which is what the chip's colour was chosen for. The SCSS comment on `&__label` already says this is the intent; the white card is what defeats it.

[auth] /link-sent — The whole block: envelope circle, "Check your email" heading, standfirst, amber note and the button  (alignment)
  file   : resources/scss/components/_auth.scss
  design : <div style="text-align: center;"> wrapping badge, h1, standfirst, note and button
  built  : Measured in the browser at 1440: .auth__inner, .auth__title and .auth__standfirst all compute text-align: start. Nothing in the compiled CSS sets text-align on any .auth class; only .auth__badge{margin:0 auto 18px} centr
  FIX    : Add to the .auth block in _auth.scss: `&__inner--centred { text-align: center; }`. Then in /Users/matt/Local Sites/frith/resources/views/pages/auth/link-sent.blade.php line 10 use `<div class="auth__inner auth__inner--centred">`. /login and /forgot keep the plain .auth__inner, which the design draws left. The hi-fi's desktop frame centres the note's text too; only its mobile thumbnail sets text-align: left on that box, so add `@media (max-width: 47.99em) { .auth__inner--centred .note { text-align: start; } }` if you want the phone to match as well.

[auth] /verified — The whole block: tick circle, "You're all set" heading, standfirst and the button  (alignment)
  file   : resources/scss/components/_auth.scss
  design : <div style="text-align: center;"> wrapping badge, h1, standfirst, note and button
  built  : Same as /link-sent. Measured: .auth__inner and .auth__title compute text-align: start; only the badge is centred by its auto margins
  FIX    : Same rule as /link-sent: `&__inner--centred { text-align: center; }` in _auth.scss, then `<div class="auth__inner auth__inner--centred">` at line 10 of /Users/matt/Local Sites/frith/resources/views/pages/auth/verified.blade.php.

[auth] /verified — The "Still need to complete the details in your profile?" note box  (colour)
  file   : resources/scss/components/_note.scss
  design : background: #F3F1EB; border-left: 3px solid #274B44; border-radius: 4px 12px 12px 4px; padding: 14px 16px; color: #1B2940
  built  : class="note" with no modifier, so .note{color:#00322f;background:#dff1ef;border-radius:10px;padding:16px}: teal fill, no left rule, even corners. Read from verified.blade.php line 17 and the compiled .note rule; it does 
  FIX    : Add a modifier to _note.scss after `&--urgent`: ``` &--aside { background: $linen-075; border-inline-start: 3px solid $action; border-radius: $radius-sm $radius-lg $radius-lg $radius-sm; color: $text-heading; } ``` Then line 17 of /Users/matt/Local Sites/frith/resources/views/pages/auth/verified.blade.php becomes `<p class="note note--aside">`. Those four tokens are exactly #F3F1EB, #274B44, 4px/12px and #1B2940.

[auth] /link-sent — The "Send it again" button, the screen's only action  (missing)
  file   : resources/views/pages/auth/link-sent.blade.php
  design : <span>Send it again</span>, outline pill
  built  : <a class="button button--outline button--block" href="https://localhost/login">Back to log in</a>
  FIX    : Replace the link at lines 34-36 with a POST form to `route('login.send-link')` carrying `@csrf`, `<x-honeypot />` (the route runs ProtectAgainstSpam) and `<input type="hidden" name="email" value="{{ $email }}">`, with `<button class="button button--outline button--block" type="submit">{{ $s['resend'] }}</button>`; add `'resend' => 'Send it again'` to the `link_sent` block in /Users/matt/Local Sites/frith/config/content/auth.php. LoginController::sendLink flashes the address with `->with('link-sent.email', ...)` and re-flashes it on every post, so repeat presses keep working and the existing th

[auth] /verified — The primary button at the bottom  (other)
  file   : resources/views/pages/auth/verified.blade.php
  design : <span>Go to your village</span>, primary pill
  built  : <a class="button button--primary button--block" href="https://localhost">Back to the homepage</a>, from route('home')
  FIX    : Line 24: `href="{{ route('village') }}"`, and in /Users/matt/Local Sites/frith/config/content/auth.php change `verified.button` to 'Go to your village'. The route exists (routes/web.php line 110) and /verified already sits behind auth:founder, so the family is logged in by the time they press it.

[help-plus] /help — Help topic card grid (the six subject cards)  (alignment)
  file   : resources/scss/components/_link-cards.scss
  design : grid-template-columns: repeat(3, 1fr); gap: 16px
  built  : .link-cards{grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:16px}. Measured at 1440 (doc width 1425, .wrap content 1361): "328.25px 328.25px 328.25px 328.25px"
  FIX    : In .link-cards, keep the auto-fit line for small screens and pin three columns from md up: add `@include from(md) { grid-template-columns: repeat(3, minmax(0, 1fr)); }` inside the .link-cards block. gap is already $space-4 (16px) and is correct.

[help-plus] /help — The three contact cards (General questions / Safety concerns / Your data)  (alignment)
  file   : resources/scss/components/_link-cards.scss
  design : display: flex; flex-direction: column; gap: 14px — each card the full width of the left column (~672px), padding 18px 20px
  built  : .link-cards--tight{grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr));gap:12px}. Measured "222.648px 222.648px 222.648px" inside a 691.95px column
  FIX    : Do not change --tight: the search-results list on this same page is the only other user of it and reads correctly as a grid. Add a new modifier to _link-cards.scss: `&--stacked { gap: 14px; grid-template-columns: minmax(0, 1fr); }`. Then in /Users/matt/Local Sites/frith/resources/views/pages/help.blade.php change the contact list (the <ul> inside .split--lead, around line 125) from `class="link-cards link-cards--tight"` to `class="link-cards link-cards--stacked"`. Leave the search-results <ul> on --tight.

[help-plus] /help — Safeguarding note ("If you believe a child is at immediate risk...")  (alignment)
  file   : resources/views/pages/help.blade.php
  design : last child of the left column, directly under the three contact cards, margin-top: 16px, so it shares their left and right edges (~672px wide)
  built  : <p class="note note--panel note--urgent note--standalone"> sits after the closing </div> of .split, as a child of .wrap. Measured x:32 w:1361 against the left column's 691.95px
  FIX    : Wrap the contact list and the note in one <div> so they form a single grid column. Inside `<div class="split split--wide split--lead">`, open a plain `<div>` before the contact `<ul>`, move the `<p class="note note--panel note--urgent note--standalone">…</p>` block (currently after the `</div>` that closes .split, around line 203) to sit directly after that `</ul>`, then close the wrapper before `<div class="panel" id="contact">`. Swap `note--standalone` (margin-top $space-5 = 20px) for a 16px top margin: add `&--under-cards { margin-top: $space-4; }` to /Users/matt/Local Sites/frith/resources

[help-plus] /help — Safeguarding note colours  (colour)
  file   : resources/scss/components/_note.scss
  design : background: #FFD4C9; border-left: 3px solid #963630; color: #5F0F00
  built  : .note--urgent{color:#573b00;background:#fceacf;border-inline-start:3px solid #785300}. Measured rgb(252,234,207) / rgb(120,83,0) / rgb(87,59,0)
  FIX    : Do NOT change .note--urgent. I checked every other build user of it against the hi-fi and the design keeps them mustard: /meet-up-safety, /reporting and /link-sent all draw #FCEACF with a #785300 rule. Only /help (and /error) draw the coral set. Add a token `$coral-900: #5F0F00;` next to $coral-ink in abstracts/_tokens.scss, then add a sibling modifier in _note.scss: `&--alarm { background: $coral-100; border-inline-start: 3px solid $coral-ink; border-radius: $radius-sm $radius-xl $radius-xl $radius-sm; color: $coral-900; }` and `&--alarm &__label { color: $coral-ink; }`. In help.blade.php swa

[help-plus] /help — Page head band: top padding, and the gap it leaves above the topic grid  (spacing)
  file   : resources/scss/components/_page-head.scss
  design : one #F8F4EE band, padding: 64px 0 56px 0, holding the head block and the card grid; the head block carries margin-bottom: 34px, so there is 34px between the search pill and the first row of cards
  built  : .page-head{padding-block:56px 40px} at (width>=768px), then a separate <section class="band band--page band--tight"> with .band--tight{padding-block:48px}. Measured: head band top padding 56px; search pill bottom 320, gr
  FIX    : Two parts. (1) Global top padding: in _page-head.scss change the md rule from `padding-block: $space-14 $space-10;` to `padding-block: $space-16 $space-10;` — the design draws 64px on every page-head band I checked (/about, /how-it-works, /help all 64). (2) The 88px gap: add `&--run-on { padding-block-end: 0; @include from(md) { padding-block-end: 0; } }` to .page-head, and add `&--run-on { padding-block: 34px $space-14; @include from(md) { padding-block: 34px $space-14; } }` to .band in /Users/matt/Local Sites/frith/resources/scss/layout/_wrap.scss. Then in help.blade.php use `<section class=

[village-journeys] /village — The page title "Your village" (and the same double wrap on /journeys, /journeys/{slug}, /messages, /requests)  (alignment)
  file   : resources/views/pages/village/show.blade.php
  design : One container: `width:100%; max-width:1140px; margin:0 auto; padding:34px 0 44px 0` holding the title and everything under it
  built  : `<section class="wrap page-head"><div class="page-head__inner">` — `.wrap` and `.page-head__inner` are both `@include container`, so padding-inline:32px is applied twice. Measured at 1440px: h1 left edge 64px, village ca
  FIX    : Drop `wrap` from the page-head section: `<section class="page-head">` (line 3). Same one-word change at journeys/index.blade.php:3, journeys/show.blade.php:3, messages/index.blade.php:3, requests/index.blade.php:3. The public pages (help, how-it-works, about) already do exactly this.

[village-journeys] /journeys — The page title "Journeys" and its standfirst  (alignment)
  file   : resources/views/pages/journeys/index.blade.php
  design : One container: `max-width:1140px; margin:0 auto; padding:34px 0 44px 0`
  built  : Same double wrap as /village: `<section class="wrap page-head"><div class="page-head__inner">`, title at left 64px against the search field and journey rows at left 32px
  FIX    : Line 3: `<section class="wrap page-head">` → `<section class="page-head">`. Nothing else needed; `.page-head__inner` already carries the container.

[village-journeys] /journeys/autism-assessment — The whole page body: family list plus right-hand rail  (order)
  file   : resources/views/pages/journeys/show.blade.php
  design : `display:flex; gap:48px; align-items:flex-start` with the list at `flex:1; min-width:0` and a rail at `flex:0 0 300px`, inside `max-width:1140px; padding:40px 0 52px 0`
  built  : Single column. `<section class="wrap family-list">` with `.family-list { padding-bottom:64px }`. Measured list width 1361px, no flex, no rail. The only rail content that exists (`.panel-stack__section` → "Not the right J
  FIX    : Wrap the body in a two-column grid. In show.blade.php put `<div class="journey-detail">` around the list and a new `<aside class="journey-detail__rail">` holding the connections card and the "Not the right Journey?" panel. In resources/scss/components/_family-list.scss (or a new _journey-detail.scss): `.journey-detail { display:grid; gap:$space-12; align-items:start; @include from(lg) { grid-template-columns: minmax(0,1fr) 300px; } }` and `.journey-detail__rail { display:grid; gap:14px; }`. Move the `.panel-stack__section` block into the rail.

[village-journeys] /journeys/autism-assessment — The "Your connections here" rail card  (missing)
  file   : resources/views/pages/journeys/show.blade.php
  design : `background:#FFFFFF; border:1px solid #DCD8D3; border-radius:14px; padding:20px 22px; margin-bottom:14px`, with an 11px uppercase eyebrow, "2 families of 7" at 24px/600/-0.02em, 32px-avatar rows at gap 11px, and a 12.5px
  built  : Absent. journeys/show.blade.php renders only `.family-list__head`, `.family-list__items`, `.panel-stack__section` and `.family-list__back` — no element matching it anywhere in the codebase (grep for "connections here" re
  FIX    : Build it as the first card in the new `.journey-detail__rail`. Needs the count of this family's connections inside this journey and their names/connected-at labels from the controller. Styles: `.rail-card { padding:20px 22px; margin-bottom:14px; background:$bg-surface; border:1px solid $border-hairline; border-radius:$radius-xl }`, `.rail-card__eyebrow { @include type((11px,11px),1.3,600); letter-spacing:0.09em; text-transform:uppercase; color:$linen-700; margin-bottom:$space-1 }`, `.rail-card__count { @include type((24px,24px),1.2,600); letter-spacing:-0.02em; margin-bottom:$space-4 }` with t

[village-journeys] /journeys — The "Is something missing?" suggestion panel at the foot of the directory  (missing)
  file   : resources/views/pages/journeys/index.blade.php
  design : `background:#FFFFFF; border:1px solid #DCD8D3; border-radius:16px; padding:26px 28px; margin-top:26px`, a two-column flex with gap 40px: 18px heading, 14.5px/1.65 blurb, a mustard note (`background:#FCEACF; border-left:3
  built  : Absent. index.blade.php ends at `</ul>` then `</section>` after the journey rows. No route, controller action or view for a journey suggestion exists.
  FIX    : Add the panel after the `</ul>` at the end of the journeys section, as a plain POST form (no JS needed, so the no-JS rule is not in the way): a `journey-suggest` component with `.journey-suggest { margin-top:26px; padding:26px 28px; background:$bg-surface; border:1px solid $border-hairline; border-radius:$radius-card }`, an inner `display:flex; gap:$space-10; flex-wrap:wrap`, the blurb column at `flex:1; min-width:300px`, the form column at `flex:0 0 340px`, and the warning reusing/extending `.note` with `background:#FCEACF; border-left:3px solid #785300; border-radius:4px 12px 12px 4px`. Need

[village-journeys] /village — Gap between the page title and "Journeys you have joined"  (spacing)
  file   : resources/views/pages/village/show.blade.php
  design : `<h2 style="margin: 0 0 3px 0; font-size: 34px; ...">Your village</h2>` with the caption directly beneath it — 3px
  built  : 56px, measured. `.page-head__title { margin-bottom:16px }` collapses through `.page-head__inner`, then `.page-head { padding-block:56px 40px }` adds its 40px bottom, and the caption lives in the next `<section>`.
  FIX    : Move the caption into the page head and tighten the title. In village/show.blade.php move line 14 up inside `.page-head__inner`, directly under the `<h1>`, as `<p class="page-head__caption">Journeys you have joined</p>`. In _page-head.scss add `&--app { padding-block: 34px 0; @include from(md) { padding-block: 34px 0; } .page-head__title { margin-bottom: 3px; } }` and put `page-head--app` on the section. Keep the bare `.page-head` padding as it is for the public pages.

[village-journeys] /village — The caption "Journeys you have joined"  (size)
  file   : resources/scss/components/_village.scss
  design : `<p style="margin: 0 0 16px 0; font-size: 13px; color: #4F5B70;">` — a small grey caption
  built  : Measured 18px, #1B2940, weight 600. `.village__heading { margin-bottom:16px; color:$text-heading; @include type((17px,18px),1.3,600) }`
  FIX    : Give it its own class rather than changing `.village__heading`, which also styles "Families you are connected with" and "Families suggested for you" — the design draws those at 17px/600/#1B2940 and they are close to right. In village/show.blade.php line 14 use `<p class="page-head__caption">`, and in _page-head.scss add `&__caption { margin-bottom: $space-4; color: $text-body; @include type((13px, 13px), 1.5); }`. Worth a note in review: this drops an h2 from the outline, which matches the design but changes the heading structure.

[village-journeys] /journeys — The standfirst under the title  (size)
  file   : resources/scss/components/_page-head.scss
  design : `<p style="margin: 0 0 18px 0; font-size: 13px; color: #4F5B70;">`
  built  : 18px at desktop. `.page-head__standfirst { max-width:62ch; @include type((16px,18px),1.7) }`, measured 18px at 1440px
  FIX    : Scope it, do not change the base rule: the design sets 17-18px on /help, /about and /how-it-works, so `.page-head__standfirst` at 18px is correct there. Add `&__standfirst--caption { max-width: none; margin-bottom: 18px; @include type((13px, 13px), 1.5); }` and use `class="page-head__standfirst page-head__standfirst--caption"` at journeys/index.blade.php:6. Leave /journeys/{slug} and /requests alone for now — the design draws those two at 16px, which is a separate 2px gap.

[connecting] /requests — The right-hand rail: the "Requests you have made" counter card with its 10-segment meter, the "Seeing who asked" Frith+ card, and the "Saying no" card  (missing)
  file   : resources/views/pages/requests/index.blade.php
  design : Content area `width: 100%; max-width: 1140px; margin: 0 auto; padding: 40px 0 52px 0; display: flex; gap: 48px; align-items: flex-start;` with main at `flex: 1; min-width: 0` and rail at `flex: 0 0 300px`. Counter card `
  built  : No rail and no two-column layout. The blade emits `<section class="wrap page-head">` then `<section class="wrap family-list">`; `.family-list{padding-bottom:64px}` is a single full-width column.
  FIX    : Wrap the two lists in `<div class="split split--rail">` with the lists in a plain `<div>` and a new `<aside class="requests-rail">` beside them. Add to resources/scss/layout/_split.scss: `&--rail { @include from(lg) { grid-template-columns: minmax(0, 1fr) 300px; gap: $space-12; align-items: start; } }`. Add resources/scss/components/_requests-rail.scss (and `@use 'components/requests-rail';` in main.scss): `.requests-rail__card { padding: 18px 20px; margin-bottom: 14px; background: $bg-surface; border: 1px solid $border-hairline; border-radius: $radius-xl; }`, `.requests-rail__card--count { pa

[connecting] /messages/{id} — Your own message bubbles  (colour)
  file   : resources/scss/components/_thread.scss
  design : `align-self: flex-end; max-width: 78%; background: #274B44; border-radius: 14px 14px 4px 14px; padding: 10px 13px;` with text `font-size: 13px; line-height: 1.55; color: #F8F4EE;`
  built  : .thread__line--mine .thread__bubble{background:#dff1ef;border-color:#d6dfdd;border-radius:14px 14px 4px} — no colour override, so the text stays #1b2940 from .thread__bubble
  FIX    : In `&__line--mine &__bubble` change `background: $euc-050; border-color: $euc-100;` to `background: $euc-700; border-color: $euc-700; color: $text-on-inverse;`. Keep `border-radius: $radius-xl $radius-xl $radius-sm $radius-xl`. ($linen-050 on $euc-700 is 8.8:1.)

[connecting] /f/{id} — The "Ask to connect" button, and the "Say hello back" button on /requests  (colour)
  file   : resources/views/pages/family/show.blade.php
  design : `font-size: 14px; font-weight: 500; color: #1B2940; background: #E56A4E; padding: 12px; border-radius: 999px; display: block; text-align: center;` above the outlined "Not for us". On /requests the same action is `font-si
  built  : `class="button button--primary button--block"` → `.button--primary{color:#f8f4ee;background:#274b44}`, the same green as the `button--outline` directly beneath it. `.button--accent{color:#1b2940;background:#e56a4e}` alre
  FIX    : In the `@default` case change the `value="ask"` submit from `class="button button--primary button--block"` to `class="button button--accent button--block"`. Make the same swap on the "Say hello back" submit in resources/views/pages/requests/index.blade.php (`button button--primary button--small` → `button button--accent button--small`). Leave every other `button--primary` alone, including Block. ($ink-900 on $coral-400 is 4.5:1, and $coral-400 is never used as the text colour, so the brand rule against white-on-coral still holds.)

[connecting] /f/{id}/report — The "what happened" option marks  (radius)
  file   : resources/scss/components/_check-list.scss
  design : A radio circle: `width: 15px; height: 15px; border-radius: 50%; border: 1.5px solid #BDB6AB; background: #FFFFFF;`, the chosen one `border: 4.5px solid #274B44; background: #FFFFFF` — a thick ring, no glyph
  built  : .check-list__mark{background:#fff;border:1.5px solid #938c81;border-radius:5px;width:18px;height:18px} with .check-list__mark:after{content:"✓"} and .check-list__item:has(input:checked) .check-list__mark{color:#f8f4ee;ba
  FIX    : Add a radio-scoped block so the registration tickboxes are untouched: `.check-list__item:has(input[type="radio"]) { grid-template-columns: 15px minmax(0, 1fr); .check-list__mark { width: 15px; height: 15px; border-radius: $radius-circle; &::after { content: none; } } }` and `.check-list__item:has(input[type="radio"]:checked) .check-list__mark { background: $bg-surface; border: 4.5px solid $action; }`. Keep `$border-control` on the unchosen outline — that darkening is the settled deviation.

[connecting] /requests — The "N things in common" and "via <Journey>" chips on a request card  (missing)
  file   : resources/scss/components/_family-list.scss
  design : Two pills in a `display: flex; gap: 7px; flex-wrap: wrap` row: `font-size: 11.5px; font-weight: 500; color: #00322F; background: #DFF1EF; padding: 4px 10px; border-radius: 999px;` and `color: #2D1C57; background: #EDEAFF
  built  : .family-card__meta,.family-card__common{color:#4f5b70;font-size:12px;line-height:1.45;display:block} with .family-card__common{color:#006964}; the blade concatenates the journey onto the same line as "&middot; via {{ $co
  FIX    : In requests/index.blade.php replace the single `family-card__common` span with `<span class="family-card__chips"><span class="family-card__chip">{{ $card->commonality() }}</span>@if ($connection->journey)<span class="family-card__chip family-card__chip--journey">via {{ $connection->journey->title }}</span>@else<span class="family-card__chip family-card__chip--suggested">Suggested by Frith</span>@endif</span>`. Add to _family-list.scss under `.family-card`: `&__chips { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 5px; }`, `&__chip { padding: 4px 10px; background: $euc-050; border-radiu

[connecting] /messages — The unread marker on a conversation row  (missing)
  file   : resources/views/pages/messages/index.blade.php
  design : A dot on the row: `flex: none; width: 7px; height: 7px; border-radius: 50%; background: #E56A4E;`, with the unread family's name at `font-weight: 600` against `font-weight: 500` for a read one. The frame's own caption re
  built  : The blade emits `<p class="family-card__unread">{{ $unread }} new…</p>` and there is no `.family-card__unread` rule anywhere: 0 hits in public/build/assets/main-xaOaQvUj.css and 0 in resources/scss. It falls back to `bod
  FIX    : Replace `<p class="family-card__unread">{{ $unread }} new<span class="visually-hidden">…</span></p>` with `<span class="family-card__unread" aria-hidden="true"></span><span class="visually-hidden">Unread messages from {{ $other->displayName() }}</span>`, and add `family-card--unread` to the `<li>` when `$unread`. Add to _family-list.scss: `&__unread { flex: none; width: 7px; height: 7px; background: $action-accent; border-radius: $radius-circle; }` and `&--unread &__name { font-weight: 600; }`, changing the base `&__name` to `@include type((15px, 15px), 1.3, 500)`. A dot rather than a number a

[account] /account (and /account/badges, /account/notifications, /account/experiences/review, /account/data) — The main content column that holds the cards  (size)
  file   : resources/scss/layout/_account.scss
  design : <div style="flex: 1; min-width: 0; max-width: 660px;"> beside a <div style="flex: 0 0 240px;"> rail with gap: 48px, inside the 1140px container
  built  : .account__body has no rule at all in main-xaOaQvUj.css. Measured width 1081px at a 1440px viewport (1440 - 15 scrollbar - 64 wrap padding - 232 rail - 48 gap).
  FIX    : Add to .account in _account.scss: `&__body { max-width: 660px; }`. The grid's minmax(0,1fr) already supplies the design's min-width: 0. Note the design caps at 660px inside a 1140px container that leaves 192px of slack, so this is not a consequence of the 1440px wrap decision. One scope correction to the reviewer's wording: the designs draw five rail screens, not six. /account/frith is a 620px centred page with no rail and is a deferred Frith+ screen.

[account] /account/data — The "Delete my account" button in the Leave Frith card  (colour)
  file   : resources/scss/components/_button.scss
  design : font-size: 13.5px; font-weight: 500; color: #F8F4EE; background: #963630; padding: 11px 22px; border-radius: 999px
  built  : <a class="button button--quiet button--small"> → measured background transparent, colour #274b44, border 1.5px solid #bdb6ab, padding 11px 22px, font-size 14px. Pixel-identical to the "Request my data" button above it.
  FIX    : Add a modifier to .button in _button.scss: `&--danger { color: $linen-050; background: $coral-ink; border-color: transparent; }` ($coral-ink is already #963630 in abstracts/_tokens.scss, line 25). Then in resources/views/pages/account/data.blade.php change the Delete my account link's classes from `button button--quiet button--small` to `button button--danger button--small`. #F8F4EE on #963630 is 6.8:1, so there is no accessibility reason for the current treatment. Confirmed there is no existing filled danger variant: _button.scss has only --primary, --accent, --quiet, --outline, --small, --bl

[account] /account/badges — The circular icon medallion on the left of every badge row  (missing)
  file   : resources/scss/components/_badges.scss
  design : <span style="flex: none; width: 52px; height: 52px; border-radius: 50%; background: #D6DFDD; display: flex; align-items: center; justify-content: center; position: relative;"> holding a 26px stroked SVG at stroke #274B44
  built  : .badge-list__item{background:#fff;border:1px solid #dcd8d3;border-radius:14px;padding:16px 20px} and measured display: list-item. No medallion element in badges.blade.php and no medallion rule anywhere in the compiled CS
  FIX    : In _badges.scss set `.badge-list__item { display: flex; gap: 16px; align-items: flex-start; }` and add `.badge-list__medal { flex: none; width: 52px; height: 52px; border-radius: 50%; background: #D6DFDD; display: grid; place-items: center; position: relative; color: #274B44; }`, `.badge-list__item--not-yet .badge-list__medal { background: #F0ECE6; color: #8F8C87; }`, `.badge-list__dot { position: absolute; right: -1px; bottom: -1px; width: 15px; height: 15px; border-radius: 50%; background: #E56A4E; border: 2px solid #F8F4EE; }`. Then in resources/views/pages/account/badges.blade.php wrap the

[account] /account/notifications — The on/off toggle in each notification row  (alignment)
  file   : resources/scss/components/_switch.scss
  design : Row is <div style="display: flex; gap: 16px; align-items: center; padding: 15px 0;"> with the text block <div style="flex: 1;"> FIRST and the toggle <span style="flex: none; width: 40px; height: 23px; border-radius: 999p
  built  : .switch{cursor:pointer;align-items:flex-start;gap:12px;display:flex} with .switch__mark emitted before .switch__text. Measured: card content starts at x=330, mark occupies 331-375, text starts at 387, i.e. the label begi
  FIX    : In _switch.scss change .switch to `{ display: flex; gap: 16px; align-items: center; justify-content: space-between; }` and add `.switch__text { order: -1; flex: 1; }` plus `.switch__mark { order: 1; margin-top: 0; }`. (Reordering the spans in the blades works too, but the CSS fix covers notifications.blade.php and badges.blade.php in one place.) The same fault shows on the badges screen, where the design puts the Helping hand toggle to the right of the text and the build stacks it below and left.

[account] /account — The list of profile sections (Name and area, Your children, and the rest)  (other)
  file   : resources/scss/components/_profile-sections.scss
  design : One card: <div style="background: #FFFFFF; border: 1px solid #DCD8D3; border-radius: 14px; padding: 8px 22px; margin-bottom: 14px;"> containing rows of <div style="display: flex; gap: 16px; align-items: center; padding: 
  built  : .section-list{gap:10px;display:grid} + .section-list__item{background:#fff;border:1px solid #dcd8d3;border-radius:14px;padding:14px 18px} — six separate bordered cards with 10px of page background between them
  FIX    : In _profile-sections.scss change .section-list to `{ display: block; margin: 0; padding: 8px 22px; list-style: none; background: $bg-surface; border: 1px solid $border-hairline; border-radius: 14px; margin-bottom: 14px; }` and .section-list__item to `{ display: flex; gap: 16px; align-items: center; padding: 15px 0; background: none; border: 0; border-bottom: 1px solid $linen-100; border-radius: 0; }` with `&:last-child { border-bottom: 0; }`.

[account] /account/notifications — The block of notification switches  (other)
  file   : resources/scss/components/_switch.scss
  design : One card: <div style="background: #FFFFFF; border: 1px solid #DCD8D3; border-radius: 14px; padding: 8px 22px; margin-bottom: 14px;"> with rows at padding: 15px 0; border-bottom: 1px solid #F0ECE6, last row without the ru
  built  : .switch-list{gap:10px;margin:20px 0 0;display:grid} + .switch-list__item{background:#fff;border:1px solid #dcd8d3;border-radius:14px;padding:14px 18px} — measured 5 standalone cards with a 10px gap
  FIX    : In _switch.scss change .switch-list to `{ display: block; margin: 30px 0 14px; padding: 8px 22px; list-style: none; background: $bg-surface; border: 1px solid $border-hairline; border-radius: 14px; }` and .switch-list__item to `{ padding: 15px 0; background: none; border: 0; border-bottom: 1px solid $linen-100; border-radius: 0; }` with `&:last-child { border-bottom: 0; }`. The quiet-hours card in the design is a second such card at padding: 8px 22px 18px 22px.

[account] /account — The six-segment profile completeness bar inside the progress card  (missing)
  file   : resources/views/pages/account/profile.blade.php
  design : <div style="display: flex; gap: 4px;"> holding six <span style="height: 4px; flex: 1; background: #274B44; border-radius: 2px;">, the unearned ones background: #DCD8D3
  built  : resources/views/pages/account/profile.blade.php lines 33-37 render only .account-progress__tally and .account-progress__note. No bar element, and no .account-progress__bar or __seg rule anywhere in the compiled styleshee
  FIX    : In profile.blade.php, inside the .account-progress div after the note, emit `<div class="account-progress__bar" aria-hidden="true">` with one `<span class="account-progress__seg">` per section, adding `account-progress__seg--done` for the completed ones. Then in _profile-sections.scss add `.account-progress__bar { display: flex; gap: 4px; margin-top: 14px; }` and `.account-progress__seg { height: 4px; flex: 1; border-radius: 2px; background: $border-hairline; &--done { background: $euc-700; } }`.

[account] /account/data — The Blocked families block  (missing)
  file   : resources/views/pages/account/data.blade.php
  design : <div style="background: #FFFFFF; border: 1px solid #DCD8D3; border-radius: 14px; padding: 20px 22px; margin-bottom: 14px;"> as the third card in the stack, heading font-size: 15px; font-weight: 600, body font-size: 13.5p
  built  : <section class="panel-stack__section" id="blocked"> → measured background transparent, no border, no radius, no padding, margin-top 40px, heading 17px (.account__subhead), body 14px (.account__subnote)
  FIX    : In data.blade.php move the `#blocked` section inside the existing `.panel-stack` div and give it `<section class="panel" id="blocked">`, swapping `account__subhead` for `panel__title` and `account__subnote` for `panel__body`. .panel already renders white with a 1px #dcd8d3 border and a 15px/600 title; set `.panel { border-radius: 14px; padding: 20px 22px; }` in _panel.scss to match the design's 14px/20px 22px rather than the current 16px/20px 24px.

[edges] /messages (no messages) — The empty state as a whole  (alignment)
  file   : resources/views/pages/messages/index.blade.php
  design : no card: inner column `max-width: 520px; margin: 0 auto; text-align: center; padding: 40px 0` sitting directly on the linen page background
  built  : .village__empty{color:#4f5b70;background:#fff;border:1px solid #dcd8d3;border-radius:16px;padding:24px;font-size:15px;line-height:1.6} inside `section.wrap family-list`, so it fills the 1440px wrap less 64px of gutters (
  FIX    : Rebuild the empty branch on the .outcome component instead of .village__empty. Replace the whole `<div class="village__empty">` block with `<section class="wrap outcome">` holding `<span class="outcome__mark">` (icon), `<h1 class="heading outcome__title">Nothing here yet</h1>`, `<p class="outcome__body">`, and `<div class="outcome__actions">` with two full-size pills: `button button--primary` and `button button--outline`. Drop `button--small` from both, the design draws them at full size. The white card, its 1px #dcd8d3 border and its 16px radius all go.

[edges] /error (500) — The whole message column  (alignment)
  file   : resources/scss/components/_outcome.scss
  design : inner column `max-width: 560px; margin: 0 auto; padding: 30px 0` with no text-align, so heading, body, note and button all sit flush left; the actions div has no justify-content
  built  : .outcome{text-align:center;padding-block:64px 80px} and .outcome__actions{justify-content:center}
  FIX    : Do not change the base, /oops and the three empty states really are centred. Add a modifier in _outcome.scss: `&--start { text-align: start; padding-block: 70px 82px; max-width: 560px + ($gutter-lg * 2); .outcome__actions { justify-content: flex-start; } }` (70/82 is the design's outer 40/52 plus inner 30/30). Then in resources/views/errors/500.blade.php and 503.blade.php use `class="wrap outcome outcome--start"`. `.outcome__note{text-align:start}` becomes redundant but is harmless.

[edges] /messages (no messages) — "Nothing here yet" title  (size)
  file   : resources/views/pages/messages/index.blade.php
  design : `<h1 style="margin: 0 0 10px 0; font-size: 32px; font-weight: 600; letter-spacing: -0.022em; line-height: 1.18; color: #1B2940;">`
  built  : `<p class="village__empty-title">` styled .village__empty-title{color:#1b2940;margin-bottom:8px;font-size:16px;font-weight:600;line-height:1.3}, resolving to 17px from 1300px up
  FIX    : Use `<h1 class="heading outcome__title">Nothing here yet</h1>` in place of `<p class="village__empty-title">`, as part of moving the block onto .outcome. That gives it the 32px size set in the .outcome__title fix below, and makes the empty state's own heading the page's h1.

[edges] /messages (no messages) — "Messages" page title above the empty state  (extra)
  file   : resources/views/pages/messages/index.blade.php
  design : the frame goes straight from the 72px header into the centred empty state, with no page title (the populated /messages frame has no page title either)
  built  : `<section class="wrap page-head"><h1 class="heading page-head__title">Messages</h1></section>` with .page-head{padding-block:56px 40px} and .page-head__title{font-size:40px;margin-bottom:16px}
  FIX    : Move the `<section class="wrap page-head">` block inside the `@else` branch, so it only renders when there are conversations. On the empty branch the empty state replaces the page rather than nesting under a 40px heading.

[edges] /messages (no messages) — Empty-state icon  (missing)
  file   : resources/views/components/site/icon.blade.php
  design : 68px circle `background: #D6DFDD` holding a 30x30 speech-bubble svg, `stroke: #274B44; stroke-width: 1.6`; /village has a map pin and /journeys a calendar in the same plate
  built  : no icon in the markup, and resources/views/components/site/icon.blade.php carries only people, leaf, search, star and shield
  FIX    : Add the three paths, lifted from the hi-fi svgs, to the $paths array: 'speech' => '<path d="M4 6h16v10H9l-5 4V6Z"/>', 'pin' => '<path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>', 'calendar' => '<rect x="4" y="5.5" width="16" height="14" rx="3"/><path d="M8 3.5v4M16 3.5v4M4 11h16"/>'. Then in each empty state: `<span class="outcome__mark" aria-hidden="true"><x-site.icon name="speech" width="30" height="30"/></span>`.

[edges] /oops (404) — "Email us about it" secondary action  (missing)
  file   : resources/views/errors/404.blade.php
  design : an outlined pill: `font-size: 15px; font-weight: 500; color: #274B44; border: 1.5px solid #274B44; padding: 13px 28px; border-radius: 999px`
  built  : .outcome__quiet{color:#5d5a56;min-height:44px;font-size:14px;line-height:1.3;display:inline-flex}, a plain grey text link
  FIX    : Change the second action from `class="outcome__quiet"` to `class="button button--outline"`. .button--outline already ships as color:#274b44 with a 1.5px #274b44 border, which is the drawn treatment. Once nothing uses it, delete the `&__quiet` block from resources/scss/components/_outcome.scss.

[edges] /error (500) — The "we have kept it" reassurance box  (colour)
  file   : resources/scss/components/_note.scss
  design : `background: #FFD4C9; border-left: 3px solid #963630; border-radius: 4px 14px 14px 4px`, text `color: #5F0F00`
  built  : .note{color:#00322f;background:#dff1ef;border-radius:10px}, the teal note, with no left rule
  FIX    : Add a coral variant alongside --urgent rather than touching the base, which the policy pages rely on. First add `$coral-900: #5F0F00;` to resources/scss/abstracts/_tokens.scss (the ink does not exist yet). Then in _note.scss: `&--alert { background: $coral-100; border-inline-start: 3px solid $coral-ink; border-radius: $radius-sm $radius-xl $radius-xl $radius-sm; color: $coral-900; }`. In resources/views/errors/500.blade.php the div becomes `class="note note--alert note--standalone outcome__note"`.

[edges] /error (500) — Site header and footer  (missing)
  file   : resources/views/errors/500.blade.php
  design : the frame draws the full public header (wordmark, Home / How it works / Safety, Log in, Register, `height: 72px`) and the dark footer (`background: #03221D; padding: 48px 0 26px 0`) with all four link columns
  built  : resources/views/errors/500.blade.php is a standalone document with `<main id="main">` only: no <x-layouts.site>, no <x-site.header>, no <x-site.footer>. 503.blade.php is the same.
  FIX    : The template's no-lookup rule is real: <x-site.header> calls SiteNavigation::primary() and auth('founder'), and <x-site.footer> calls SiteNavigation::footer() and PageContent::for('footer'), all of which can be the thing that just failed. So do not simply wrap it in <x-layouts.site>. Inline a static chrome in the template instead: a `<header class="site-header">` with the wordmark linking to /, the three public links and the Log in / Register pills written as literal markup, and a `<footer class="site-footer">` with the four column groups hard-coded. No config, no PageContent, no database. Wor

======================================================================
NOTICEABLE  (49)
======================================================================

[home-marketing] / — Row under the Journeys grid ("These are all nine…" + "See who is in each Journey")  (missing)
  file   : resources/scss/components/_journeys.scss
  design : margin-top: 24px; padding-top: 20px; border-top: 1px solid #DCD8D3; align-items: baseline; gap: 22px; with flex: 1 on the paragraph
  built  : .journeys-footer{flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;margin-top:24px} — computed border-top-width 0px, padding-top 0px, align-items center on the live page. The `.section-note` inside
  FIX    : In `.journeys-footer`: change `align-items: center` to `align-items: baseline`, change `gap: $space-3` to `gap: 22px`, and add `padding-top: 20px; border-top: 1px solid $border-hairline;`. Also add `.section-note { flex: 1; margin-top: 0; }` inside the block — otherwise the nested .section-note's own 24px margin-top stacks on the footer's 24px and the row starts 48px below the grid instead of 24px.

[home-marketing] / — Site header, bottom edge  (missing)
  file   : resources/scss/layout/_site-header.scss
  design : the wrapper around the header bar is <div style="background: #F8F4EE; border-bottom: 1px solid #DCD8D3;">
  built  : .site-header{z-index:50;background:#f8f4ee;position:sticky;top:0} — no border-bottom in _site-header.scss or anywhere in the compiled CSS. Confirmed visually at 1440px: the bar runs straight into the hero with nothing be
  FIX    : In `.site-header`, add `border-bottom: 1px solid $border-hairline;` beside the existing `background: $bg-page;`. Note this shows on every screen, so check it against the `:has(.menu__disclosure[open])` dark state — the rule will want to go to `$euc-750` there, or be suppressed.

[home-marketing] / — Header "Log in"  (missing)
  file   : resources/scss/layout/_site-header.scss
  design : font-weight: 500; color: #274B44; border: 1.5px solid #274B44; padding: 8px 16px; border-radius: 999px
  built  : .site-header__link--strong{color:#274b44;font-weight:500} — a plain text link, no border, padding or radius. Confirmed visually: Log in is bare text next to the filled Register pill.
  FIX    : Extend `&--strong` to `{ padding: 8px 16px; border: 1.5px solid $action; border-radius: $radius-pill; color: $action; font-weight: 500; }`. Be aware `.site-header__link` carries `@include tap-target(40px)`, so the pill will sit at 40px tall rather than the design's ~34px; that is the build's own target floor, not a design miss.

[home-marketing] / — Pillar icons on the green band  (size)
  file   : resources/scss/components/_pillars.scss
  design : width="40" height="40" stroke-width="1.5" stroke="#FFB19E"; margin-bottom: 18px
  built  : .pillars__icon{color:#ffb19e;width:28px;height:28px;margin-bottom:16px}; the SVG carries stroke-width="1.6" and no width/height attributes, so the CSS 28px wins
  FIX    : In `.pillars__icon`, change `width: 28px; height: 28px;` to `width: 40px; height: 40px;` and `margin-bottom: $space-4` to `margin-bottom: 18px`. Change `stroke-width="1.6"` to `stroke-width="1.5"` in /Users/matt/Local Sites/frith/resources/views/components/site/icon.blade.php. The colour is already right (currentColor resolves to #FFB19E).

[home-marketing] / — Third pillar icon ("Checked before they can message")  (other)
  file   : resources/views/components/site/icon.blade.php
  design : <path d="M12 20.3C7 16.8 3.8 14 3.8 10.3A4.4 4.4 0 0 1 12 7.9a4.4 4.4 0 0 1 8.2 2.4c0 3.7-3.2 6.5-8.2 10Z"> — a heart
  built  : <path d="M12 3.2 5 6v5.4c0 4.6 2.9 7.9 7 9.4 4.1-1.5 7-4.8 7-9.4V6l-7-2.8Z"/><path d="m9 12 2.2 2.2L15.5 10"/> — a shield with a tick, from the 'shield' case of the icon component
  FIX    : The icon is not inline in home.blade.php as reported — it comes from `<x-site.icon :name="$pillar['icon']" />`. Add a case to the icon map in /Users/matt/Local Sites/frith/resources/views/components/site/icon.blade.php: `'heart' => '<path d="M12 20.3C7 16.8 3.8 14 3.8 10.3A4.4 4.4 0 0 1 12 7.9a4.4 4.4 0 0 1 8.2 2.4c0 3.7-3.2 6.5-8.2 10Z"/>',` then change `'icon' => 'shield'` to `'icon' => 'heart'` at line 32 of /Users/matt/Local Sites/frith/config/content/home.php. Pillars 1 and 2 are drawn with arcs rather than the design's curves but render as the same marks, so leave those alone.

[home-marketing] / — Hero headline "Find your people. Find your village."  (spacing)
  file   : resources/scss/components/_hero.scss
  design : margin: 0 0 16px 0; font-size: 58px; line-height: 1.08; letter-spacing: -0.025em
  built  : .hero__headline{margin-bottom:20px}; .heading--hero{letter-spacing:-.01em} (font-size and line-height already match)
  FIX    : In _hero.scss, change `.hero__headline { margin-bottom: $space-5; }` to `margin-bottom: $space-4;` (16px). In /Users/matt/Local Sites/frith/resources/scss/base/_typography.scss, change `&--hero { letter-spacing: -0.01em; }` to `letter-spacing: -0.025em;`.

[home-marketing] / — Hero standfirst paragraph  (size)
  file   : resources/scss/components/_hero.scss
  design : margin: 0 0 28px 0; max-width: 26em (and the small note below it at max-width: 30em)
  built  : .hero__standfirst{max-width:30em;margin-bottom:24px}; .hero__note{max-width:28em}
  FIX    : `.hero__standfirst { max-width: 26em; margin-bottom: 28px; }` (was 30em / $space-6) and `.hero__note { max-width: 30em; }` (was 28em). Not quite a straight swap as reported — the note's build value is 28em, not the standfirst's designed 26em — but both caps are wrong in the direction stated. At 18px the standfirst currently runs 540px against a designed 468px.

[home-marketing] / — Hero coral dot  (alignment)
  file   : resources/scss/components/_hero.scss
  design : position: absolute; left: 71.4%; top: 8.2%; width: 7.1%; aspect-ratio: 1/1 — about 33px, overlapping the plate's top edge roughly 101px in from the figure's right side, clipped by the figure's overflow: hidden
  built  : .hero__dot{width:33px;height:33px;top:-14px;right:-6px}, at >=768px {width:40px;height:40px;top:-16px;right:-12px}. Confirmed visually at 1440px: a 40px disc hanging off the top-right corner, clear of the picture.
  FIX    : Replace the `.hero__dot` offsets with `left: 71.4%; top: 8.2%; right: auto; width: 7.1%; height: auto; aspect-ratio: 1 / 1;` and delete the `@include from(md)` override that grows it to 40px. This needs the `.hero__figure { aspect-ratio: 529 / 407; }` change from the plate fix above, otherwise the percentages have no sized box to resolve against.

[home-marketing] / — Hero Register button  (size)
  file   : resources/scss/components/_button.scss
  design : font-size: 14px; font-weight: 500; padding: 12px 24px; border-radius: 999px, in a row with gap: 10px
  built  : .button{min-height:48px;padding:14px 34px;font-size:15px;font-weight:500}; .hero__actions{gap:12px}
  FIX    : In `.button`, change `padding: 14px 34px` to `padding: 12px 24px` and `font-size: 15px` to `font-size: 14px`. In /Users/matt/Local Sites/frith/resources/scss/components/_hero.scss, change `.hero__actions { gap: $space-3; }` to `gap: 10px;`. `.button` is the site-wide primary, so this lands on every screen — and `@include tap-target` keeps min-height at 48px, which the designed 12px padding on 14px type would otherwise undercut.

[home-marketing] / — Section standfirst (the paragraph under each h2)  (size)
  file   : resources/scss/components/_section-head.scss
  design : font-size: 16px; line-height: 1.68
  built  : .section-head__standfirst{font-size:16px} base, {clamp(16px,4vw,18px)} at >=768px, {font-size:18px} at >=1300px — the SCSS uses $type-lead (16px, 18px), not $type-body (15px, 16px)
  FIX    : Change `@include type($type-lead, 1.68)` to `@include type($type-body, 1.68)` in `.section-head__standfirst`. That gives 16px at desktop as drawn. Note it also drops the mobile value from 16px to 15px, which the hi-fi cannot adjudicate.

[registration] /join/experiences — Label colour of a ticked statement  (colour)
  file   : resources/scss/components/_check-list.scss
  design : ticked rows `color: #1B2940`, unticked rows `color: #4F5B70`
  built  : `.check-list--statements .check-list__label{color:#4f5b70}` with no checked variant — only `.check-list__item:has(input:checked) .check-list__mark` changes, so every label stays the same grey-blue
  FIX    : Add to _check-list.scss, after the `&--statements &__label` block: `&--statements &__item:has(input:checked) &__label { color: $ink-900; }`

[registration] /join/areas — Gap between "Continue" and "Skip this section"  (spacing)
  file   : resources/scss/layout/_wizard.scss
  design : `display: flex; gap: 20px; align-items: center`
  built  : `.wizard__actions{flex-wrap:wrap;align-items:center;gap:12px;display:flex}` (`gap: $space-3`)
  FIX    : In `.wizard__actions` in _wizard.scss change `gap: $space-3;` to `gap: $space-5;`. /join/areas is the only screen with two children in this row, so nothing else moves.

[auth] /login — The "or" divider between the Log in button and "Email me a link instead"  (spacing)
  file   : resources/scss/components/_auth.scss
  design : Log in button margin-bottom 18px, divider margin-bottom 18px, so 18px above and 18px below
  built  : Measured: 32px above the divider, 28px below. .auth__divider{margin-block:18px 8px} inside .auth__form{gap:14px}, with .auth__form>.button{margin-block-start:6px} hitting both buttons
  FIX    : In _auth.scss change `&__divider { margin-block: 18px $space-2; }` to `margin-block: 4px;`, and change `&__form { > .button { margin-block-start: 6px; } }` to `> .button:first-of-type { margin-block-start: 6px; }` so the 6px stops reaching the button under the rule. With the form's 14px grid gap that gives 18px above and 18px below, as drawn.

[auth] /forgot — Gap between the submit button and the "Back to log in" button under it  (spacing)
  file   : resources/scss/components/_auth.scss
  design : <div style="height: 12px;"></div> between the two buttons
  built  : Measured 32px. .auth__foot{margin-top:32px;padding-top:20px;border-top:1px solid #dcd8d3} with .auth__foot--action{padding-top:0;border-top:0}; the 32px margin is never reset
  FIX    : In _auth.scss give the modifier a margin of its own: `&--action { margin-top: $space-5; padding-top: 0; border-top: 0; }` (20px, which /link-sent and /verified want), and add `&--tight { margin-top: $space-3; }` under `&__foot` for this screen's 12px. Then line 28 of /Users/matt/Local Sites/frith/resources/views/pages/auth/forgot.blade.php becomes `<div class="auth__foot auth__foot--action auth__foot--tight">`.

[auth] /login — The "Not registered yet? Register" line and the hairline rule above it  (extra)
  file   : resources/views/pages/auth/login.blade.php
  design : Nothing. The design's /login column ends at the note after "Email me a link instead"
  built  : <p class="auth__foot"> with .auth__foot{border-top:1px solid #dcd8d3;margin-top:32px;padding-top:20px}. Measured 32px of margin plus a 43px block, so 75px of extra column below the note
  FIX    : Delete the `<p class="auth__foot"> ... </p>` block at lines 56-59 of login.blade.php. The global header, which the build keeps on this screen by decision, already carries Register. The `no_account` and `register` keys in /Users/matt/Local Sites/frith/config/content/auth.php become unused, so drop them too and check tests/Feature/EditableCopyTest.php still passes.

[auth] /login — "Email address" and "Password" field labels  (size)
  file   : resources/scss/components/_auth.scss
  design : font-size: 12px; font-weight: 500; color: #1B2940
  built  : Measured 15px. .field__label{color:#1b2940;font-size:15px;font-weight:500;line-height:1.3}, against .auth__aside-link ("Forgotten it?") which measures 12px in the same row
  FIX    : Do not change .field__label in _field.scss: the hi-fi draws 15px labels on /join/you and 14px on /join/family, which is what the shared component is tuned to. Scope it instead, in _auth.scss inside .auth: `.field__label { font-size: 12px; }`. That fixes /login and /forgot together and leaves the wizard alone.

[auth] /forgot — "Email address" field label  (size)
  file   : resources/scss/components/_auth.scss
  design : font-size: 12px; font-weight: 500; color: #1B2940
  built  : Measured 15px, from the same .field__label rule
  FIX    : Covered by the same scoped rule: `.auth { .field__label { font-size: 12px; } }` in _auth.scss. No change to _field.scss.

[help-plus] /help — All cards: corner radius and padding  (radius)
  file   : resources/scss/components/_link-cards.scss
  design : topic cards: border-radius: 14px; padding: 22px 24px — contact cards: border-radius: 14px; padding: 18px 20px
  built  : .link-cards__item{border-radius:16px;padding:20px 24px} for both. Measured borderRadius 16px, paddingTop 20px, paddingLeft 24px on the topic card and on the contact card
  FIX    : In .link-cards__item change `border-radius: $radius-card;` to `border-radius: $radius-xl;` (14px) and `padding: $space-5 $space-6;` to `padding: 22px $space-6;`. Then give the small card its own size next to the --stacked modifier: `&--stacked &__item { padding: 18px $space-5; }`. 14px is the design's card radius almost everywhere (dozens of instances against nine at 16px, all of which are larger panels), so the radius change is safe beyond this screen.

[help-plus] /help — "Send us a message" panel  (spacing)
  file   : resources/scss/components/_panel.scss
  design : border-radius: 16px; padding: 28px 30px; title font-size: 17px
  built  : .panel{border-radius:16px;padding:20px 24px} with .panel__title{font-size:16px} at (width>=1300px). Measured paddingTop 20px, paddingLeft 24px, title 16px
  FIX    : The radius is right; only the inside air is wrong. .panel is used on eight views, so scope it: add `&--roomy { padding: 28px 30px; }` and `&--roomy &__title { @include type((16px, 17px), 1.3, 600); }` to _panel.scss, and use `class="panel panel--roomy"` on the contact panel in help.blade.php. (For reference, the design's other 16px-radius panels sit at 24px 26px, so a global bump is also defensible, but 28px 30px is what /help draws.)

[help-plus] /help — Search box width  (size)
  file   : resources/scss/components/_field.scss
  design : max-width: 560px; padding: 14px 20px; gap: 11px
  built  : .search{max-width:520px;gap:12px;padding-inline:20px}. Measured width 520px, gap 12px
  FIX    : In .search change `max-width: 520px;` to `max-width: 560px;` and `gap: $space-3;` (12px) to `gap: 11px;`. The vertical padding is carried by .search__input's min-height:48px rather than padding on the pill, which lands at the same 51px outer height the design's 14px padding gives; leave that alone.

[help-plus] /help — "Search help" submit button inside the search pill  (extra)
  file   : resources/views/pages/help.blade.php
  design : the pill holds a magnifier svg and the placeholder text only, with no button
  built  : <button class="button button--primary button--small search__submit">Search help</button>. Measured 122.13px wide inside a 520px pill, so the typing area is ~350px against the design's ~524px
  FIX    : Keep a submit (a form with one text input does submit on Enter, but there must be a clickable control), and make it the icon rather than a 122px slab. In help.blade.php move the existing magnifier <svg> inside the <button>, keep the visually-hidden label text as the button's accessible name, and drop the visible "Search help" string. Add to _field.scss: `.search__submit { flex: none; width: 40px; min-width: 0; padding: 0; margin-inline-end: -8px; }`. With the 560px pill from the width fix, that returns the typing area to ~480px.

[help-plus] /help — Message textarea  (size)
  file   : resources/scss/components/_field.scss
  design : height: 108px (border-box: the hi-fi sets a single global box-sizing: border-box)
  built  : 149px. .field__control--multiline{min-height:7em} computes to 105px, so the height actually comes from rows="5": 5 x 24px line-height + 26px padding + 3px border
  FIX    : Their builtValue blames min-height; the live driver is the rows attribute. Change `rows="5"` to `rows="3"` on the contact textarea in /Users/matt/Local Sites/frith/resources/views/pages/help.blade.php, and in _field.scss change `&--multiline { min-height: 7em; }` to `min-height: 108px;`. rows=3 gives 101px intrinsic, the min-height lifts it to exactly 108px, and the box stays resizable.

[help-plus] /help — Headings: the h1 and the "Write to a person" h2  (size)
  file   : resources/scss/base/_typography.scss
  design : h1 40px / letter-spacing: -0.025em; h2 30px / letter-spacing: -0.022em
  built  : .heading{font-weight:600} with no letter-spacing; only .heading--hero sets -0.01em. Measured h1 letterSpacing "normal", h2 letterSpacing "normal"
  FIX    : Add `letter-spacing: -0.022em;` to `.heading--section` in _typography.scss, and `letter-spacing: -0.025em;` to `.page-head__title` in /Users/matt/Local Sites/frith/resources/scss/components/_page-head.scss. Both are the design's consistent values, not one-offs: across the hi-fi the 40px h1 is -0.025em in 8 of 9 frames and the 30px h2 is -0.022em in 10 of 14.

[help-plus] /help — Contact form field labels and controls  (size)
  file   : resources/scss/components/_field.scss
  design : label font-size: 13px; font-weight: 500; margin-bottom: 6px — control padding: 12px 14px
  built  : .field__label{font-size:15px;font-weight:500} with .field{gap:7px}, and .field__control{padding:13px 16px}. Measured label 15px, field row-gap 7px, control paddingTop 13px paddingLeft 16px
  FIX    : The 15px control font-size is on the deliberate list and they correctly left it alone; the label and the padding are not. .field__label and .field__control are used by the registration wizard too, so scope it: add to _field.scss `.contact-form { .field { gap: 6px; } .field__label { @include type((14px, 13px), 1.3, 500); } .field__control { padding: 12px 14px; } }`. Worth noting the design draws a 13px/6px label on most forms (11 desktop instances against 4 at 14px and 3 at 15px), so making it global is a reasonable separate call.

[help-plus] /help — "What is it about?" topic pills  (size)
  file   : resources/scss/components/_field.scss
  design : font-size: 13px; padding: 8px 15px; background: #FFFFFF when unselected; container gap: 7px (giving a ~34px pill)
  built  : .topic-choices__item{background:#f8f4ee;min-height:40px;padding-inline:16px;font-size:14px} with .topic-choices{gap:8px}. Measured 40px tall, backgroundColor rgb(248,244,238), gap 8px
  FIX    : In .topic-choices__item change `background: $bg-page;` to `background: $bg-surface;` (#fff), `min-height: 40px` to `min-height: 34px`, `padding-inline: $space-4` to `padding-inline: 15px`, and the type to 13px. In .topic-choices change `gap: $space-2;` to `gap: 7px;`. Leave the 1.5px border width: the colour already matches the design's #DCD8D3 exactly, so the $border-control deviation is not in play here, but the width looks like the same decision. 34px still clears the 24px target floor the project works to.

[help-plus] /help — Paragraph under "Send us a message"  (extra)
  file   : resources/views/pages/help.blade.php
  design : nothing between the panel title and the first field label
  built  : <p class="panel__body">We read every one. There is no ticket number, just a person who replies.</p>, measured 22.68px tall between the title and the "Your name" label
  FIX    : Delete the `<p class="panel__body">{{ $f['standfirst'] }}</p>` line from the contact panel in help.blade.php. It does repeat the section standfirst above it: the standfirst says "There is no ticket number and no chatbot" and this says "There is no ticket number, just a person who replies." Remove the now-unused `standfirst` key from the contact form content in App\Support\PageContent (or leave it for a later use, but drop it from the view).

[help-plus] /help — Rows inside the help topic cards  (spacing)
  file   : resources/scss/components/_link-cards.scss
  design : display: flex; flex-direction: column; gap: 9px; each row 13.5px / line-height 1.45, so a 19.6px row and a ~105px four-row list
  built  : .link-cards__links{display:grid} with no row-gap, .link-cards__link{min-height:40px}, .link-cards__summary{min-height:48px}. Measured row-gap "normal", link 40px, summary 48px, first card's list 168px
  FIX    : The design's 19.6px row cannot be matched without dropping the link below the 24px target floor the SCSS comment cites, so get as close as that floor allows: add `row-gap: 9px;` to .link-cards__links, change `@include tap-target(40px)` on .link-cards__link to `@include tap-target(28px)`, and change the bare `@include tap-target` on .link-cards__summary to `@include tap-target(32px)`. A four-row card goes from 168px to ~139px against the design's 105px. Flag to Matt as a judgement call rather than applying it blind: the gap is the part that is clearly a miss, the heights are a defensible access

[village-journeys] /journeys/autism-assessment — The journey title  (size)
  file   : resources/scss/components/_page-head.scss
  design : `font-size: 32px; letter-spacing: -0.022em; line-height: 1.18; margin: 0 0 10px 0` (and 34px/-0.02em for the /village and /journeys titles)
  built  : Measured 40px, letter-spacing `normal`, line-height 44.8px. `.page-head__title { margin-bottom:$space-4; @include type((30px,40px),1.12) }`, `.heading` sets no tracking.
  FIX    : Scope it to the logged-in screens; 40px is what the design draws on /help, /about and /how-it-works. Add `&--app &__title { @include type((28px, 34px), 1.2); letter-spacing: -0.02em; }` and `&--detail &__title { @include type((26px, 32px), 1.18); letter-spacing: -0.022em; }`, then put `page-head--app` on village/show.blade.php:3 and journeys/index.blade.php:3 and `page-head--detail` on journeys/show.blade.php:3.

[village-journeys] /journeys/autism-assessment — The "N in common" marker on a family card  (other)
  file   : resources/scss/components/_family-list.scss
  design : A eucalyptus pill on the name's baseline row: `display:inline-block; margin-left:9px; font-size:11.5px; font-weight:600; color:#00322F; background:#DFF1EF; padding:3px 9px; border-radius:999px` reading "6 in common"
  built  : A block-level green sentence two rows down, below the area/children line: measured `display:block; font-size:12px; color:rgb(0,105,100)`, text "18 things in common". `.family-card__meta, .family-card__common { display:bl
  FIX    : In components/family/card.blade.php wrap the name and the commonality in `<span class="family-card__line">` and put `.family-card__common` immediately after `.family-card__name`, before `.family-card__meta`. In _family-list.scss add `&__line { display:flex; gap:$space-2; align-items:baseline; flex-wrap:wrap; }` and replace the `&__common` rules with `&__common { display:inline-block; padding:3px 9px; background:$euc-050; border-radius:$radius-pill; color:$euc-800; @include type((11.5px, 11.5px), 1.3, 600); }` (drop it from the shared `&__meta, &__common` selector). The label text wants shorten

[village-journeys] /journeys/autism-assessment — Badge pills on the family cards in the journey list  (extra)
  file   : resources/views/components/family/card.blade.php
  design : No badge row here. The card is avatar, then name + "N in common" + an optional "Joined this week" pill on one baseline row, then `margin: 2px 0 0 0; font-size: 12.5px` for area and children. The design puts badges on the
  built  : Four badge rows render on this screen (measured). `<span class="badge-row">` with `.badge-row { gap:7px; margin-top:9px; display:flex }` showing "Profile complete", "First connection" etc on every card.
  FIX    : Add `'badges' => false` to the `@props` on line 4, change the badge block to `@if ($badges && $card->badges()->isNotEmpty())`, and pass `:badges="true"` at village/show.blade.php:187. journeys/show.blade.php:31 and requests/index.blade.php:85 then get no badges, which is what both designs draw.

[village-journeys] /journeys/autism-assessment — The hairline rule under "Families in this Journey"  (missing)
  file   : resources/scss/components/_family-list.scss
  design : `margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #DCD8D3` on the section-head row
  built  : Measured border-bottom 0px, padding-bottom 0px, margin-bottom 16px. `.family-list__head { display:flex; gap:$space-3; align-items:baseline; justify-content:space-between; margin-bottom:$space-4 }`
  FIX    : In `.family-list__head`, change `margin-bottom: $space-4` to `margin-bottom: 14px` and add `padding-bottom: $space-3;` and `border-bottom: 1px solid $border-hairline;`. Apply the `__heading` margin reset below at the same time or the row ends up 43px deep.

[village-journeys] /village — Space above the page title (all five logged-in screens)  (spacing)
  file   : resources/scss/components/_page-head.scss
  design : `padding: 34px 0 44px 0` on the content container for /village and /journeys, `40px 0 52px 0` for a journey detail and /requests
  built  : Measured 56px from the header rule to the title. `.page-head { padding-block:40px 32px }` then `{ padding-block:56px 40px }` from md, used unchanged on every screen.
  FIX    : Add modifiers rather than changing the base: the design draws `padding: 64px 0 56px 0` on /help, /about and /how-it-works, so the current 56px/40px is close to right there. Add `&--app { padding-block: 34px 0; @include from(md) { padding-block: 34px 0; } }` and `&--detail { padding-block: $space-10 0; @include from(md) { padding-block: $space-10 0; } }`, then apply `page-head--app` on village/show.blade.php:3 and journeys/index.blade.php:3 and `page-head--detail` on journeys/show.blade.php:3 and requests/index.blade.php:3.

[village-journeys] /village — Space between the "waiting to hear from you" card and "Families you are connected with"  (spacing)
  file   : resources/scss/components/_village.scss
  design : `margin-bottom: 22px` on the waiting card
  built  : Measured 40px. `.waiting` has `margin-top:$space-6` and no bottom margin; `.village__section-head { margin-top:$space-10; margin-bottom:$space-4 }` wins the sibling collapse.
  FIX    : In `.village__section-head`, change `margin-top: $space-10;` to `margin-top: 22px;`. (The design's row also uses `margin-bottom: 14px`, so `margin-bottom: $space-4` is 2px heavy — worth taking in the same edit.)

[village-journeys] /village — Gap between a journey card's title and its row of dots  (spacing)
  file   : resources/scss/components/_village.scss
  design : `<p style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; line-height: 1.3;">`
  built  : Measured 3px. `.village-card__title { margin-bottom: 3px; @include type((15px,15px),1.3,600) }`
  FIX    : In `.village-card__title`, change `margin-bottom: 3px;` to `margin-bottom: 10px;`. Note the design also draws this at 14px, not 15px.

[connecting] /f/{id}/report — The "Report and block" submit button  (colour)
  file   : resources/views/pages/safety/report.blade.php
  design : `font-size: 13.5px; font-weight: 500; color: #F8F4EE; background: #963630; padding: 12px 20px; border-radius: 999px;` in a `display: flex; gap: 10px; align-items: center` row beside Cancel
  built  : `<button class="button button--primary button--block">` → `.button--primary{color:#f8f4ee;background:#274b44}` plus `.button--block{width:100%;display:flex}`. #963630 is in the build as `$coral-ink` and is used on `.wait
  FIX    : Change the submit to `class="button button--danger"` (drop `button--block`; see the action-row fix). Add to resources/scss/components/_button.scss: `&--danger { color: $text-on-inverse; background: $coral-ink; &:hover { background: $coral-ink-900; } }`, and add `$coral-ink-900: #7A2B26; // a report button, pressed` under the Coral block in resources/scss/abstracts/_tokens.scss. $coral-ink on $linen-050 is 6.7:1, so it clears AA.

[connecting] /f/{id}/block — The "What blocking does" group, the four reassurance lines  (missing)
  file   : resources/views/pages/safety/block.blade.php
  design : Wrapped in a card: `background: #FFFFFF; border: 1px solid #DCD8D3; border-radius: 14px; padding: 20px 22px; margin-bottom: 20px;` with the title inside it at `font-size: 14px; font-weight: 600; color: #1B2940; margin: 0
  built  : No wrapper. `<p class="auth__label">What blocking does</p>` then `<ul class="safety-list">` sit straight on the linen page: `.auth__label{color:#1b2940;margin-bottom:12px;font-size:15px;font-weight:500}`, `.safety-list{g
  FIX    : Wrap both in a card and give the title its own class: `<div class="safety-card"><p class="safety-card__title">What blocking does</p><ul class="safety-list">…</ul></div>`. Add to resources/scss/components/_thread.scss beside `.safety-list`: `.safety-card { padding: 20px 22px; margin-bottom: $space-5; background: $bg-surface; border: 1px solid $border-hairline; border-radius: $radius-xl; }`, `.safety-card__title { margin-bottom: $space-3; color: $text-heading; @include type((14px, 14px), 1.3, 600); }`, `.safety-card .safety-list { margin-bottom: 0; }`. Dropping `auth__label` also fixes the title

[connecting] /f/{id}/block — The Block / Cancel action row  (alignment)
  file   : resources/scss/components/_thread.scss
  design : `display: flex; gap: 12px; align-items: center; flex-wrap: wrap;` with the button at `padding: 13px 26px; font-size: 14.5px; font-weight: 500; color: #F8F4EE; background: #274B44` and Cancel beside it at `font-size: 14.5
  built  : `.safety-actions{margin-top:24px}` holding a `button--block` (`width:100%;display:flex`), then a separate `<p class="safety-cancel">` at `text-align:center;margin-top:12px` with `.safety-cancel a{color:#4f5b70;font-size:
  FIX    : In block.blade.php put the form and the link in one row: `<div class="safety-actions"><form method="POST" action="…">@csrf<button class="button button--primary" type="submit">Block this family</button></form><a class="safety-cancel" href="…">Cancel</a></div>` — drop `button--block` and the `<p>` wrapper. Then in _thread.scss: `.safety-actions { display: flex; flex-wrap: wrap; gap: $space-3; align-items: center; margin-top: $space-6; }` and replace the `.safety-cancel` block with `.safety-cancel { margin: 0; color: $action; @include type((14.5px, 14.5px), 1.3, 500); }` (drops `text-align: cente

[connecting] /f/{id}/report — The Report / Cancel action row  (alignment)
  file   : resources/scss/components/_thread.scss
  design : `display: flex; gap: 10px; align-items: center;` with Cancel inline at `font-size: 13.5px; font-weight: 500; color: #274B44;`
  built  : Full-width `button--block` as the last item in `<form class="auth__form">`, then `<p class="safety-cancel">` outside the form at `text-align:center;margin-top:12px` with `.safety-cancel a{color:#4f5b70;font-size:14px}`
  FIX    : Make the last child of `<form class="auth__form">` a row holding both: `<div class="safety-actions safety-actions--tight"><button class="button button--danger" type="submit">Report and block</button><a class="safety-cancel safety-cancel--tight" href="{{ route('family', $other->public_id) }}">Cancel</a></div>`, and delete the standalone `<p class="safety-cancel">`. Add to _thread.scss: `.safety-actions--tight { gap: 10px; margin-top: 0; }` and `.safety-cancel--tight { @include type((13.5px, 13.5px), 1.3, 500); }`, on top of the `.safety-actions` / `.safety-cancel` rewrite from the /block fix.

[connecting] /messages/{id} — The in-thread meet-up safety note  (colour)
  file   : resources/scss/components/_thread.scss
  design : `align-self: flex-start; max-width: 86%; background: #EDEAFF; border-radius: 12px; padding: 11px 13px; display: flex; gap: 9px;` with a `width: 17px; height: 17px; border-radius: 50%; background: #5D4C97; color: #EDEAFF;
  built  : .note{color:#00322f;background:#dff1ef;border-radius:10px;padding:16px;font-size:13px;line-height:1.6} plus .thread__note{margin-bottom:20px} — full column width, no badge, and the same #dff1ef as the viewer's own messag
  FIX    : In messages/show.blade.php change the meet-up note from `class="note thread__note"` to `class="thread__note thread__note--meetup"` and give it a mark: `<span class="thread__note-mark" aria-hidden="true">i</span><span>Meeting in person? …</span>`. Leave the "this family has left" note on `.note`. Add to _thread.scss: `&__note--meetup { display: flex; gap: 9px; max-width: 86%; padding: 11px 13px; background: $violet-050; border-radius: $radius-lg; color: $violet-900; @include type((12px, 12px), 1.5); }` and `&__note-mark { flex: none; width: 17px; height: 17px; background: $violet-600; border-ra

[connecting] /requests — The page heading and standfirst  (size)
  file   : resources/scss/components/_page-head.scss
  design : h1 `margin: 0 0 8px 0; font-size: 32px; font-weight: 600; letter-spacing: -0.022em; line-height: 1.18;` then the standfirst `margin: 0 0 24px 0; font-size: 16px; line-height: 1.62; color: #4F5B70;`. The hi-fi keeps two s
  built  : .page-head__title{margin-bottom:16px;font-size:30px;line-height:1.12} rising to clamp(30px,4vw,40px) at (width>=768px) and 40px at (width>=1300px); .page-head__standfirst{font-size:16px;line-height:1.7} rising to 18px at
  FIX    : Add an in-product modifier and leave the base as the marketing scale: `&--product { .page-head__title { margin-bottom: $space-2; @include type((26px, 32px), 1.18); letter-spacing: -0.022em; } .page-head__standfirst { @include type((15px, 16px), 1.62); } }`. Then add `page-head--product` to the `<section class="wrap page-head">` in resources/views/pages/requests/index.blade.php, pages/messages/index.blade.php, pages/village/show.blade.php and pages/journeys/show.blade.php. Do not change the base: 40px/1.12 and 18px/1.7 are what the hi-fi draws on /about, /how-it-works, /help, /terms, /privacy, 

[account] /account/experiences/review — The tick marker beside each experience line  (other)
  file   : resources/scss/components/_review.scss
  design : <span style="flex: none; width: 17px; height: 17px; border-radius: 5px; background: #274B44; color: #F8F4EE; font-size: 11px; line-height: 17px; text-align: center;">✓</span>
  built  : .review-list__tick{background:#dff1ef;border-radius:50%;flex:none;width:18px;height:18px;margin-top:2px;position:relative} with the check drawn by .review-list__tick:after{border-block-end:2px solid #274b44;border-inline
  FIX    : In _review.scss set `.review-list__tick { width: 17px; height: 17px; border-radius: 5px; background: $euc-700; }` and change the :after check strokes from `2px solid #274b44` to `2px solid $linen-050` (#F8F4EE), keeping the 8x4 rotated box centred in the new 17px square.

[account] /account — The profile-completeness card  (colour)
  file   : resources/scss/components/_profile-sections.scss
  design : background: #FFFFFF; border: 1px solid #DCD8D3; border-radius: 14px; padding: 20px 22px; margin-bottom: 14px
  built  : .account-progress{background:#f0ece6;border-radius:12px;margin-bottom:20px;padding:16px 20px} — measured rgb(240,236,230), no border. It sits between two white bordered cards.
  FIX    : In _profile-sections.scss change .account-progress to `{ padding: 20px 22px; margin-bottom: 14px; background: $bg-surface; border: 1px solid $border-hairline; border-radius: 14px; }`.

[account] /account/badges — The "You can hide any badge" information panel under the standfirst  (colour)
  file   : resources/views/pages/account/badges.blade.php
  design : <div style="background: #EDEAFF; border-radius: 14px; padding: 16px 20px; margin-bottom: 28px; display: flex; gap: 12px; align-items: flex-start;"> with <span style="width: 20px; height: 20px; border-radius: 50%; backgro
  built  : <div class="note note--standalone"> → measured background rgb(223,241,239), colour rgb(0,50,47), border-radius 10px, padding 16px, no icon element
  FIX    : In badges.blade.php replace `<div class="note note--standalone">…</div>` with `<div class="nudge nudge--static"><span class="nudge__mark" aria-hidden="true">i</span><p class="nudge__text">…</p></div>`. .nudge already ships the exact palette (background #edeaff, .nudge__mark #5d4c97 on #edeaff, .nudge__text #2d1c57). Add `.nudge--static { align-items: flex-start; gap: 12px; margin-top: 0; margin-bottom: 28px; }` in the nudge component, since the base rule sets align-items: center and margin-top: 32px.

[account] /account — The avatar in the identity card at the top of the profile  (size)
  file   : resources/scss/components/_profile-sections.scss
  design : flex: none; width: 60px; height: 60px; border-radius: 50%; background: #D6DFDD; border: 1px solid #B8C7C4; font-size: 24px; font-weight: 600
  built  : .account-identity__avatar{...width:48px;height:48px;font-size:19px;font-weight:600} — measured 48x48 at 19px
  FIX    : In _profile-sections.scss change .account-identity__avatar to `width: 60px; height: 60px; font-size: 24px;` (colours and border already match). The design's identity card is also padding: 20px 22px against the build's 16px 20px, if you want the whole card to land.

[account] /account/notifications — The Frith+ tag beside the page title  (colour)
  file   : resources/scss/layout/_account.scss
  design : font-size: 11.5px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; color: #B3432A; background: #FFFFFF; box-shadow: 0 0 0 1.5px #E56A4E; padding: 5px 11px; border-radius: 999px
  built  : .account__tag{color:#1b2940;background:#e56a4e;border-radius:999px;padding:4px 11px;font-size:11px;font-weight:600;...} — measured background rgb(229,106,78), colour rgb(27,41,64), box-shadow none
  FIX    : In _account.scss change .account__tag to `{ padding: 5px 11px; background: $bg-surface; box-shadow: 0 0 0 1.5px $action-accent; border-radius: $radius-pill; color: $coral-600; font-size: 11.5px; }` (Coral 600 is #B3432A). Also drop or rewrite the `// Ink on coral, never white (guidelines §02)` comment above it: the design is not white-on-coral, it is Coral 600 type on white with a coral ring, which is exactly what the brand rules ask for. The current build is the non-compliant one, because the rules put a 16px floor on text set on coral and this is 11px. This is a different element from the ac

[account] /account/experiences/review — The gap between the h1 and its standfirst  (spacing)
  file   : resources/views/pages/account/experiences.blade.php
  design : <h1 style="margin: 0 0 8px 0; font-size: 32px; ...">Does this still sound like your family?</h1> followed immediately by <p style="margin: 0 0 30px 0; ...">
  built  : The standfirst is rendered outside the head wrapper in experiences.blade.php, so the gap is .account__head{margin-bottom:20px} and the standfirst then carries .account__standfirst--own{margin-bottom:24px}. Measured 20px 
  FIX    : Pass the copy through the shell instead of emitting it in the page: add `standfirst="You last updated this in {month}. Things change: a diagnosis arrives, a placement settles, a new worry starts. Keeping this current is what keeps your matches useful."` to the <x-account.shell> props and delete the standalone `<p class="account__standfirst account__standfirst--own">`. Inside .account__head it then picks up .account__title's 8px, matching the design, and the gap to the first card comes from .account__head (30px once the previous finding is applied). If the conditional "You last updated this in 

[edges] /oops (404) — The icon circle behind the question mark  (size)
  file   : resources/scss/components/_outcome.scss
  design : `width: 68px; height: 68px; border-radius: 50%; background: #D6DFDD; margin: 0 auto 22px auto`
  built  : .outcome__mark{width:56px;height:56px;margin:0 auto 20px;background:#f0ece6;border-radius:50%} with .outcome__mark--question{background:#dff1ef}
  FIX    : In _outcome.scss set `.outcome__mark { width: 68px; height: 68px; margin: 0 auto 22px; background: $euc-100; }` ($euc-100 is #D6DFDD, the tint the design uses on all four of these circles). Delete the `&--question { background: $euc-050; }` override and the class from resources/views/errors/404.blade.php. The `?` glyph stays at 26px/600, which already matches. .outcome__mark is used on the 404 only, so nothing else moves.

[edges] /oops (404) — Text column width  (size)
  file   : resources/scss/components/_outcome.scss
  design : inner column `max-width: 520px; margin: 0 auto`, the same 520px as the three empty states; /error is the one drawn at 560px
  built  : .outcome{max-width:624px;padding-inline:32px} from `@include container(560px + ($gutter-lg * 2))`, a 560px measure
  FIX    : Change the base to `@include container(520px + ($gutter-lg * 2))` (max-width 584px), which is right for the 404 and the three empty states, and keep 560px for the error pages by putting `max-width: 560px + ($gutter-lg * 2)` inside the `&--start` modifier added for the /error alignment fix.

[edges] /messages (no messages) — Body copy size and gap to the buttons  (size)
  file   : resources/scss/components/_village.scss
  design : `font-size: 16px; line-height: 1.66; margin: 0 0 26px 0`
  built  : .village__empty{font-size:15px;line-height:1.6} with no desktop step-up (the type mixin is called with (15px, 15px)), and .village__empty-action{margin-top:16px} carrying the gap
  FIX    : Falls out of moving the block onto .outcome: `.outcome__body` already reaches 16px at 1300px up, and needs `line-height: 1.66` (see the line-height fix) plus `.outcome__actions { margin-top: 26px; }` in place of the current 24px. That 26px matches the design on /oops and all three empty states, so set it on .outcome__actions rather than per screen.

======================================================================
SUBTLE  (44)
======================================================================

[home-marketing] / — Header nav spacing and link size  (spacing)
  file   : resources/scss/layout/_site-header.scss
  design : display: flex; align-items: center; gap: 20px; font-size: 13px
  built  : .site-header__nav{gap:24px}; .site-header__link{font-size:13px} base, {font-size:clamp(13px,4vw,14px)} at >=768px, {font-size:14px} at >=1300px
  FIX    : In `.site-header__nav`, change `gap: $space-6` to `gap: $space-5` (20px). In `.site-header__link`, change `@include type($type-small, 1.2)` to `@include type((13px, 13px), 1.2)` so the label stays 13px at every width instead of growing to 14px at xl.

[home-marketing] / — Header Register pill  (size)
  file   : resources/scss/components/_button.scss
  design : font-weight: 500; color: #F8F4EE; background: #274B44; padding: 9px 18px; border-radius: 999px, inheriting the nav container's 13px
  built  : .button--small{min-height:40px;padding:11px 22px;font-size:14px}
  FIX    : In `.button--small`, change `padding: 11px 22px` to `padding: 9px 18px` and `font-size: 14px` to `font-size: 13px`. Two cautions: `.button--small` is global, so check the other screens that use it; and the `min-height: 40px` will still hold the pill at 40px rather than the design's ~34px, so drop that too if the header pill is meant to match exactly — which puts it under the build's own 40px target floor.

[home-marketing] / — Three-pillar band padding and column gutters  (spacing)
  file   : resources/scss/layout/_wrap.scss
  design : band padding: 54px 0 58px 0; each column padding: 0 36px with padding-left: 0 on the first and padding-right: 0 on the last
  built  : .band--tight{padding-block:40px} base, {padding-block:48px} at >=768px; .pillars__item{padding-inline:32px} at >=768px
  FIX    : The band padding is NOT in _pillars.scss as reported — `.band--tight` lives in /Users/matt/Local Sites/frith/resources/scss/layout/_wrap.scss. There, change the `@include from(md) { padding-block: $space-12; }` inside `&--tight` to `padding-block: 54px 58px;`. Separately, in /Users/matt/Local Sites/frith/resources/scss/components/_pillars.scss change `.pillars__item { padding-inline: $space-8; }` to `padding-inline: 36px;`.

[home-marketing] / — Divider between the three pillars  (colour)
  file   : resources/scss/components/_pillars.scss
  design : border-right: 1px solid #3D5A54 on the first and second columns (the $euc-650 token)
  built  : .pillars__item+.pillars__item{border-inline-start:1px solid #f8f4ee24} — rgba(248,244,238,.14) over #274B44 composites to rgb(68,99,92) = #44635C
  FIX    : Change `& + & { border-inline-start: 1px solid rgba($linen-050, 0.14); }` to `& + & { border-inline-start: 1px solid $euc-650; }`. $euc-650 is #3D5A54 and already exists in abstracts/_tokens.scss (used once, in layout/_menu.scss). The side is fine: with gap:0 a border-left on items 2 and 3 lands on the same pixel as the design's border-right on items 1 and 2.

[home-marketing] / — Hero block padding  (spacing)
  file   : resources/scss/components/_hero.scss
  design : padding: 30px 0 44px 0
  built  : .hero{padding-block:32px 48px} base — NOT 40px 48px as reported — and {padding-block:40px 56px} at >=1024px. The desktop mismatch is real: 40/56 against a designed 30/44.
  FIX    : In `.hero`, change the `@include from(lg) { padding-block: $space-10 $space-14; }` to `padding-block: 30px 44px;`. The base `padding-block: $space-8 $space-12` is the mobile value and the design has no readable mobile frame, so leave it.

[home-marketing] / — Section heads (How it works, Journeys, What others see)  (spacing)
  file   : resources/scss/components/_section-head.scss
  design : margin-bottom: 40px on How it works and Journeys, 36px on What others see; h2 margin: 0 0 14px 0
  built  : .section-head{max-width:780px;margin-bottom:32px} .section-head__title{margin-bottom:12px} — one value for all three
  FIX    : Change `.section-head { margin-bottom: $space-8; }` to `margin-bottom: $space-10;` (40px) and `.section-head__title { margin-bottom: $space-3; }` to `margin-bottom: 14px;`. Add `&--tight { margin-bottom: 36px; }` and put that modifier on the "What others see" head in home.blade.php. The "40 / 40 / 36 / 32" in the finding is loose — home has three section heads, not four — but the three values are exactly as reported.

[home-marketing] / — Section headings (every h2)  (spacing)
  file   : resources/scss/base/_typography.scss
  design : letter-spacing: -0.022em on every 30px h2
  built  : .heading--section{font-size:26px;line-height:1.2} / clamp / 30px — no letter-spacing declared anywhere for this class, so it inherits normal
  FIX    : In `.heading`, add `letter-spacing: -0.022em;` to the `&--section` block (it currently holds only `@include type($type-h2, 1.2)`). The second half of this finding, about h1s outside the hero, was cut off in the report before the value and the screens it applies to, so I could not check it.

[registration] /join/you (and all six steps) — Vertical padding on the registration column  (spacing)
  file   : resources/scss/layout/_wizard.scss
  design : `padding: 56px 24px 64px 24px`
  built  : `.wizard{padding-block:32px 64px}` and `@media (width>=768px){.wizard{padding-block:48px 80px}}` — 48px top, 80px bottom at desktop. The 32px inline padding is correct, since .wizard is 624px = 560 + 2×32, keeping the 56
  FIX    : In _wizard.scss change the md block to `@include from(md) { padding-block: $space-14 $space-16; }` (56px 64px) in place of `$space-12 $space-20`.

[registration] /join/welcome — The ringed "Join your Journeys now" panel  (spacing)
  file   : resources/scss/components/_panel.scss
  design : `padding: 22px 24px` — the hi-fi uses `padding: 22px 24px` on all 11 of its panels and `padding: 20px 24px` on none
  built  : `.panel{background:#fff;border:1px solid #dcd8d3;border-radius:16px;padding:20px 24px}` (`$space-5 $space-6`)
  FIX    : In _panel.scss change `.panel`'s `padding: $space-5 $space-6;` to `padding: 22px $space-6;`. Leave `.panel__title { margin-bottom: $space-2 }` alone — their 6px claim is right for this one frame, but the design's own panel titles run 6px, 7px and 12px across the file, so 8px sits inside its spread and changing it would just move the error to the other nine panels.

[registration] /join/hopes — Gap between the tickbox and the option text  (spacing)
  file   : resources/scss/components/_check-list.scss
  design : `gap: 12px` on the option row (18px box, so the label starts 30px in)
  built  : `.check-list__item{grid-template-columns:18px minmax(0,1fr);gap:10px}`
  FIX    : Set `gap: $space-3;` inside the `&--cards &__item` block added for the hopes-card fix above. Leave `.check-list__item`'s own `gap: 10px` — the design's statement rows on /join/experiences really are 10px, so this must not be changed globally.

[registration] /join/family — Gap between child cards, and between the last card and "Add another child"  (spacing)
  file   : resources/scss/components/_child-rows.scss
  design : each card carries `margin-bottom: 14px`, including the last one before the add button
  built  : `.child-rows{gap:12px;display:grid}`, and the `<p>` wrapping the add button is a grid child of `.wizard__group{gap:8px}` with `p{margin:0}` from the reset — so 12px between cards and 8px under the last one
  FIX    : In _child-rows.scss change `.child-rows`'s `gap: $space-3;` to `gap: 14px;` and add `&__add { margin-top: 6px; }`. Then put `class="child-rows__add"` on the `<p>` at line 74 of /Users/matt/Local Sites/frith/resources/views/register/family.blade.php, so 8px group gap + 6px = the drawn 14px.

[registration] /join/welcome — The "We will email you at …" box  (colour)
  file   : resources/scss/layout/_wizard.scss
  design : `background: #F3F1EB`
  built  : `.wizard__launch{…background:#f0ece6…}` ($linen-100)
  FIX    : In `.wizard__launch` in _wizard.scss change `background: $linen-100;` to `background: $linen-075;`. $linen-075 is already #F3F1EB in abstracts/_tokens.scss, so no new value is needed.

[registration] /join/experiences — "4 more" disclosure under the statement list  (alignment)
  file   : resources/scss/components/_experience-groups.scss
  design : `font-size: 13px; color: #5D5A56; padding-left: 27px` — the 17px statement box plus the 10px gap, so it starts exactly under the statement labels
  built  : `.experience-groups__more-summary{…padding-left:30px…}`, written as `calc(18px + #{$space-3})` — the 18px/12px of the base check-list, not the 17px/10px the statements actually use
  FIX    : In _experience-groups.scss change `padding-left: calc(18px + #{$space-3});` to `padding-left: 27px;` (17px box + 10px gap). The comment above it already states the intent; the arithmetic just used the wrong box.

[registration] /join/you — The consent paragraph above Continue  (size)
  file   : resources/scss/layout/_wizard.scss
  design : `margin: 30px 0 24px 0; font-size: 13.5px; line-height: 1.65`
  built  : `.wizard__hint{color:#5d5a56;font-size:13px;line-height:1.5}` (13.5px from md up), sitting in `.wizard__form{gap:26px}`
  FIX    : Add `&--consent { line-height: 1.65; }` to the `&__hint` block in _wizard.scss and put `wizard__hint--consent` on the consent paragraph in /Users/matt/Local Sites/frith/resources/views/register/you.blade.php. Do not change `.wizard__hint`'s base 1.5 — every field hint in the design is 1.5 and only this paragraph is 1.65. The 30px/24px margins are within 4px and 2px of the form's 26px gap and are not worth special-casing.

[registration] /join/family — Space above the Continue button  (spacing)
  file   : resources/scss/layout/_wizard.scss
  design : `<div style="margin-top: 34px;">` wrapping Continue
  built  : `.wizard__actions` is a plain grid child of `.wizard__form{gap:26px}`; no `--spaced` modifier on this step
  FIX    : Add `&--end { margin-top: $space-2; }` to the `&__actions` block in _wizard.scss and put `wizard__actions--end` on the actions div at line 81 of /Users/matt/Local Sites/frith/resources/views/register/family.blade.php (26px gap + 8px = 34px). Do not reuse `--spaced`, which is 24px and would give 50px.

[registration] /join/family — Gap between the "Your household" group and "Your children"  (spacing)
  file   : resources/scss/layout/_wizard.scss
  design : `<div style="margin-bottom: 30px;">` on the household block
  built  : both blocks are `.wizard__group` children of `.wizard__form{gap:26px}`
  FIX    : 4px. If it is worth taking, add `&--parted { margin-bottom: $space-1; }` to the `&__group` block in _wizard.scss and put `wizard__group--parted` on the household div at line 18 of /Users/matt/Local Sites/frith/resources/views/register/family.blade.php. The design's uniform step rhythm is 26px and this is the only block that breaks it, so leaving it is defensible.

[registration] /join/welcome — Gap between the journeys list and "Browse all nine Journeys"  (spacing)
  file   : resources/scss/layout/_wizard.scss
  design : the journeys container carries `margin-bottom: 16px`
  built  : `.wizard__panel-link{margin-top:12px}` ($space-3); the 16px above the list is already right via `.wizard__journeys{margin-top:16px}`
  FIX    : In _wizard.scss change `&__panel-link { margin-top: $space-3; }` to `margin-top: $space-4;`.

[registration] /join/interests and /join/family — Pill height  (size)
  file   : resources/scss/components/_pills.scss
  design : `font-size: 13.5px; padding: 9px 16px; border: 1px solid; border-radius: 999px` with no minimum — 17.5px of text + 18px padding + 2px border = about 37.5px
  built  : `.pills__item{…border:1px solid #938c81;min-height:42px;padding-inline:16px;font-size:13.5px;line-height:1.3…}` via `tap-target(42px)` and no padding-block — 42px, so about 4.5px taller than drawn, not the 6px claimed
  FIX    : To match the design exactly, replace `@include tap-target(42px);` in `.pills__item` with `display: inline-flex; align-items: center; padding-block: 9px;`, keeping `padding-inline: $space-4`. That lands at 37.5px. It drops the pill below the 42px target the build chose on purpose, so this one is Matt's call rather than an obvious fix — 4.5px across three pill groups is barely visible.

[registration] /join/welcome — "Browse all nine Journeys" outline button  (size)
  file   : resources/scss/layout/_wizard.scss
  design : `font-size: 13.5px; font-weight: 500; border: 1.5px solid #274B44; padding: 10px 20px; border-radius: 999px`
  built  : `.button--small{min-height:40px;padding:11px 22px;font-size:14px}` on top of `.button--outline{color:#274b44;border-color:#274b44}` — 0.5px larger type and 1px/2px more padding, about 2.5px taller and 4px wider
  FIX    : Scope it: add `&__panel-link .button--small { padding: 10px $space-5; font-size: 13.5px; }` to the `.wizard` block in _wizard.scss. Do NOT change `.button--small` itself — the design's "+ Add another child" on /join/family is drawn at exactly 14px / 11px 22px / 1.5px #BDB6AB, which is what `.button--quiet.button--small` already produces, so a global change would break that one.

[auth] /link-sent — Gap between the amber "Nothing arrived?" box and the button under it  (spacing)
  file   : resources/scss/components/_auth.scss
  design : margin-bottom: 20px on the note box, button immediately after
  built  : Measured 32px. .note has no bottom margin and .auth__foot--action inherits margin-top:32px
  FIX    : In _auth.scss: `&--action { margin-top: $space-5; padding-top: 0; border-top: 0; }`. That is the 20px the design leaves under the note on both /link-sent and /verified; /forgot then takes the extra `--tight` modifier for its 12px.

[auth] /login — The note under "Email me a link instead" ("A parent registering at 1am...")  (spacing)
  file   : resources/scss/components/_auth.scss
  design : margin: 16px 0 0 0; font-size: 12.5px; line-height: 1.6
  built  : Measured 8px. _auth.scss declares &__note twice, at line 74 with margin-top: $space-6 and at line 101 with margin-top: $space-2, and the second wins
  FIX    : Delete the dead `&__note { margin-top: $space-6; }` at line 74, and in the surviving block at line 101 set `margin-top: $space-4;` (16px). Note that neither of the two values currently in the file is the design's: 24px was never right either, so do not simply reinstate the first one.

[auth] /login — Both buttons, "Log in" and "Email me a link instead"  (size)
  file   : resources/scss/components/_auth.scss
  design : primary font-size 14px, padding 13px; outline font-size 14px, padding 12px plus a 1.5px border, so about 43px and 44px tall
  built  : Measured 48px for both. .button{min-height:48px;padding:14px 34px;font-size:15px;line-height:1.2}
  FIX    : Do not change _button.scss as reported: the hi-fi draws the full-width pill at exactly 15px / 14px 34px on /join/you, /join/family, /join/hopes, /join/areas, /join/experiences, /join/interests, /join/welcome, /frith-plus/choose, /frith-plus/payment and /frith-plus/welcome, so the shipped global value is the design's. Scope it in _auth.scss instead: inside .auth, `.button { min-height: 44px; padding-block: 13px; font-size: 14px; }` and `.button--outline { padding-block: 12px; }`. Dropping $target-min to 44px on these is safe: they are full-width pills, so 420x44 still clears the 44px touch targ

[auth] /forgot — Gap between the email input and the submit button  (spacing)
  file   : resources/scss/components/_auth.scss
  design : margin-bottom: 14px on the input, button immediately after
  built  : Measured 20px. .auth__form{gap:14px} plus .auth__form>.button{margin-block-start:6px}
  FIX    : The 6px is right on /login, where the design does leave 20px over the submit, so cancel it on this screen rather than removing it. In _auth.scss add `&__form--flush > .button { margin-block-start: 0; }` under `&__form`, and put `class="auth__form auth__form--flush"` on the form at line 13 of forgot.blade.php. Apply this together with the divider fix, which narrows the 6px rule to `> .button:first-of-type`.

[auth] /login — Both text inputs  (spacing)
  file   : resources/scss/components/_auth.scss
  design : padding: 12px 14px
  built  : Measured 13px 16px. .field__control{padding:13px $space-4}, giving a 49px box
  FIX    : Scope it: inside .auth in _auth.scss, `.field__control { padding: 12px 14px; }`. Leave _field.scss as it is, because /join/you draws 13px 15px, which is what the shipped value is close to. Worth saying for whoever owns the other groups: the hi-fi draws 12px 14px on /help, /journeys and /frith-plus/payment too, so this could instead become the global default with /join/you overriding.

[auth] /link-sent — The amber "Nothing arrived?" box  (radius)
  file   : resources/scss/components/_note.scss
  design : border-radius: 4px 12px 12px 4px; padding: 14px 16px
  built  : Measured 4px 14px 14px 4px and 16px. .note--urgent{border-radius:$radius-sm $radius-xl $radius-xl $radius-sm} over .note{padding:$space-4}
  FIX    : The 14px is right at panel size: the hi-fi draws 4px 14px 14px 4px with 18px 20px padding on /terms, /reporting, /help, /meet-up-safety and /account/notifications, and 4px 12px 12px 4px with 13-14px 15-16px padding on the inline ones. So in _note.scss set `&--urgent { border-radius: $radius-sm $radius-lg $radius-lg $radius-sm; }` (12px) and add after it `&--panel#{&}--urgent { border-radius: $radius-sm $radius-xl $radius-xl $radius-sm; }` to hold the panels at 14px. For the padding put `.note { padding: 14px $space-4; }` inside .auth in _auth.scss, which serves /link-sent and /verified, both d

[auth] /link-sent — The envelope glyph inside the badge circle  (size)
  file   : resources/scss/components/_auth.scss
  design : font-size: 22px
  built  : Measured 24px on both /link-sent and /verified. .auth__badge{font-size:24px} with no override on --done
  FIX    : In _auth.scss change `&__badge`'s `@include type((24px, 24px), 1);` to `@include type((22px, 22px), 1);` and add `@include type((24px, 24px), 1);` inside `&--done`. The hi-fi draws the envelope at 22px and the tick at 24px, in the same 56px circle.

[auth] /login — Gap between a field label and its input  (spacing)
  file   : resources/scss/components/_auth.scss
  design : margin-bottom: 5px on the label, and on the label row
  built  : Measured 7px on /login and /forgot. .field{gap:7px}
  FIX    : The SASS comment is right about the screens it was written for: /join/you and /join/family both draw 7px under the label. The auth frames draw 5px, so override rather than edit _field.scss. Inside .auth in _auth.scss: `.field { gap: 5px; }`, alongside the 12px label and 12px 14px input padding.

[help-plus] /help — Contact section two-column split  (spacing)
  file   : resources/scss/layout/_split.scss
  design : grid-template-columns: 1.05fr 1fr; gap: 48px (672px / 641px at this container width)
  built  : .split--lead{grid-template-columns:minmax(0,1.1fr) minmax(0,1fr)} with .split--wide{gap:40px}. Measured "691.945px 629.055px", gap 40px
  FIX    : In .split--lead change `grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);` to `minmax(0, 1.05fr) minmax(0, 1fr);` and in .split--wide change `gap: $space-10;` (40px) to `gap: $space-12;` (48px). Check the other users of --wide before shipping the gap change, since it is shared.

[help-plus] /help — Page standfirst ("Most answers are here...")  (size)
  file   : resources/scss/components/_page-head.scss
  design : font-size: 17px; line-height: 1.68
  built  : .page-head__standfirst{font-size:18px;line-height:1.7} at (width>=1300px). Measured 18px / 30.6px
  FIX    : Scope it rather than changing the token: the design is inconsistent here and the build's 18px/1.7 is exactly what /how-it-works draws, while /about draws 16px/1.7 and /help 17px/1.68. Add `.page-head--help .page-head__standfirst { @include type((16px, 17px), 1.68); }` and put `page-head--help` on the section in help.blade.php. One pixel, and arguably not worth the extra class — worth saying so to Matt rather than silently changing a shared token.

[help-plus] /help — Eyebrow labels ("HELP CENTRE", "CONTACT US")  (size)
  file   : resources/scss/base/_typography.scss
  design : letter-spacing: 0.1em (1.1px at 11px)
  built  : .eyebrow{letter-spacing:.08em}. Measured 0.88px
  FIX    : In .eyebrow change `letter-spacing: 0.08em;` to `letter-spacing: 0.1em;`. Safe to do globally: across the hi-fi the page eyebrow (#B3432A, the build's $text-eyebrow) is 0.1em in 29 frames against 0.09em in 2. Leave .note__label and the footer column headings alone, which the design draws at 0.09em.

[village-journeys] /village — Padding inside a journey card  (spacing)
  file   : resources/scss/components/_village.scss
  design : `padding: 16px`
  built  : Measured 20px. `.village-card { padding: $space-5; ... }`
  FIX    : In `.village-card`, change `padding: $space-5;` to `padding: $space-4;`. (Their note says the cards are "8px wider inside" — it is the other way round: 20px padding makes the content 8px narrower and pushes it 4px in on every edge.)

[village-journeys] /village — Avatar on a "Families you are connected with" card  (size)
  file   : resources/views/pages/village/show.blade.php
  design : `width: 38px; height: 38px; font-size: 15px; font-weight: 600; color: #274B44`
  built  : Measured 42px wide, 17px type. The connected cards reuse `.family-card__avatar`, which is the 42px/17px suggestion-card avatar.
  FIX    : Add a size modifier instead of changing the shared avatar. In village/show.blade.php (the `.connected__link` loop, ~line 152) use `class="family-card__avatar family-card__avatar--sm"`, and in _family-list.scss add `&__avatar--sm { width: 38px; height: 38px; @include type((15px, 15px), 1, 600); }` inside `.family-card`.

[village-journeys] /village — Gap between a journey card's "You and N others" line and the "Open journey" pill  (spacing)
  file   : resources/scss/components/_village.scss
  design : `<p style="margin: 0 0 12px 0; font-size: 11.5px; line-height: 1.5;">`
  built  : Measured 16px. `.village-card__action { margin-top: $space-4 }`, `.village-card__meta` carries no bottom margin.
  FIX    : In `.village-card__action`, change `margin-top: $space-4;` to `margin-top: $space-3;`.

[village-journeys] /journeys/autism-assessment — Gap between "Families in this Journey" and the first family card  (spacing)
  file   : resources/scss/components/_family-list.scss
  design : 27px in total: `<p style="margin: 0">` inside a row with `padding-bottom: 12px`, a 1px rule, and `margin-bottom: 14px` (they called it 26px, which drops the rule)
  built  : Measured 32px: `.family-list__heading` keeps its standalone `margin-bottom: $space-4` as a flex item, which is not collapsed inside `.family-list__head`, and the head adds its own `margin-bottom: $space-4` on top. Toggli
  FIX    : Inside `.family-list`, add `&__head &__heading { margin-bottom: 0; }`. Do this together with the border/padding fix on `.family-list__head` above, or the head row will come out 43px deep instead of 27px.

[connecting] /requests — The request card: padding, avatar and name  (spacing)
  file   : resources/scss/components/_family-list.scss
  design : The "Asked you" cards are the roomy treatment: `border-radius: 14px; padding: 18px 20px;` avatar `width: 44px; height: 44px; font-size: 18px`, name `font-size: 16px; font-weight: 600; margin: 0 0 3px 0`, meta `font-size:
  built  : One size for both. `.family-card{border-radius:14px;padding:15px 18px;gap:16px}`, `.family-card__avatar{width:42px;height:42px;font-size:17px}`, `.family-card__name{font-size:15px;font-weight:600}`, `.family-card__meta{f
  FIX    : Write the modifier the blade already asks for. Add under `.family-card`: `&--request { padding: 18px 20px; .family-card__avatar { width: 44px; height: 44px; @include type((18px, 18px), 1, 600); } .family-card__name { @include type((16px, 16px), 1.3, 600); } .family-card__meta { @include type((13px, 13px), 1.5); } }`. Leave the shared `.family-card` at `padding: 15px 18px` with the 42px avatar: 42px is what the hi-fi's village frame draws, and the same component renders /village and /journeys/{slug}, so the compact row is right as it stands.

[connecting] /requests — The "Asked you 4" / "You asked 6" tally  (extra)
  file   : resources/scss/components/_family-list.scss
  design : Plain text trailing the label: `font-size: 12.5px; color: #5D5A56;` — no background, no padding, no added weight (it sits inside the tab row, not a heading)
  built  : .family-list__tally{color:#4f5b70;background:#f0ece6;border-radius:999px;padding:2px 8px;font-size:12px;font-weight:600;line-height:1.3}, rising to font-size:12.5px at (width>=1300px)
  FIX    : `&__tally { padding: 0; background: none; border-radius: 0; color: $linen-700; @include type((12.5px, 12.5px), 1.3, 400); }` — drop `padding: 2px $space-2`, `background: $linen-100`, `border-radius: $radius-pill` and the 600 weight, and move the colour from $text-body (#4f5b70) to $linen-700 (#5D5A56). The class appears only on /requests, so nothing else moves. Note the size already matches at desktop; it is the pill chrome and the colour that differ. Their stated reason is wrong — the hi-fi does draw a filled grey pill on this screen, the "Suggested by Frith" chip at `background: #F0ECE6` — s

[account] /account, /account/notifications, /account/data — The gap between the standfirst and the first card  (spacing)
  file   : resources/scss/layout/_account.scss
  design : <p style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: #4F5B70;">
  built  : .account__head{margin-bottom:20px} and .account__standfirst carries no margin-bottom in its base rule. Measured standfirst-bottom to first-card-top: 20px on all three screens.
  FIX    : In _account.scss set `.account__head { margin-bottom: 30px; }`. Watch one knock-on: the design draws /account/badges at 20px (its standfirst is margin: 0 0 20px 0), so if exactness matters on that screen, keep it at 20px with a page-level override rather than changing the shared rule in both directions.

[edges] /oops (404) and /error (500) — Page heading  (size)
  file   : resources/scss/components/_outcome.scss
  design : `font-size: 32px; font-weight: 600; letter-spacing: -0.022em; line-height: 1.18; margin: 0 0 10px 0`
  built  : .outcome__title{margin-bottom:12px;font-size:26px;line-height:1.15;letter-spacing:-.02em}, reaching font-size:30px at 1300px up via $type-h2
  FIX    : Give .outcome__title its own scale rather than editing $type-h2, which every other h2 on the site reads: `.outcome__title { margin-bottom: $space-2 + 2px; @include type((26px, 32px), 1.18, 600); letter-spacing: -0.022em; }`. That is margin-bottom 10px, 32px at desktop, line-height 1.18 (the build's 1.15 is off by the same reading) and the drawn tracking.

[edges] /oops (404) — Space above and below the message block  (spacing)
  file   : resources/scss/components/_outcome.scss
  design : outer `padding: 40px 0 52px 0` plus inner `padding: 40px 0`, so 80px above the circle and 92px below the buttons
  built  : .outcome{padding-block:64px 80px}
  FIX    : `.outcome { padding-block: $space-20 92px; }` (80px top, 92px bottom). The /error column keeps its own 70px/82px through the `&--start` modifier, since its inner padding is drawn at 30px rather than 40px.

[edges] /error (500) — Reassurance box padding and text size  (size)
  file   : resources/scss/components/_note.scss
  design : `padding: 18px 20px` with the paragraph at `font-size: 14.5px; line-height: 1.6`
  built  : .note{padding:16px;font-size:13px;line-height:1.6}, reaching 13.5px from 1300px up
  FIX    : Put both on the new --alert variant, not on base .note: the design's teal panel note is 20px 22px and its inline teal notes are smaller again, so changing the base would pull the policy pages out. Inside `&--alert` add `padding: 18px 20px;` and `@include type((14px, 14.5px), 1.6);`.

[edges] /error (500) — Gap between the body paragraph and the reassurance box  (spacing)
  file   : resources/scss/components/_outcome.scss
  design : body paragraph `margin: 0 0 28px 0`
  built  : `h1,h2,h3,h4,p,figure,blockquote,dl,dd{margin:0}` in the reset, with .outcome__note{margin-top:20px} (and .note--standalone{margin-top:20px}) carrying the whole gap
  FIX    : `.outcome__note { margin-top: 28px; }`. It is the more specific of the two rules in source order, so it wins over .note--standalone's 20px without touching that shared class.

[edges] /oops (404) and /error (500) — Primary pill button padding  (spacing)
  file   : resources/scss/components/_outcome.scss
  design : `padding: 14px 30px` with no border, so the text sits 30px from the pill edge
  built  : .button{border:1.5px solid #0000;padding:14px 34px}, putting the text 35.5px from the edge, about 11px wider across
  FIX    : Scope it, do not change .button in _button.scss. The base 34px is correct for the seven /join steps and the three Frith+ screens, which the hi-fi draws at `padding: 14px 34px` (10 instances against 9 at 30px). Add `.outcome .button { padding-inline: 28.5px; }` to _outcome.scss, and the same rule for the empty states once they move onto .outcome. 28.5px plus the 1.5px transparent border lands the text at the drawn 30px.

[edges] /oops (404) — Gap between the two actions  (spacing)
  file   : resources/scss/components/_outcome.scss
  design : `display: flex; gap: 12px; align-items: center; justify-content: center`
  built  : .outcome__actions{flex-wrap:wrap;justify-content:center;align-items:center;gap:16px;margin-top:24px;display:flex}
  FIX    : `.outcome__actions { gap: $space-3; }` (12px).

[edges] /oops (404) and /error (500) — Body paragraph line height  (size)
  file   : resources/scss/components/_outcome.scss
  design : `font-size: 16px; line-height: 1.66`
  built  : .outcome__body{font-size:15px;line-height:1.6}, reaching 16px at 1300px up
  FIX    : `.outcome__body { @include type((15px, 16px), 1.66); }`. The size is already right; only the leading moves.