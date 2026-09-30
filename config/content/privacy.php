<?php

return [

    'meta' => [
        'title' => 'Privacy Policy — Frith',
        'description' => 'What Frith holds about your family, why we hold it, who sees it, and how to get a copy or have it removed. We do not sell data, and we never will.',
    ],

    'head' => [
        'eyebrow' => 'Legal',
        'title' => 'Privacy Policy',
        'doc_meta' => 'Last updated 9 September 2026 · about 7 minutes to read',
    ],

    // The address the policy points people at for data requests. Kept here
    // rather than read from config because the company contact address is
    // hello@ and this policy asks for support@.
    'data_email' => 'support@frith.community',

    'short_version' => [
        'label' => 'The short version',
        'body' => 'We hold as little as we can. The things you tell us about what your family is navigating are used to find your matches and are never shown to anyone. We do not sell data.',
    ],

    'intro' => 'This explains what we hold, why, who sees it, and how to get it back or have it removed. Frith Community Ltd is the data controller.',

    'holdings' => [
        'title' => 'What we hold, and who sees it',
        'columns' => [
            'what' => 'What',
            'why' => 'Why we hold it',
            'who' => 'Who sees it',
        ],
        'rows' => [
            [
                'what' => 'Your name or nickname',
                'why' => 'So families know what to call you',
                'who' => 'Families in your Journeys',
            ],
            [
                'what' => 'Your email address',
                'why' => 'To reply, and to tell you about a connection',
                'who' => 'Only us',
            ],
            [
                'what' => 'The first part of your postcode',
                'why' => 'To introduce you to families nearby',
                'who' => 'Nobody — we show a place name',
            ],
            [
                'what' => "Your children's month and year of birth",
                'why' => 'To match you with families at a similar stage',
                'who' => 'Ages only, to families in your Journeys',
            ],
            [
                'what' => 'What your family is navigating',
                'why' => 'To find you good matches',
                'who' => 'Nobody directly — but a Journey you join is visible to its families',
            ],
            [
                'what' => 'Your interests and how you like to meet',
                'why' => 'To make meet-ups work for your family',
                'who' => 'Families you connect with',
            ],
            [
                'what' => 'Your messages',
                'why' => 'So conversations work',
                'who' => 'Only you and the family you are talking to',
            ],
            [
                'what' => 'Reports you make',
                'why' => 'To keep Frith safe',
                'who' => 'Our moderation team',
            ],
        ],
    ],

    // Each section is a heading, then its paragraphs, then its bullets. Use
    // :email in a paragraph for the data address above.
    'sections' => [
        [
            'title' => 'Special category data',
            'paragraphs' => [
                'What your family is navigating may say something about health or disability, which UK data protection law treats as special category data. We hold it only because you have chosen to give it to us, and we use it to find your matches. We never display your answers on your profile. Note that joining a Journey is itself visible to the families in it — a Journey named for a particular experience will tell them something about your family, which is why joining is always your choice and you can leave at any time. You can remove your answers at any point and keep your account.',
            ],
        ],
        [
            'title' => 'Children',
            'paragraphs' => [
                'Frith holds no profiles for children. We never ask for a child’s name, their diagnosis, their school or a photograph of them. We hold a month and year of birth so we can keep an age accurate, and other families only ever see the age.',
            ],
        ],
        [
            'title' => 'Who we share it with',
            'paragraphs' => [
                'Nobody, for money, ever. We use a small number of providers to run Frith — hosting, email delivery, payment processing and identity verification — and they only ever see what they need to do their part. We will disclose information to the police or a safeguarding authority where there is a risk to a child, and we would do so without hesitation.',
            ],
        ],
        [
            'title' => 'How long we keep it',
            'items' => [
                'Your account and profile: while your account is open.',
                'After you leave: deleted within 30 days, except messages already delivered to another family.',
                'Reports and safety records: up to six years, because we may need to explain a decision.',
                'Payment records: as long as tax law requires, usually six years.',
            ],
        ],
        [
            'title' => 'Your rights',
            'paragraphs' => [
                'You can ask for a copy of everything we hold, correct anything wrong, remove anything you have given us, or have your account deleted. Requests are answered within 30 days and usually much sooner — most of it you can do yourself from your account.',
                'If you are unhappy with how we have handled your information, tell us at :email. You can also complain to the Information Commissioner’s Office, and you do not need to go through us first.',
            ],
        ],
        [
            'title' => 'Cookies',
            'paragraphs' => [
                'We use the cookies needed to keep you logged in and to keep Frith secure. We do not track you across other websites.',
            ],
        ],
    ],

    'footer' => [
        'label' => 'Data questions:',
    ],
];
