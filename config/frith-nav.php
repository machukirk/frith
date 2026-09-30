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
