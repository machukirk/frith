# Build audit, 12 November 2026

Four sweeps over the whole app, every finding then checked against the code by a
second pass. 78 confirmed. This is the list, grouped by what it costs to fix.

## Blockers

9 of them.

### faked-data (1)

- **The registration welcome panel still reads the home page's invented Journey list, which has just been deleted**  
  `resources/views/register/welcome.blade.php:5` · small  
  The "Join your Journeys now, before we open" panel lists three Journey titles taken from the home page's copy block rather than from the journeys table, so a Journey renamed or archived in the admin never changed it. It is now worse than wrong: the uncommitted fix to the home page has removed journeys.items from config/content/home.php, and the new migration unsets it from the stored pages row too, so this read falls through to its [] default. The panel renders with its heading and its "Browse all nine Journeys" button and an empty list between them, naming no Journeys at all. This is the only remaining reader of journeys.items anywhere in the codebase.
  Live source: `journeys table (App\Models\Journey::scopeOffered(); App\Support\JourneyBoard::forHome() already returns exactly this shape)`

### broken-flows (6)

- **"Pick up where you left off" puts a verified family into shadow mode, where all six screens save nothing and the end still says "You are now a First Frith Family"**  
  `/Users/matt/Local Sites/frith/app/Http/Controllers/RegistrationController.php:145` · medium  
  Three places offer "Pick up where you left off": the verify screen (resources/views/register/verified.blade.php:32), the logged-in /verified screen (resources/views/pages/auth/verified.blade.php:19) and the welcome email (app/Mail/FounderWelcome.php:55). All three point at register.start. By the time any of them is clicked, email_verified_at is set (RegistrationEmailController::verify writes it immediately before rendering the screen; LoginController::consume writes it on login). So re-entering their own address on step one takes the shadow branch, applyStep returns without writing, and the family fills in six screens for nothing. /join/welcome then tells them "You are now a First Frith Family" with their email printed on it. The comment's own mitigation, telling the real owner by email, is not implemented either: the shadow branch returns before sendWelcome().

- **The last button of registration, "Go to your village", bounces a brand-new family to the login page**  
  `/Users/matt/Local Sites/frith/resources/views/register/welcome.blade.php:51` · medium  
  Registration never logs anybody in: RegistrationController imports no Auth facade and never calls Auth::guard('founder')->login(). /village and /journeys are both inside the auth:founder group in routes/web.php, and bootstrap/app.php sends guests to route('login'). So the two buttons on the final registration screen, "Go to your village" (line 51) and "Browse all nine Journeys" (line 44), both land the family on a log-in form they have no password for. The panel above them says "Join your Journeys now, before we open / Pick them today and they will already have families in them on launch day" — an instruction the visitor cannot carry out from this screen.

- **A family who has pressed "Delete my account" can log straight back in and use the site as if nothing happened**  
  `/Users/matt/Local Sites/frith/app/Http/Controllers/LoginController.php:122` · medium  
  delete() sets deletion_requested_at, detaches journeys, closes pending requests and logs them out. Nothing then stops them logging back in: find() does not use the present() scope, the founders provider in config/auth.php is the bare Registration model with no global scope, and VillageController::show never checks hasLeft(). They land on an empty village with no explanation. Worse, MessageController::store only aborts when the OTHER side has left, so a departed family can keep sending messages into a thread where the recipient is being shown "This family has left Frith... there is nobody at the other end to read a reply now." There is no screen anywhere offering to cancel the deletion during the grace window.

- **The report form writes a row nothing reads, and the family's own screen says "Read by a person: Not yet" for ever**  
  `/Users/matt/Local Sites/frith/app/Http/Controllers/SafetyController.php:59` · medium  
  Submitting the report form redirects with "Thank you. A person reads every report, usually within 24 hours." The Report row is the only thing that happens: no mail, no job, no admin screen. app/Filament has resources for Forms, Journeys, Pages, Registrations and Users, and none for Report. The only code that reads a Report is AccountDataController::blocked, which shows it back to the person who filed it, and handled_at is never written by anything in app/ or database/, so pages/account/blocked.blade.php:35 renders "Read by a person: Not yet" permanently. A safeguarding report is the one flow on the site that must not end in a table nobody opens.
  Live source: `reports table (App\Models\Report); handled_at column exists and is indexed in 2026_11_09_120000_create_connections_and_messages.php but is never written`

- **"Pick up where you left off" always restarts at step one with an empty form, although the resume point is stored**  
  `/Users/matt/Local Sites/frith/app/Http/Controllers/RegistrationController.php:41` · medium  
  register.start always redirects to STEPS[0]. The email arrives after a delay and is usually opened on another device or after the session has gone, so current() returns null and every field renders blank. The family is asked their name, email and postcode again, from screen one of six, under a link that said it would resume them. registrations.furthest_step holds the answer and is written on every step, but it is read nowhere on the public side — only in three Filament files.
  Live source: `registrations.furthest_step, written by Registration::recordProgress() and read only in app/Filament/Resources/Registrations/*`

- **The "Email me a link instead" button on /login posts an empty email every time, so the main way in never works on the first try**  
  `/Users/matt/Local Sites/frith/resources/views/pages/auth/login.blade.php:43` · small  
  A family types their address into the email field, presses "Email me a link instead" (the button the page itself calls the kinder default), and gets the error "We need the email address you registered with." The address they just typed is in the other form, so the link form submits nothing. It only ever works on a second attempt, after a failed submission has repopulated old('email'). There is no JavaScript in the project (resources/ holds only scss and views), so nothing syncs the two.

### unbuilt-screens (1)

