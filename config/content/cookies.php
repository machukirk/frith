<?php

/*
 * The cookies page.
 *
 * Short on purpose. Frith sets three cookies and runs no analytics, so the
 * honest version of this page is a table and four short sections. Every row
 * below was read off the code rather than copied from a template: the session
 * cookie is config/session.php, the CSRF cookie is Laravel's
 * PreventRequestForgery middleware, and the third exists because both login
 * paths call Auth::guard('founder')->login($family, remember: true).
 *
 * If a fourth cookie is ever added, this page is part of adding it.
 */

return [

    'meta' => [
        'title' => 'Cookies · Frith',
        'description' => 'Frith sets three cookies. They keep you logged in and they keep forms safe. No analytics, no advertising, and nothing for you to accept.',
    ],

    // No date in here. The "Last updated" line is generated from this page's
    // own pages.updated_at by App\Support\PolicyPages::lastUpdated, so saving
    // the page in the admin panel moves it. A typed one goes stale the first
    // time anybody edits the page.
    'head' => [
        'eyebrow' => 'Legal',
        'title' => 'Cookies',
    ],

    'short_version' => [
        'label' => 'The short version',
        'body' => 'Frith sets three cookies. They keep you logged in and they keep forms safe. We do not measure you, advertise to you, or let anybody else do either, so there is nothing here for you to accept or refuse.',
    ],

    'intro' => 'A cookie is a small file a website asks your browser to keep. Below is every cookie Frith sets, what it is for, and how long it stays.',

    'cookies' => [
        'title' => 'Every cookie we set',
        'columns' => [
            'name' => 'Name',
            'what' => 'What it does',
            'how_long' => 'How long it stays',
        ],
        'rows' => [
            [
                'name' => 'frith-session',
                'what' => 'Remembers who you are while you are using the site, so you are not asked to log in again on every page.',
                'how_long' => 'Two hours after you stop',
            ],
            [
                'name' => 'XSRF-TOKEN',
                'what' => 'Checks that a form was sent from Frith, and not by another site pretending to be you.',
                'how_long' => 'Two hours',
            ],
            [
                'name' => 'remember_founder',
                'what' => 'Keeps you logged in when you come back, so you do not need a new email link every visit. Your browser shows it with a long code after the name.',
                'how_long' => '400 days, or until you log out',
            ],
        ],
    ],

    'sections' => [
        [
            'title' => 'What we do not set',
            'paragraphs' => [
                'No analytics. We do not count visitors, measure pages or record what you click on. No advertising cookies, and nothing shared with an advertising network. Nothing on these pages is loaded from another company, so there is no third party here to watch you.',
            ],
        ],
        [
            'title' => 'Why there is no banner',
            'paragraphs' => [
                'A cookie banner is there to ask you about tracking. We do not track you, so there is nothing to ask you about. The three cookies above are the ones Frith needs to work at all.',
            ],
        ],
        [
            'title' => 'Turning them off',
            'paragraphs' => [
                'Your browser can block or delete cookies, for Frith as for any other site. Frith will then stop working. You will be logged out, you will not be able to log back in, and forms will be refused.',
            ],
        ],
        [
            'title' => 'If this changes',
            'paragraphs' => [
                'If we ever set a cookie that is not on this list, we will update this page first and say what it is for. If it is one that tracks you, we will ask you before we set it.',
            ],
        ],
    ],

    'more' => [
        'body' => 'Everything else we hold about your family, and what you can ask us to do with it, is in the Privacy Policy.',
        'link' => 'Read the Privacy Policy',
    ],

    'footer' => [
        'label' => 'Questions about any of this:',
    ],
];
