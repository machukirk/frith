<?php

/*
|--------------------------------------------------------------------------
| Help centre — /help
|--------------------------------------------------------------------------
|
| Topic links carry a `route` rather than a URL, resolved through
| App\Support\SiteNavigation so that a help article that has not been written
| yet renders as a dead anchor instead of throwing. `route => null` means
| "designed, no page behind it yet".
|
*/

return [

    'meta' => [
        'title' => 'Help centre — Frith',
        'description' => 'Getting started, connecting, safety, your account, Frith+ and what to do when something is broken. If your answer is not here, a person will reply.',
    ],

    'head' => [
        'eyebrow' => 'Help centre',
        'title' => 'How can we help?',
        'standfirst' => 'Most answers are here. If yours is not, a person will reply — usually the same day.',
        'search_label' => 'Search help',
        'search_placeholder' => 'Search help — try "block", "postcode" or "cancel"',
    ],

    'search' => [
        'found' => ':count result for “:query”|:count results for “:query”',
        'empty' => 'Nothing matched “:query”. Try a shorter word, or write to us and a person will reply — usually the same day.',
    ],

    'topics' => [
        [
            'title' => 'Getting started',
            'links' => [
                ['label' => 'What Frith is, and is not', 'route' => 'about'],
                ['label' => 'Registering and verifying', 'route' => null],
                ['label' => 'Choosing your Journeys', 'route' => 'journeys'],
                ['label' => 'What others can see', 'route' => 'privacy'],
            ],
        ],
        [
            'title' => 'Connecting',
            'links' => [
                ['label' => 'How matching works', 'route' => 'how-it-works'],
                ['label' => 'Asking to connect', 'route' => null],
                ['label' => 'The 10-request limit', 'route' => null],
                ['label' => 'Messaging and meeting up', 'route' => null],
            ],
        ],
        [
            'title' => 'Safety',
            'links' => [
                ['label' => 'Meet-up safety guide', 'route' => 'meet-up-safety'],
                ['label' => 'Blocking someone', 'route' => null],
                ['label' => 'Reporting a family', 'route' => 'reporting'],
                ['label' => 'Keeping children safe', 'route' => 'community-guidelines'],
            ],
        ],
        [
            'title' => 'Your account',
            'links' => [
                ['label' => 'Editing your profile', 'route' => null],
                ['label' => 'Notifications and quiet hours', 'route' => null],
                ['label' => 'Badges on your profile', 'route' => null],
                ['label' => 'Downloading your data', 'route' => 'privacy'],
                ['label' => 'Leaving Frith', 'route' => null],
            ],
        ],
        [
            'title' => 'Frith+',
            'links' => [
                ['label' => 'What Frith+ adds', 'route' => 'frith-plus'],
                ['label' => 'Prices and billing', 'route' => null],
                ['label' => 'Cancelling', 'route' => null],
                ['label' => 'First Frith Family', 'route' => null],
            ],
        ],
        [
            'title' => 'Trouble',
            'links' => [
                ['label' => 'I cannot log in', 'route' => null],
                ['label' => 'Nothing is showing near me', 'route' => null],
                ['label' => 'I did not get an email', 'route' => null],
                ['label' => 'Something looks broken', 'route' => null],
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
            'standfirst' => 'We read every one. There is no ticket number — a person replies.',

            'name' => 'Your name',
            'email' => 'Your email',
            'topic' => 'What is it about?',
            'message' => 'Your message',
            'message_help' => 'Tell us what has happened. There is no wrong way to write it.',
            'button' => 'Send',
            'reassurance' => 'We store nothing beyond the email itself, and we never add you to a mailing list.',
            'sent' => 'Thank you — that has gone to a person, and you will hear back. Usually the same day.',

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