- **"A family has asked to connect" is a designed email and no such mail exists**  
  `app/Http/Controllers/ConnectionController.php:66` · medium  
  The hi-fi section "Emails — confirm your address, and a family has asked to connect" draws the second email in full: a coral circle icon, h3 "A family has asked to connect", a card with the asker's avatar, "Priya, Sevenoaks area", "two children (aged 6, 9)", a teal pill "You have 6 things in common", a coral "Respond to Priya" button and "No rush, and no pressure to reply." `ls app/Mail` holds only ContactMessage, FounderWelcome and LoginLink, and `store()` sends nothing. The whole notification promise on /account/notifications ("A Frith+ member asks to connect", locked on) has no mail behind it.

### admin-coverage (1)

- **Eighteen product screens behind the login have no content source at all, so none of their copy is editable**  
  `resources/views/pages/village/show.blade.php:19` · large  
  Every screen a family uses once they are in is written directly into Blade: village, village setup, the Journeys directory and a Journey page, Messages and a thread, Requests, report, block, a family profile, and the eight account screens. That includes the empty states, which are the copy most likely to need tuning after launch, and the safety wording on the message thread. Of the 68 Blade files, only 22 read from PageContent, StepContent or FormDefinition, and five of those 22 read the three stranded config blocks above.

## Important

55 of them.

### faked-data (7)

- **/join/welcome tells every family they are a First Frith Family, including the ones who are not**  
  `config/content/join.php:17` · medium  
  The screen at the end of registration says "You are now a First Frith Family" as an h1, and closes with "Frith+ stays free for you, always." Both are rendered unconditionally. Only the first hundred registrations are issued a founder_number; after that assignFounderNumber() returns without writing one, so family 101 onwards is told they hold a status the database says they do not have, and promised a membership that hasPlus() will refuse them. The very next screen in the same flow, register/verified.blade.php:6, already guards on exactly this column (@if ($registration->founder_number)), so the two screens in one journey disagree about the same family.
  Live source: `registrations.founder_number (App\Models\Registration::isFounder() / hasPlus())`

- **Five policy pages print a typed-in "Last updated 9 September 2026" while pages.updated_at holds the real date**  
  `config/content/privacy.php:13` · medium  
  Each policy page carries its own last-updated date as a string inside the editable copy. An editor changes the Privacy Policy in Filament, the pages row's updated_at moves, the sitemap's <lastmod> moves with it (SitemapController already reads that column), and the page itself goes on saying 9 September 2026. The reading time in the same string has the same problem. For a document whose whole purpose is to be the version in force on a given date, the date is the one field that must not be hand-maintained. Five files carry the same string: privacy.php:13, terms.php:13, community_guidelines.php:24, meet_up_safety.php:13, reporting.php:28.
  Live source: `pages.updated_at (and pages.updated_by), App\Models\Page`

- **"Up to 10 open requests at a time" describes a cap that nothing enforces**  
  `config/content/how_it_works.php:45` · medium  
  /how-it-works promises a ten-request ceiling as a fairness guarantee, and the help centre lists "The 10-request limit" as a topic. ConnectionController::store() counts nothing before creating a row: its only guard is that a connection between the two families does not already exist. There is no limit key in config/frith.php and nothing matching a limit in Connection. A family can open a hundred requests, and the stated reason for the cap ("so every family gets a real reply rather than a queue") does not hold. The number also appears nowhere the app could read it, so the copy and the code cannot currently be made to agree.
  Live source: `connections table (pending rows by requester_id) — the cap itself has no config key yet`

- **Frith+ advertises "specialist Journeys" that the journeys table has no concept of**  
  `config/content/frith_plus.php:28` · medium  
  /frith-plus lists "Join more Journeys - access specialist Journeys" as a paid benefit. The journeys table carries slug, title, blurb, position and archived_at and nothing else: there is no tier, no plus-only flag, no specialist category. Journey::scopeOffered() returns every unarchived Journey to every family, and JourneyController::index() applies no membership filter. A family who pays finds exactly the same list they already had.
  Live source: `journeys table (App\Models\Journey) — would need a tier or plus_only column`

- **"Browse all nine Journeys" is a hand-typed count of a table**  
  `config/content/join.php:22` · small  
  The button at the end of registration counts the Journeys in prose. Add a tenth Journey, or archive one, and the button lies. /journeys itself gets this right on the same data, printing "All {{ $total }}" from Journey::offered()->count(). Worse, join is not one of PageSeeder::pages(), so this string has no admin screen behind it: nobody but a developer can ever correct it.
  Live source: `journeys table (App\Support\JourneyBoard::count())`

- **The phone menu describes Journeys as "Nine groups of families"**  
  `config/frith-nav.php:30` · small  
  Every visitor opening the menu on a phone is told there are nine Journeys. The count is in a config file with no admin screen, and the comment eight lines below it in the same file states the principle this breaks: counts wait until there is something true to count. The journeys table is where the number lives, and JourneyBoard::count() already returns it.
  Live source: `journeys table (App\Support\JourneyBoard::count())`

- **Frith+ sells a commonality count that every family already sees for free**  
  `config/content/frith_plus.php:24` · small  
  /frith-plus lists "Spot stronger matches - see how many things you have in common as you browse" as something the membership adds. Every family card on every screen already renders it, with no membership check: the family list in a Journey, the suggestions on /village, and the Requests screen all go through the same component. A free family reading the Frith+ page is being offered something already on the screen behind it.
  Live source: `App\Support\Matching::inCommon() over registration_experiences — computed for every viewer, no tier check`

### broken-flows (14)

