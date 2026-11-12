<?php

return [

    /*
     * The search snippet and the link preview, which are the same promise as
     * the page and were missed when the page was rewritten. The old sentence
     * said Frith+ "gives you more ways to find and connect with families",
     * which is the "look beyond your Frith suggestions" claim under another
     * name: nothing in the code holds back who a family can find or ask. It
     * was present tense as well, in front of a page that now says the plan is
     * not open. Keep this agreeing with 'intro' and 'benefits' below.
     */
    'meta' => [
        'title' => 'Frith+: discover more of your village',
        'description' => 'Frith+ is an optional membership, and it is not open yet. Finding families, asking to connect and messaging them are free, and they stay free.',
    ],

    'intro' => [
        'title' => 'Discover more of your village',
        'standfirst' => 'Finding families, asking to connect and messaging them are free, and they stay free. Frith+ adds more ways to hear who wants to meet you.',
    ],

    /*
     * Only what the code actually gates.
     *
     * This list used to sell three things Frith does not do. "Specialist
     * Journeys" was the worst of them: the journeys table has a slug, a
     * title, a blurb, a position and an archived_at, and nothing else, so
     * there is no such thing as a specialist Journey to withhold. "See how
     * many things you have in common" is on every family card already, free,
     * through App\Support\FamilyCard::commonality(). "View requests and
     * choose who to connect with" is the Requests screen, which shows every
     * pending ask to every family with no plan check at all.
     *
     * What is left is the three switches App\Support\NotificationSettings
     * really does hold back, which are the ones marked 'plus' in
     * config/frith-notifications.php. If an item is added here, there must be
     * a line of code that enforces it first.
     *
     * Future tense throughout, because Frith+ is not open. See 'cta'.
     */
    'benefits' => [
        'label' => 'What Frith+ will add when it opens:',
        'items' => [
            [
                'title' => 'Hear about every ask',
                'detail' => 'The free plan tells you when a Frith+ family asks to connect. Frith+ tells you about everyone who asks.',
            ],
            [
                'title' => 'Set your own pace',
                'detail' => 'Choose how often those emails arrive, from one a week to as many as come, or pause them.',
            ],
            [
                'title' => 'News from your Journeys',
                'detail' => 'Now and then, from the Journeys you have joined.',
            ],
        ],
    ],

    /*
     * Not a buy button. Payment screens are deferred, and the old button
     * pointed at a frith-plus.choose route that was never written, so the
     * one control on the page did nothing at all.
     *
     * Registering is the real thing a visitor can do today, so that is what
     * the button does. The note above it says why there is nothing to buy.
     */
    'cta' => [
        'label' => 'Frith+ is not open yet',
        'body' => 'It opens when Frith opens. There is nothing to pay for today and nothing to choose. When it does open we will tell you what it costs, and you can decide then.',
        'button' => 'Register with Frith',
    ],
];
