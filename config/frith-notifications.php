<?php

/*
|--------------------------------------------------------------------------
| What Frith will email about
|--------------------------------------------------------------------------
|
| Five switches, and the plan decides which of them a family may touch. The
| design's claim is that "Frith is quiet on the free plan", and these are
| what makes that true rather than a sentence on a marketing page.
|
| `plus` marks the ones that need a Frith+ membership. `free` is the value a
| family on the free plan gets whether they ask for it or not, so the screen
| can show a switch as on-and-locked or off-and-locked and be honest either
| way. Nothing here can be turned on by a family who is not paying for it.
|
| Deliberately absent: streaks, "you have been away" reminders, and counts of
| what somebody has missed. On either plan. There is nowhere in this file to
| configure one, which is the point.
|
*/

return [

    'plus_asks' => [
        'label' => 'A Frith+ member asks to connect',
        'blurb' => 'The one notification the free plan includes',
        'plus' => false,
        'free' => true,
        // On for everybody and not worth a switch: a family cannot choose to
        // be told about nothing at all, or a request would sit unseen for
        // weeks and the other family would think they had been ignored.
        'locked' => true,
    ],

    'any_asks' => [
        'label' => 'Any family asks to connect',
        'blurb' => 'Be told whoever asks, free plan or Frith+',
        'plus' => true,
        'free' => false,
        'locked' => false,
    ],

    'replies' => [
        'label' => 'Someone replies to you',
        'blurb' => 'When a conversation you are in gets a reply',
        'plus' => false,
        'free' => true,
        'locked' => false,
    ],

    'journeys' => [
        'label' => 'Your journeys',
        'blurb' => 'Occasional updates from journeys you have joined',
        'plus' => true,
        'free' => false,
        'locked' => false,
    ],

    'news' => [
        'label' => 'News from Frith',
        'blurb' => 'Rare, and never marketing',
        'plus' => false,
        'free' => false,
        'locked' => false,
    ],

    /*
     * How many connection requests a family wants put in front of them.
     * Frith+ only, because on the free plan requests are not notified at all
     * and so there is no pace to set.
     */
    'pace' => [
        'day' => 'One a day',
        'three-week' => 'Three a week',
        'week' => 'One a week',
        'any' => 'As many as come',
        'paused' => 'Pause them',
    ],

];