- **16 of the 25 links in the Help centre are dead anchors, and the help search returns them as results**  
  `/Users/matt/Local Sites/frith/config/content/help.php:40` · large  
  Every topic card on /help renders its links through SiteNavigation::url(), which turns a null route into href="#". "Registering and verifying", "Asking to connect", "The 10-request limit", "Messaging and meeting up", "Blocking someone", "Editing your profile", "Notifications and quiet hours", "Badges on your profile", "Leaving Frith", "Prices and billing", "Cancelling", "First Frith Family", "I cannot log in", "Nothing is showing near me", "I did not get an email" and "Something looks broken" all look like links, are styled like links, and do nothing when pressed. HelpSearch::run (app/Support/HelpSearch.php:34) builds its result URLs the same way, so searching the terms the placeholder itself suggests ("block", "cancel") returns results that go nowhere. Help is what the footer and both phone menus point at for support.

- **"Request my data" records a timestamp that nothing ever acts on, under copy promising an email within the hour**  
  `/Users/matt/Local Sites/frith/app/Http/Controllers/AccountDataController.php:46` · large  
  The form posts, the screen says "Asked for. We will email it to you, usually within the hour", and from then on the panel reads "Asked for on 12 November. It goes to …". Nothing else in the codebase reads data_requested_at: grep across app/, routes/, database/ and config/ finds only the cast, this write, and the migration. No job, no mail, no Filament column, no command. A subject-access request disappears into a column, and the page carries the legal line "Data requests answered within 30 days" underneath it.
  Live source: `registrations.data_requested_at — already holds who asked and when; nothing surfaces it`

- **/forgot promises "a link to set a new one" but there is no set-a-password screen anywhere in the app**  
  `/Users/matt/Local Sites/frith/config/content/auth.php:36` · medium  
  A family who did set a password and forgot it presses "Forgotten it?" on /login, reads that a link will let them set a new one, and gets a one-use login link instead (LoginController::sendLink → LoginLink::issue). The link logs them in and drops them on /verified. There is no password route, no password view and no password form outside /login — artisan route:list has nothing under password, and the only other mention is pages/account/name.blade.php, which says the email address cannot be changed from there. The promise on this screen cannot be kept by any destination that exists.

- **The designed 10-open-request limit screen can never appear, because no limit is enforced**  
  `/Users/matt/Local Sites/frith/app/Http/Controllers/ConnectionController.php:44` · medium  
  The hi-fi has a full screen for it: "You've reached 10 open connection requests." / "Once one is accepted, declined or closed you will be able to reach out to another family." / counts of "6 waiting for a reply" and "4 you have not answered yet" / a "Review your requests" button. store() counts nothing; it only checks for an existing connection. config/frith.php has no limit setting (only founders.limit), and the help centre lists "The 10-request limit" as a topic whose link is one of the dead anchors. A family can open unlimited requests and the screen that explains the rule is unreachable.
  Live source: `connections table — Connection::for($family)->where('status', Connection::PENDING) already gives both numbers the design shows`

- **The /journeys "suggest a Journey" form in the design does not exist: no form, no route, no handler**  
  `/Users/matt/Local Sites/frith/resources/views/pages/journeys/index.blade.php:88` · medium  
  The hi-fi's Journeys screen ends with a panel: "Is something missing? / Tell us the Journey you wish existed. We read every suggestion and they shape what we add next.", two fields ("The Journey you would like to see", "Anything you want to add — optional") and a "Send suggestion" button, plus the caveat that it will not be replied to. The built page ends at the journey list. There is no route for it in routes/web.php, no controller, and App\Support\Suggestions is about suggesting families, not Journeys. The whole flow is absent rather than broken.

- **The login flow is a cul-de-sac: "Log in to see your village" lands on a screen whose only button is "Back to the homepage"**  
  `/Users/matt/Local Sites/frith/config/content/auth.php:56` · small  
  Both login paths end at route('verified') (LoginController::attempt:64 and ::consume:110). resources/views/pages/auth/verified.blade.php:24 renders one button, labelled from this config, pointing at route('home'). So a family who logs in to see their village is put back on the public marketing page, and has to find the village through the header. The login screen's own standfirst promises the village: 'standfirst' => 'Log in to see your village.'

- **Leaving Frith ends in silence: the confirmation is flashed to a page that never renders it**  
  `/Users/matt/Local Sites/frith/app/Http/Controllers/AccountDataController.php:104` · small  
  A family ticks the box, presses "Delete my account", and arrives on the public home page with no acknowledgement of any kind — no banner, nothing to say their profile has gone or when the rest is deleted. resources/views/pages/home.blade.php contains no session('status') block, and neither does components/layouts/site.blade.php. Only the account shell, the village, the thread, the family profile, requests and the two journey screens render that flash. This is the last thing the product ever says to them.

- **Public links to /journeys bounce visitors to a login form, on the home page, in the phone menu and in the footer of every page**  
  `/Users/matt/Local Sites/frith/resources/views/pages/home.blade.php:105` · small  
  App\Support\JourneyBoard already documents this exact hazard and routes the home page's journey cards around it ("sending a visitor there from the public home page bounces them to a login screen they have no account for. That is a dead end on the one page meant to persuade them"). The "See who is in each Journey" link sitting directly beneath those cards was not given the same treatment, and nor were config/frith-nav.php's guest phone menu (['label' => 'Journeys', 'route' => 'journeys', 'meta' => 'Nine groups of families']), its footer group, or config/content/help.php's "Choosing your Journeys". A visitor who has never heard of Frith taps "Journeys" and gets "Welcome back".

- **"Blog" and "Cookies" render as href="#" in the footer of every page on the site**  
  `/Users/matt/Local Sites/frith/config/frith-nav.php:84` · small  
  Neither route exists, so SiteNavigation::url() returns '#' and components/site/footer.blade.php:25 emits them as ordinary footer links. Both are in the hi-fi footer, so they are designed; neither has a route, a view or a content file. Two dead links on every page, including the error pages that render the site layout. Cookies in particular is a link people go looking for.

