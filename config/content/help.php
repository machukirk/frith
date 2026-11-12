<?php

/*
|--------------------------------------------------------------------------
| Help centre: /help
|--------------------------------------------------------------------------
|
| A topic link is one of two things. With a `route` it is an ordinary link to
| a page, resolved through App\Support\SiteNavigation. With an `answer` it is
| a disclosure opened in place on this page, and `anchor` is the id a search
| result points at, so it has to stay put once it has shipped.
|
| There used to be a third kind: `route => null` and nothing else, meaning
| "designed, no page behind it yet". Sixteen of the twenty-five links were
| that, and SiteNavigation turns a missing route into '#', so two thirds of
| the help centre did nothing when a family pressed it. Do not put one back.
| A topic with no page behind it gets an answer here instead. Help articles
| have no CMS of their own and are not getting one for this release.
|
| Answers are written from what the code does, including where the honest
| answer is that something is not built. If a feature arrives, the answer is
| wrong until somebody changes it here and in a migration.
|
| Changing any of this on its own changes nothing a visitor sees: config is
| the floor and the stored page is layered over it. See the migration dated
| 2026_11_12 for the shape that takes.
|
*/

return [

    'meta' => [
        'title' => 'Help centre · Frith',
        'description' => 'Getting started, connecting, safety, your account, Frith+ and what to do when something is broken. If your answer is not here, a person will reply.',
    ],

    'head' => [
        'eyebrow' => 'Help centre',
        'title' => 'How can we help?',
        'standfirst' => 'Most answers are here. If yours is not, a person will reply, usually the same day.',
        'search_label' => 'Search help',
        // Every word offered here has to find something. The search reads
        // topic labels and card titles, and nothing on this page mentions a
        // postcode, so the old example landed on the empty state. Check a
        // replacement against App\Support\HelpSearch before changing it.
        'search_placeholder' => 'Search help, try "block", "badges" or "cancel"',
    ],

    'search' => [
        'found' => ':count result for “:query”|:count results for “:query”',
        'empty' => 'Nothing matched “:query”. Try a shorter word, or write to us and a person will reply, usually the same day.',
    ],

    'topics' => [
        [
            'title' => 'Getting started',
            'links' => [
                ['label' => 'What Frith is, and is not', 'route' => 'about'],
                [
                    'label' => 'Registering and verifying',
                    'anchor' => 'registering-and-verifying',
                    'answer' => 'Registering is six short screens, and each one saves on its own, so you can stop halfway and come back without losing anything. About ten minutes after you start we email you. That message says you are registered and carries a link that confirms the address is yours. You are registered either way, but until you follow that link you are not on the list the launch email goes to.',
                ],
                ['label' => 'Choosing your Journeys', 'route' => 'journeys'],
                ['label' => 'What others can see', 'route' => 'privacy'],
            ],
        ],
        [
            'title' => 'Connecting',
            'links' => [
                ['label' => 'How matching works', 'route' => 'how-it-works'],
                [
                    'label' => 'Asking to connect',
                    'anchor' => 'asking-to-connect',
                    'answer' => 'You can ask any family you share a Journey with. Open their profile, press the button, and they will see it the next time they look. Nothing else happens until they answer, and while it is unanswered you can take the request back from your Requests screen.',
                ],
                // The number here is App\Http\Controllers\ConnectionController
                // ::PENDING_LIMIT and must not be written from memory. This
                // answer said "there is no limit" for a day, which was true
                // when it was written and false by the time it shipped,
                // because the cap was built in the same week. If the constant
                // moves, this moves with it, and so does the label.
                [
                    'label' => 'The 10-request limit',
                    'anchor' => 'the-10-request-limit',
                    'answer' => 'Ten. It counts the asks still waiting for a reply, not the asks you have ever made. When one is answered, or you take it back from your Requests screen, a place frees up and you can ask somebody else. If you are already at ten, we name the families you are waiting on, so you know which ask to take back. A family you have asked, or who has asked you, also drops out of your suggestions, so the same name does not keep coming round.',
                ],
                [
                    'label' => 'Messaging and meeting up',
                    'anchor' => 'messaging-and-meeting-up',
                    'answer' => 'Messages open once both families have said yes, and not before. A message is plain text, up to two thousand characters, with nothing to attach. Meeting up is arranged between the two of you, and the meet-up safety guide is worth reading first.',
                ],
            ],
        ],
        [
            'title' => 'Safety',
            'links' => [
                ['label' => 'Meet-up safety guide', 'route' => 'meet-up-safety'],
                [
                    'label' => 'Blocking someone',
                    'anchor' => 'blocking-someone',
                    'answer' => 'Blocking is on every family’s profile. It needs no reason, the other family is never told, and from then on they cannot see you and you will not see them. Your blocks are listed under Your data in your account, where you can undo one, and unblocking is just as quiet.',
                ],
                ['label' => 'Reporting a family', 'route' => 'reporting'],
                ['label' => 'Keeping children safe', 'route' => 'community-guidelines'],
            ],
        ],
        [
            'title' => 'Your account',
            'links' => [
                [
                    'label' => 'Editing your profile',
                    'anchor' => 'editing-your-profile',
                    'answer' => 'Everything registration asked you can be changed from your account. Each of the six sections opens the screen you filled in the first time, with the same wording and the same options. Every few months we will ask whether what your family is navigating still sounds right, and telling us it has not changed counts as an answer.',
                ],
                [
                    'label' => 'Notifications and quiet hours',
                    'anchor' => 'notifications-and-quiet-hours',
                    'answer' => 'There are no quiet hours. There is no setting for the time of day an email arrives and we have not built one. What you can change is in your account under Notifications: replies, requests to connect, updates from your Journeys and news from Frith. Being told when a Frith+ member asks to connect is the one that cannot be turned off, or a request could sit unseen for weeks.',
                ],
                [
                    'label' => 'Badges on your profile',
                    'anchor' => 'badges-on-your-profile',
                    'answer' => 'Badges describe, they do not reward. Nothing is counted and nothing expires. Most are worked out from what we already know, such as when you joined or whether your profile is filled in, and Helping hand is the one you choose. You can hide any of them from your profile, and nobody is told that you have.',
                ],
                ['label' => 'Downloading your data', 'route' => 'privacy'],
                [
                    'label' => 'Leaving Frith',
                    'anchor' => 'leaving-frith',
                    'answer' => 'Your account has a screen under Your data that explains what leaving does before it does it. Your profile stops being visible straight away, you leave your Journeys, and any unanswered requests are closed. The rest of your record is deleted after 30 days. Messages you have already sent stay in the other family’s inbox, because we cannot take your words out of somebody else’s conversation.',
                ],
            ],
        ],
        [
            'title' => 'Frith+',
            'links' => [
                ['label' => 'What Frith+ adds', 'route' => 'frith-plus'],
                [
                    'label' => 'Prices and billing',
                    'anchor' => 'prices-and-billing',
                    'answer' => 'There is nothing to pay. Frith has no payment screen, takes no card details and bills nobody. Joining, your profile, your Journeys, connecting and messaging are free and will stay free. When Frith+ goes on sale we will say what it costs before anyone is asked for anything.',
                ],
                [
                    'label' => 'Cancelling',
                    'anchor' => 'cancelling',
                    'answer' => 'There is no subscription to cancel, because nobody is paying for Frith+ yet. If you want fewer emails, every message we send carries an unsubscribe link, and you can change which ones you get in your account under Notifications. If you want to go altogether, see Leaving Frith under Your account.',
                ],
                [
                    'label' => 'First Frith Family',
                    'anchor' => 'first-frith-family',
                    'answer' => 'It is the badge for the first hundred families to register, and a Founder number is what marks one. A number is given out once and never reused, so Founder #47 stays true even if number 12 later leaves. Frith+ is free for those families for good, and once the hundred are gone no more are given out.',
                ],
            ],
        ],
        [
            'title' => 'Trouble',
            'links' => [
                [
                    'label' => 'I cannot log in',
                    'anchor' => 'i-cannot-log-in',
                    'answer' => 'Registering never asked for a password, so most families do not have one. On the login screen, ask us to email you a link instead. It works once and lasts an hour, and you can ask for another whenever you need to. We answer the same way whether or not we find an account, so being told a link is on its way is not proof the address was right.',
                ],
                [
                    'label' => 'Nothing is showing near me',
                    'anchor' => 'nothing-is-showing-near-me',
                    'answer' => 'You are only ever shown families you share a Journey with. If you have not joined one there is nobody to show, and joining is also what makes your own profile visible to the others in it. There is no radius to widen and no search of everybody nearby, which is deliberate, so a quiet Journey is one that is still small.',
                ],
                [
                    'label' => 'I did not get an email',
                    'anchor' => 'i-did-not-get-an-email',
                    'answer' => 'The email after registering is sent about ten minutes later on purpose, so it does not arrive while you are still filling the form in. If it is not there after that, look in spam or promotions. You are registered either way, and if it still has not come, write to us using the form below and a person will look.',
                ],
                [
                    'label' => 'Something looks broken',
                    'anchor' => 'something-looks-broken',
                    'answer' => 'Tell us, using the form below with Something is broken chosen. It helps to say what you were doing, what you expected and what happened instead, and whether you were on a phone or a computer. It comes to the people who build Frith, so there is no ticket number and nothing to chase.',
                ],
            ],
        ],
    ],

    'contact' => [
        'eyebrow' => 'Contact us',
        'title' => 'Write to a person',
        'standfirst' => 'There is no ticket number and no chatbot. Emails come to the small team who build and moderate Frith.',

        // A card with no `email` falls back to frith.company.contact_email, so
        // the general address stays in one place.
        'form' => [
            'title' => 'Send us a message',
            'standfirst' => 'We read every one. There is no ticket number, just a person who replies.',

            'name' => 'Your name',
            'email' => 'Your email',
            'topic' => 'What is it about?',
            'message' => 'Your message',
            'message_help' => 'Tell us what has happened. There is no wrong way to write it.',
            'button' => 'Send',
            'reassurance' => 'We store nothing beyond the email itself, and we never add you to a mailing list.',
            'sent' => 'Thank you. That has gone to a person, and you will hear back. Usually the same day.',

            // The value is what gets recorded and routed on, so it is fixed.
            // The label is free to change.
            'topics' => [
                ['value' => 'A general question', 'label' => 'A general question'],
                ['value' => 'A safety concern', 'label' => 'A safety concern'],
                ['value' => 'Something is broken', 'label' => 'Something is broken'],
                ['value' => 'My account', 'label' => 'My account'],
            ],

            // Safety goes where the page says it goes. Anything not listed
            // falls back to frith.company.contact_email.
            'inboxes' => [
                'A safety concern' => 'support@frith.community',
            ],
        ],

        'cards' => [
            [
                'title' => 'General questions',
                'email' => null,
                'detail' => 'Usually the same day, always within two working days.',
            ],
            [
                'title' => 'Safety concerns',
                'email' => 'support@frith.community',
                'detail' => 'Read by a person, usually within 24 hours, seven days a week.',
            ],
            [
                'title' => 'Your data',
                'email' => 'support@frith.community',
                'detail' => 'Data requests answered within 30 days, usually much sooner.',
            ],
        ],

        // :number is rendered in bold. The layout sets format-detection to
        // telephone=no, so it is deliberately not a tel: link.
        'emergency' => [
            'number' => '999',
            'body' => 'If you believe a child is at immediate risk, contact the police on :number first, then tell us. We will always cooperate with a safeguarding investigation.',
        ],
    ],
];
