<?php

/*
|--------------------------------------------------------------------------
| Site navigation
|--------------------------------------------------------------------------
|
| Header and footer links. A `route` that does not exist yet renders as a
| dead anchor rather than throwing, so the footer can carry the whole
| designed sitemap while the pages behind it are still being built.
|
*/

return [

    'primary' => [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'How it works', 'route' => 'how-it-works'],
        ['label' => 'Safety', 'route' => 'meet-up-safety'],
    ],

    /*
     * The phone menu. It is its own list rather than the top bar's, because a
     * full screen has room for the two the bar has to leave out, and for a
     * line of context under the ones whose name does not explain them.
     */
    'menu' => [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'How it works', 'route' => 'how-it-works'],
        ['label' => 'Journeys', 'route' => 'journeys', 'meta' => 'Nine groups of families'],
        ['label' => 'Safety', 'route' => 'meet-up-safety'],
        ['label' => 'Frith+', 'route' => 'frith-plus'],
    ],

    // The same menu for a family who is logged in. The designs put a count
    // beside Journeys and Messages; those wait until there is something true
    // to count, because a menu that says "1 new" when nothing is new is worse
    // than one that says nothing.
    'menu_founder' => [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'Your village', 'route' => 'village'],
        ['label' => 'Journeys', 'route' => 'journeys'],
        ['label' => 'Messages', 'route' => 'messages'],
    ],

    // Under the buttons, for somebody who is not logged in.
    'menu_foot' => [
        ['label' => 'Help', 'route' => 'help'],
        ['label' => 'Privacy', 'route' => 'privacy'],
        ['label' => 'Safeguarding', 'route' => 'reporting'],
    ],

    // …and for somebody who is.
    'menu_foot_founder' => [
        ['label' => 'Your account', 'route' => 'account'],
        ['label' => 'Frith+ membership', 'route' => 'frith-plus'],
        ['label' => 'Help centre', 'route' => 'help'],
    ],

    'footer' => [
        [
            'title' => 'Frith',
            'links' => [
                ['label' => 'About', 'route' => 'about'],
                ['label' => 'How it works', 'route' => 'how-it-works'],
                ['label' => 'Journeys', 'route' => 'journeys'],
                ['label' => 'Frith+', 'route' => 'frith-plus'],
                ['label' => 'Blog', 'route' => 'blog'],
            ],
        ],
        [
            'title' => 'Support',
            'links' => [
                ['label' => 'Help centre', 'route' => 'help'],
                ['label' => 'Meet-up safety guide', 'route' => 'meet-up-safety'],
                ['label' => 'Reporting & complaints', 'route' => 'reporting'],
                ['label' => 'Contact us', 'url' => 'mailto:hello@frith.community'],
            ],
        ],
        [
            'title' => 'Legal',
            'links' => [
                ['label' => 'Terms of Service', 'route' => 'terms'],
                ['label' => 'Privacy Policy', 'route' => 'privacy'],
                ['label' => 'Community Guidelines', 'route' => 'community-guidelines'],
                ['label' => 'Cookies', 'route' => 'cookies'],
            ],
        ],
    ],
];