- **The block screen is unreachable: nothing in the app links to route('block')**  
  `/Users/matt/Local Sites/frith/resources/views/pages/family/show.blade.php:80` · small  
  /f/{family}/block is fully built — a controller action, a view with its four "what blocking does" statements, and a POST — and it is a designed screen in the hi-fi (frith.community/block). A grep for route('block' across resources/ and app/ returns nothing but the form's own action inside that view. The only safety link on a family's profile goes to the report screen, so the one path on offer is the one that files a safeguarding report, which is exactly what the block screen exists to avoid: "No reason needed, and nothing is reported. You do not owe anyone an explanation for not wanting to talk." The only way to reach it is to type the URL.

- **A Journey's own page has no way to leave it, though the design puts the leave control there**  
  `/Users/matt/Local Sites/frith/resources/views/pages/journeys/show.blade.php:36` · small  
  The hi-fi's Journey screen ends with a panel reading "Not the right Journey? / Anyone you have connected with stays connected. / Leave this Journey". The built screen ends with a single "← All Journeys" link. route('journeys.leave') is posted from exactly one place in the whole app, the "Joined" pill on the directory (pages/journeys/index.blade.php:71), where the control is an unlabelled pill reading "Joined" whose only accessible name is the hidden "Leave …" span. A family looking at a Journey that is not right for them has no visible way out of it from the screen they are on.

- **/frith-plus's only call to action resolves to href="#"**  
  `/Users/matt/Local Sites/frith/resources/views/pages/frith-plus.blade.php:29` · small  
  The page is a public, indexed marketing page ending in one accent button labelled "Explore Frith+". frith-plus.choose has no route, so SiteNavigation::url() returns '#' and the button does nothing. The payment screens themselves are deferred by agreed scope, so the fix is this button's state — a waiting-list note, or no button — not the screens behind it. As it stands the one conversion control on the page is a dead anchor. The same applies to the account rail's "Frith+ membership" entry (resources/views/components/account/shell.blade.php:21), which sends a logged-in family to this public page rather than to the designed /account/frith-plus.

- **A family profile has no back link and no "Not for us", so the only way off it is the browser**  
  `/Users/matt/Local Sites/frith/resources/views/pages/family/show.blade.php:71` · small  
  The hi-fi's /f/fun-house shows two actions side by side, "Ask to connect" and "Not for us", and a back chevron in the mobile header. The built screen has the ask form, a report link, and nothing else — no "Not for us", no back link to the village or the Journey the family came from. A family who opens a suggestion, decides against it, and wants to carry on looking has no control on the page that does that; the suggestion stays on their village because Suggestions::for only filters out families with a Connection row.

- **Pressing Enter on the children step adds or removes a child instead of continuing**  
  `/Users/matt/Local Sites/frith/resources/views/register/family.blade.php:40` · small  
  store() treats any submission of the family step whose action is not exactly "continue" as a row adjustment. The first submit button in the form is "Remove child 1" when there is more than one child row, and "Add another child" otherwise — and that first button is what a browser fires when somebody presses Enter. So a parent who finishes the second child's birth year and hits Enter, as people do on a phone keyboard, silently loses child 1 and is bounced back to the same screen. Nothing on the screen explains what happened.

### unbuilt-screens (19)

- **Messages is designed as a two-pane screen and is built as two separate pages**  
  `resources/views/pages/messages/show.blade.php:3` · large  
  The hi-fi caption reads "Messages — two-pane on desktop, single pane on mobile." The desktop frame draws a 212px conversation list on the left (with a coral unread dot on the active row), the thread in the middle, and a thread header carrying the family's avatar, name, area and a bordered "Safety" chip. The build renders /messages as a list page and /messages/{connection} as a separate full-width page with a "← All messages" link at the bottom, so on desktop the conversation list disappears the moment a thread is opened.

- **The "While you are here" Journey-recommendation rail beside a conversation is not built**  
  `resources/views/pages/messages/show.blade.php:84` · large  
  The hi-fi's messages frame has a third 260px rail on a stone background: eyebrow "While you are here", heading "Because of the Journeys you are in", then two Journey cards each with a reason line ("8 families · Families here also joined Waiting for an autism assessment", "11 families · Common next step for families in Starting the EHCP process") and a "Join" button, closing with "Browse all nine Journeys". Nothing in the build matches: `grep -rn "While you are here\|Because of the Journeys" resources app` returns nothing.

- **Sixteen help-centre topic links render as href="#"**  
  `config/content/help.php:40` · large  
  /help draws six topic cards holding 25 links, and 16 of them have `'route' => null`: Registering and verifying, Asking to connect, The 10-request limit, Messaging and meeting up, Blocking someone, Editing your profile, Notifications and quiet hours, Badges on your profile, Leaving Frith, Prices and billing, Cancelling, First Frith Family, I cannot log in, Nothing is showing near me, I did not get an email, Something looks broken. help.blade.php:79 passes each one through `SiteNavigation::url($link)`, which returns '#' for a null route, so two thirds of the help centre is a link that does nothing and says nothing about it.

- **The join-Journey consent overlay is designed as a modal and is not built; joining posts with no confirmation**  
  `resources/views/pages/journeys/index.blade.php:70` · medium  
  The hi-fi draws a modal over /journeys: backdrop `rgba(27,41,64,.34)`, a 340px dialog with the Frith mark, h3 "Join this Journey", "Your profile will be visible to other families in this Journey.", a white card naming the Journey and its family count, "Join Journey" + "Cancel", and a footnote "You can leave a Journey at any time." The build has no dialog at all (no `<dialog>`, `aria-modal`, overlay or backdrop anywhere in resources/views). Pressing Join submits immediately, so the one moment a family chooses to become visible has no confirmation step. The hi-fi labels this section "Journeys — the directory with journey suggestions, then the join consent over it. Joining is the only place a family chooses to become visible."

- **The 10-request limit screen is designed as a full page and neither the screen nor the limit exists**  
  `app/Http/Controllers/ConnectionController.php:59` · medium  
  The hi-fi draws a whole /requests state: an amber circle icon, h2 "You've reached 10 open connection requests.", "Once one is accepted, declined or closed you will be able to reach out to another family.", a white card reading "6 waiting for a reply / 4 you have not answered yet", a "Review your requests" button and "Ask again once one is answered or withdrawn." `store()` creates a Connection unconditionally, so there is no limit, no counter and no screen. The sidebar meter that counts towards it ("Requests you have made — 6 of 10" with ten coral segments) is absent too.

- **Withdrawing a request you sent is designed per row and has no control, controller action or route**  
  `resources/views/components/family/card.blade.php:37` · medium  
  The hi-fi's "You are waiting on 6 families" list gives every row "No reply yet" and an underlined "Withdraw", with the reassurance "A family never sees that you are waiting, and withdrawing tells them nothing." The build shows a dead label and offers no way out. `grep -rni withdraw app resources` returns nothing: no route, no controller method, no button. A family who asked the wrong person is stuck with it for good.

- **The /requests right-hand rail (request meter, Frith+ upsell, "Saying no") is not built**  
  `resources/views/pages/requests/index.blade.php:61` · medium  
  The hi-fi puts a 300px rail beside the request lists holding three cards: "Requests you have made — 6 of 10" with a ten-segment meter and the explanation of the limit; "Seeing who asked" with "On the free plan, requests arrive one at a time as a private notification. Frith+ shows you everyone who has asked" and an "Explore Frith+" button; and "Saying no" with "'Not for us' needs no reason and sends no message. They are never told, and they cannot ask you again." The built page is a single column that ends after the three lists, so none of that explanation reaches the reader.

- **The Journey detail right rail ("Your connections here" and "Not the right Journey?") is not built**  
  `resources/views/pages/journeys/show.blade.php:36` · medium  
  The hi-fi gives /journeys/{journey} a 300px rail with two cards. First "Your connections here" — "2 families of 7", a list of the families you are connected with in this Journey each with "Connected in June", and "Requests you receive arrive as a private notification. Nothing happens unless you ask too." Second "Not the right Journey?" — "Leaving hides your profile from the families here. Anyone you have already connected with stays connected." plus a "Leave this Journey" button. The built page ends with a back link, so leaving is only possible from the /journeys list and the connection context is missing entirely.

- **The "Is something missing?" Journey suggestion form on /journeys has no route, no view and no handler**  
  `routes/web.php:118` · medium  
  The hi-fi draws a full-width card at the foot of /journeys: "Is something missing?", "Tell us the Journey you wish existed. We read every suggestion and they shape what we add next.", an amber caveat panel ("We will not reply to these, and we cannot promise to add one"), a text field labelled "The Journey you would like to see" placeholdered 'For example, "Selective mutism"', an optional "Anything you want to add" field, and a "Send suggestion" button. It is drawn on both desktop and mobile. Nothing in the build corresponds: `grep -rn "Is something missing\|Send suggestion" app resources config` returns nothing, and routes/web.php defines only index/join/leave/show under /journeys.

- **The desktop account dropdown, with its notification bell, is not built**  
  `resources/views/components/site/header.blade.php:105` · medium  
  The hi-fi draws a 236px dropdown under a bordered avatar with a chevron: a header row ("Sarah / Tonbridge area") next to a circular notifications bell carrying a coral dot, then rows "Your village — Back to your families", "Requests — 2 waiting for you", "Your profile — What other families see", "Badges — What you have earned", a dark-teal "Frith+ membership" row, "Help centre", and "Log out". The build shows the family's display name as one plain text link beside a Log out button; there is no avatar, no dropdown, no bell and no notifications surface anywhere in the app.

- **The Frith+ gated "specialist Journey" row is designed and nothing supports it**  
  `resources/views/pages/journeys/index.blade.php:74` · medium  
  The hi-fi draws a sixth row on /journeys with a stone background instead of white: "Tribunal & appeals", meta "Specialist journey", and a coral-ringed "Frith+" pill where the Join button sits on the other rows. The build has no such state: config/frith-journeys.php gives every Journey only a title and a blurb, the journeys table migration adds no plus or specialist column, and `Journey::scopeOffered` returns everything unarchived, so Tribunal and appeals renders as an ordinary joinable row. The /frith-plus page meanwhile promises "Join more Journeys - access specialist Journeys."

- **The "Not for us" action on a family's profile is designed and not built**  
  `resources/views/pages/family/show.blade.php:71` · medium  
  The hi-fi's /f/fun-house frame draws two stacked full-width actions, a coral "Ask to connect" and an outlined "Not for us" beneath it, with "Report and block this family" below as a quiet link. The build renders only the primary action and the report link. There is no way to dismiss a suggested family short of reporting them, and no route would support one: `connections.decline` requires an existing pending row where you are the addressee (ConnectionController::mineToAnswer aborts otherwise).

- **The /block screen is built and designed but nothing links to it**  
  `routes/web.php:156` · small  
  The hi-fi draws the whole /block screen: h1 "Block Willow Cottage", "No reason needed, and nothing is reported", a "What blocking does" card with four ticked statements, "Block this family" + "Cancel", and "report and block instead". resources/views/pages/safety/block.blade.php matches it line for line, but `grep -rn "route('block'" resources app` returns no matches: no profile, no thread, no menu links to it. The only inbound link anywhere goes the other way (block.blade.php points at `route('report')`). A family who wants to block without reporting can only get there by typing the URL.

- **Help search results for routeless topics link back to the help page itself**  
  `app/Support/HelpSearch.php:34` · small  
  Searching help for "block" returns "Blocking someone" as a result, and because that topic's `route` is null the fallback `?? 'help'` resolves it to route('help'). Clicking the result reloads /help and loses the search, which reads as a broken page rather than a missing article. The same topic rendered in the topic card below gives '#', so the two routes through the same data disagree.

- **The only call to action on /frith-plus points at a route that does not exist**  
  `resources/views/pages/frith-plus.blade.php:29` · small  
  The public Frith+ page ends with a single accent button whose href is `SiteNavigation::url('frith-plus.choose')`. No `frith-plus.choose` route is registered, so the button renders as href="#" and clicking it does nothing. The same "Explore Frith+" button is drawn on /requests and /account/notifications in the hi-fi and points at the same place. The payment screens themselves are deliberately deferred, but the deferral currently leaves the marketing page's one action dead rather than, say, pointing at a holding page or an interest form.

- **The Frith+ "things in common" callout inside a Journey is not built**  
  `resources/views/pages/journeys/show.blade.php:20` · small  
  Under the family list the hi-fi draws a teal panel ringed in 2px coral: "You are seeing how much you have in common because you are on Frith+. Families are listed most in common first." The build prints the bare label "Most in common first" with no explanation of why the counts are visible or what plan they belong to, so the one place Frith+ shows its value inside the product says nothing.

- **"Send it again" on /link-sent is designed and the screen offers only a way back**  
  `resources/views/pages/auth/link-sent.blade.php:34` · small  
  The hi-fi's /link-sent frame ends with a single action, "Send it again". The build renders an outlined "Back to log in" button and no resend at all. A family whose one-hour link never arrived has to go back to /login and retype their address. The POST that would do it already exists (`login.send-link`) and the address is already in the session as `session('link-sent.email')`, so the screen has everything it needs except the form.

- **A request with no Journey behind it shows no provenance; the designed "Suggested by Frith" pill is missing**  
  `resources/views/pages/requests/index.blade.php:36` · small  
  The hi-fi's caption for this section states the rule: "Every request shows where it came from — the Journey they found you in, or Suggested by Frith if it came from your matches — so it is never unclear why someone is asking." It draws a violet "via Waiting for an autism assessment" pill and a stone "Suggested by Frith" pill. The build appends "· via <title>" only when `$connection->journey` is set and renders nothing otherwise, so a request from a suggestion arrives with no stated reason. The truth is already stored: connections.journey_id is null for exactly that case.
  Live source: `connections.journey_id (null means it came from suggestions)`

- **The "Not for us" tab on /requests shows a count and a sentence instead of the closed families**  
  `resources/views/pages/requests/index.blade.php:79` · small  
  The hi-fi draws /requests with three tabs across a rule: "Asked you 4", "You asked 6" and "Not for us", the third being a list like the others. The build renders a heading with a tally and then a single sentence where the list should be, so a family who wants to see who they closed and when is told the count and nothing else, even though `$closed` is already in scope.

### admin-coverage (15)

- **The report reasons a distressed family picks from have no admin screen, despite the file inviting edits**  
  `config/frith-safety.php:19` · medium  
  The five radio options on /report are fixed in code. The file itself says to reword them freely, but there is no Filament resource for safety, so an editor who finds that "Asked to meet in a way that felt unsafe" is being misread, or who wants a sixth reason, has to ask a developer for a file change and a deploy. The slugs are stored on every report row, so this is exactly the kind of label-only change an admin screen should allow.

- **Notification switch labels, blurbs and the Frith+ request pace options are config-only**  
  `config/frith-notifications.php:26` · medium  
  Everything on /account/notifications that names a setting is uneditable: five switch labels, five blurbs under them, and the five pace options a Frith+ member chooses between. The plan gating (which switches need Frith+, which are locked on) is in the same file, so an editor cannot change what the free plan includes either. This is the screen that makes the "Frith is quiet on the free plan" promise concrete, and none of its wording can be adjusted without a deploy.

- **Every navigation link label, in the header, the phone menu and the footer, is config-only**  
  `config/frith-nav.php:14` · medium  
  No link anywhere in the chrome can be renamed, reordered, added or removed from the admin. That covers the desktop bar for visitors and for logged-in families, the full-screen phone menu with its explanatory sub-labels, the two menu footers, and the three footer columns. It also means the footer's placeholder links to pages that do not exist, Blog and Cookies, cannot be taken down by an editor who notices them going nowhere.

- **The consent sentence shown at registration cannot be edited, and its version has to be bumped by hand**  
  `config/frith.php:61` · medium  
  The one sentence a family reads before handing over their email, their postcode area and their children's ages is fixed in code. UK GDPR makes this the single most consequential piece of copy on the site: it is snapshotted onto every registration row at the moment of consent. An editor cannot change it, and the version string next to it has to be bumped in the same file by whoever does, so a wording change made without that step silently records new consent under the old version.

- **The Founders limit, the profile review interval and the deletion grace period are env-only settings with user-visible consequences**  
  `config/frith.php:103` · medium  
  Three numbers that families are told about directly. The Founders limit decides who gets a Founder number and the lifetime-free Frith+ that goes with it. The review interval is printed to the family as "We will ask again in about 3 months" and again in a flash message. The grace period is printed as "deleted within 30 days" on the leave screens and in the confirmation after an account is deleted. All three are read from env with a code default, so changing any of them is a server change, not an admin one.

- **All badge titles and explanations live in a PHP class, not in config and not in the admin**  
  `app/Support/Badges.php:38` · medium  
  The whole of /account/badges is uneditable: five badge names, three group headings with their blurbs, and for each badge a "what" sentence and a "note" sentence. These names also travel onto a family's public profile and onto every family card in a list, so they are among the most-read strings in the product. They are not even in config, so they are one step further from an editor than the nav or the report reasons.

- **Both transactional emails and their plain-text twins are hard-coded, including the Founder promise**  
  `resources/views/emails/founder-welcome.blade.php:32` · medium  
  The welcome email is the first thing a new family receives and it carries a lifetime commercial promise about free premium features. It cannot be edited. Nor can the login-link email, including the sentence about how long the link lasts. Each has a separate plain-text version, so any change has to be made twice and there is nothing in the admin to keep the two in step.

- **Seventeen confirmation messages families read after every action are string literals in controllers**  
  `app/Http/Controllers/ConnectionController.php:66` · medium  
  Every flash message in the product, the line a family reads immediately after joining a Journey, asking to connect, blocking somebody, filing a report, saving notifications or deleting their account, is written inline in a controller. Some carry promises with specific numbers, such as the 24-hour safeguarding response. None is editable, and they are a tier further from an editor than config.

- **The three registration outcome screens and the wizard's privacy note are hard-coded**  
  `resources/views/register/verified.blade.php:15` · medium  
  The screens a family sees after confirming their email, after unsubscribing, and after resubscribing are all hard-coded, as is the "only ever used to match you" note that the wizard shell prints under every private step. The six wizard steps themselves are fully editable, so these sit as unreachable islands inside an otherwise editable flow.

- **config/content/auth.php has no row in the pages table, so all four login screens are developer-only**  
  `config/content/auth.php:20` · small  
  Every word on /login, /forgot, /link-sent and /verified is fixed at deploy time. An editor who wants to change "Welcome back", the forgotten-password instructions, the "It works once, and for the next hour" expiry line, or the help address sentence has no screen to do it on. PageSeeder::pages() does not list 'auth', so no row is ever created, and PagesTable filters the list to that same array, so even a hand-inserted row would not appear.

- **config/content/join.php has no row, so the end-of-registration screen is developer-only**  
  `config/content/join.php:16` · small  
  /join/welcome is the screen a family lands on the moment they finish registering, and none of it can be changed without a deploy: the headline, the promise about launch day, the nudge to join Journeys, and the Frith+ footnote. The Founders wizard is otherwise fully editable through the Forms resource, so the last screen of that wizard is the one exception.

- **config/content/footer.php is read with config() rather than PageContent, so a pages row would not help it either**  
  `resources/views/components/site/footer.blade.php:13` · small  
  Four lines that appear on the bottom of every page of the site cannot be edited: the tagline, the "we do not sell your data" line, the company registration line, and the safeguarding response-time promise. Unlike auth and join, this one is not even wired through PageContent, so adding a row to the pages table and adding 'footer' to PageSeeder would change nothing. The Blade reads the config file directly.

- **Company name, contact address and postal address are config-only, and the postal address is still null**  
  `config/frith.php:34` · small  
  The contact email appears on at least fourteen screens and in the footer of every email, and the company name sits in the site footer, the page title fallback, the author meta tag and both email templates. None of it is editable. The postal address is still null, so the registered address the file itself says bulk mail needs is simply absent from every message, and nobody without a deploy can add it.

- **The nine account rail labels are a Blade-local array, outside frith-nav and outside the admin**  
  `resources/views/components/account/shell.blade.php:14` · small  
  The navigation down the side of every account screen cannot be edited or reordered. It is also the one navigation list that is not in config/frith-nav.php, so a developer looking for nav labels in the obvious place will not find these. The "Need a person?" block beside every policy page is in the same position.

- **The four statements on the block screen are an inline array in the markup**  
  `resources/views/pages/safety/block.blade.php:15` · small  
  The screen that explains what blocking does, which is the clearest safety promise in the product, lists its four guarantees from an array written inside the @foreach. An editor cannot change a word of it, and nothing about the file suggests this is content rather than layout.

## Minor

14 of them.

### faked-data (1)

- **The deletion grace period is typed as "30 days" in the two legal pages while config drives every other screen**  
  `config/content/privacy.php:104` · small  
  The Privacy Policy and the Terms both state the deletion window as a literal 30 days. The account screens render it from config, and so does the job that actually does the deleting. Change FRITH_DELETION_GRACE_DAYS and the two legal documents immediately contradict both the screen the family pressed Delete on and what the purge job does. The views already have the pattern (account/experiences.blade.php does the same with config('frith.review.months')), so the policy copy is the only place out of step.
  Live source: `config('frith.deletion.grace_days') — the same value PurgeDepartedFamilies applies to registrations.deletion_requested_at`

### broken-flows (2)

- **The 500 page's only button is labelled "Try again" and goes to the home page; the 503 page has no way onward at all**  
  `/Users/matt/Local Sites/frith/resources/views/errors/500.blade.php:34` · small  
  The copy two lines above says "If you were part way through writing something, we have kept it. Go back and it will still be there" — and then the one button throws the visitor to /, which is the opposite of going back. Both the 500 and the 503 deliberately skip the site layout, so neither has a header, a footer or any other link: resources/views/errors/503.blade.php has no actions block whatsoever. The hi-fi's /error screen carries the full header and footer. Somebody who hits a 503 mid-registration has nothing to press.

- **RegistrationExperienceController is dead code whose redirects name two routes that do not exist**  
  `/Users/matt/Local Sites/frith/app/Http/Controllers/RegistrationExperienceController.php:47` · small  
  Nothing at present — the controller has no routes (routes/web.php does not import it) and is left over from the one-screen-per-area flow the accordion replaced. But route('register.experiences') and route('register.experiences.show') are the only two route names used anywhere in app/ or resources/ that do not resolve, so the moment anybody wires this up, or copies a line out of it, it throws RouteNotFoundException. It should go.

### unbuilt-screens (5)

- **The mobile bottom tab bar drawn on every logged-in screen is not built**  
  `resources/views/components/site/header.blade.php:11` · medium  
  Every logged-in mobile frame in the hi-fi ends with a fixed four-item tab bar on white with a top border: Village, Journeys, Messages (with a coral unread dot) and You, each with its own icon. The build has only the hamburger `<details>` panel at the top. `grep -rn "tabbar\|tab-bar\|bottom-nav\|position: fixed" resources/scss resources/views` finds one hit, the open menu panel in _menu.scss:116, so on a phone there is no persistent navigation.

- **The footer's "Cookies" link has no route and silently renders as href="#"**  
  `config/frith-nav.php:102` · small  
  Cookies sits in the footer's Legal group beside Terms, Privacy and Community Guidelines, which all work. There is no `cookies` route, so it resolves to '#'. The Privacy Policy design has a "Cookies" h2 inside it, so the content exists on another page; the footer link is the dead one. A legal link that goes nowhere is the most visible kind of dead link on the site.

- **The day divider in a message thread is not built**  
  `resources/views/pages/messages/show.blade.php:32` · small  
  The hi-fi's thread draws a centred stone pill reading "Today" above the first bubble of each day. The build prints `diffForHumans()` under every single bubble instead, so a thread reads as a column of "2 days ago / 2 days ago / 1 day ago" with no sense of where one conversation ended and the next began. `grep -rn "Today" resources/views` finds nothing in the thread.

- **The "Joined this week" recency tag on a family row is not built**  
  `resources/views/components/family/card.blade.php:10` · small  
  The hi-fi marks the newest family in a Journey with an uppercase coral tag, "Joined this week" on desktop and "New" on the mobile card, beside the name and the "6 in common" pill. The build's card shows area, children, commonality and badges, with nothing about recency. `grep -rn "Joined this week" resources app` returns nothing. The pivot already carries the date that would drive it.
  Live source: `journey_registration.joined_at (set by JourneyController::join)`

- **/journeys has no "You have not joined a Journey yet" state; its only empty state is a failed search**  
  `resources/views/pages/journeys/index.blade.php:33` · small  
  The hi-fi draws that empty state at the /journeys URL: a 68px circle with a calendar icon, h1 "You have not joined a Journey yet", "Journeys are how most families find each other. Joining one makes your profile visible to the families in it — and nowhere else. You can leave at any time." and a "Browse all nine Journeys" button. The build has the equivalent copy on /village instead, and /journeys only ever shows "Nothing matches ..." for a search miss, so the icon, heading and reassurance never appear at the route the design puts them on.

### admin-coverage (6)

- **Postcode to town names, which are what families see as their location, have no admin screen and cover only the pilot**  
  `config/frith-postcodes.php:31` · medium  
  A family's location is shown to every other family as "Leigh-on-Sea area" rather than "SS9 area", and that mapping exists for seventeen Southend outcodes plus ten Kent districts the designs happened to use. Everyone else is shown their raw outcode. The file itself says the real table wants loading from the Royal Mail or ONS data before the UK-wide launch, and that there is a known wrong label in the hi-fi. None of this is reachable from the admin, so the first family in a new town cannot be given a town name without a developer and a deploy.

- **Registration validation messages, written in Frith's voice, are hard-coded in a support class**  
  `app/Support/AnswerSheet.php:45` · medium  
  The error messages on the registration wizard are deliberately gentle and specific, and they are the copy a family reads at their most frustrated. Every field label, help line and placeholder on those same screens is editable through the Forms resource; the error messages beside them are not. An editor renaming a field cannot change the error that names it.

- **A second, unrelated 30-day promise is typed straight into the data screen rather than read from the grace period**  
  `resources/views/pages/account/data.blade.php:94` · small  
  The data screen ends with a statutory promise about subject access requests, written as a bare number in the markup. It sits ten lines below a different 30 that comes from config. An editor cannot change either, and a developer changing FRITH_DELETION_GRACE_DAYS would move one 30 and leave the other, with nothing to flag that the two are unrelated.

- **The policy sidebar labels are a PHP constant, so renaming a policy page in the admin leaves the sidebar stale**  
  `app/Support/PolicyPages.php:14` · small  
  Five policy pages are editable through the Pages resource, including their headings. The sidebar that lists all five, shown on each of them, takes its labels from a const instead. Rename "Reporting & complaints" in the admin and every policy page's sidebar still says the old name.

- **All three error pages are hard-coded, and two deliberately cannot read content at all**  
  `resources/views/errors/404.blade.php:9` · small  
  The 404, 500 and 503 pages cannot be edited. For 500 and 503 that is a defensible choice, since both are written to avoid touching config or the database, but it still needs saying: the maintenance page's wording is decided at deploy time and cannot be changed during an outage. The 404 has no such constraint and could read from a page row.

- **The coming-soon block in config/frith.php promises it will move into the CMS, and is now dead code**  
  `config/frith.php:155` · small  
  Nothing, which is the problem. Around 60 lines of marketing copy sit in config under a comment saying it is the block that moves into the CMS when the admin panel is built. The admin panel is built, the copy moved to config/content/home.php, and this block is now referenced nowhere in app, resources or routes. Anyone looking for the editable home page copy may well find and change this instead.
