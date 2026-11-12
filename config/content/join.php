<?php

/*
|--------------------------------------------------------------------------
| The end of registration: /join/welcome
|--------------------------------------------------------------------------
|
| Two endings, not one. Only the first hundred registrations are issued a
| founder number, so everything that names the First Frith Families has a
| second wording underneath it for the families who came later. The screen
| picks between them on the column, the same way register/verified.blade.php
| always has.
|
*/

return [

    'meta' => [
        'title' => 'You are now a First Frith Family · Frith',
        'title_if_not_a_founder' => 'You are registered · Frith',
        'description' => 'Your Frith registration is complete.',
    ],

    'welcome' => [
        'title' => 'You are now a First Frith Family',
        'title_if_not_a_founder' => 'You are registered',
        'standfirst' => 'Check your email to verify your email address.',
        'launch_line' => 'We will email you at :email on launch day to share your Frith connections.',
        'journeys_title' => 'Join your Journeys now, before we open',
        'journeys_body' => 'Pick them today and they will already have families in them on launch day. Nobody sees your profile until Frith opens.',
        'journeys_link' => 'Browse all :count Journeys',
        'button' => 'Go to your village',
        'next' => 'Your village is waiting. We have sent a link to :email, and following it is what opens it. Nothing else is needed, and you are registered either way.',

        // The founder line is a lifetime commercial promise, and hasPlus()
        // only keeps it for a family with a number. The second line says what
        // is true for everybody else: the basics are free and stay free, which
        // is the same thing the Terms page promises.
        'footnote' => 'Frith+ stays free for you, always.',
        'footnote_if_not_a_founder' => 'Frith is free to join, and the basics stay free.',
    ],
];
