<?php

return [

    'meta' => [
        'title' => 'How Frith works — from registering to your first coffee',
        'description' => 'Six steps from registering to your first coffee, and you decide at every one. Nothing happens to you on Frith without you asking for it.',
    ],

    'head' => [
        'eyebrow' => 'How it works',
        'title' => 'From registering to your first coffee',
        'standfirst' => 'Six steps, and you decide at every one. Nothing happens to you on Frith without you asking for it.',
    ],

    /*
     * The six steps, numbered in the order they appear. 'note' is the teal
     * reassurance line under a step, and is optional — leave it empty and the
     * step renders without one.
     */
    'steps' => [
        [
            'title' => 'You answer a few questions',
            'body' => 'About your family, what you are navigating, and what you are hoping to find. It takes about a minute for the essentials, and you can stop there.',
            'note' => 'Your answers about what you are navigating are never shown to anyone. They are used to find your matches, then kept private.',
        ],
        [
            'title' => 'We verify you are a real adult',
            'body' => 'Before you can message a single family. It is not a character reference and we say so — but it means nobody on Frith is anonymous.',
            'note' => null,
        ],
        [
            'title' => 'You choose your Journeys',
            'body' => 'A Journey is a group of families navigating one thing. Joining one is what makes your profile visible to the families in it — and nowhere else.',
            'note' => 'You can be in as many as you like, and leave any of them at any time.',
        ],
        [
            'title' => 'You see families, and they see you',
            'body' => 'Their name, their general area, their children’s ages. No distance, no postcode, no photographs of children.',
            'note' => null,
        ],
        [
            'title' => 'You both ask to connect',
            'body' => 'If you ask and they ask too, we introduce you. If only one of you asks, nothing happens and nobody is told.',
            'note' => 'Up to 10 open requests at a time, so every family gets a real reply rather than a queue.',
        ],
        [
            'title' => 'You talk, and then you meet if you want to',
            'body' => 'No read receipts, no last-seen, no typing indicators. When you arrange to meet, we show you our safety guide without being asked.',
            'note' => null,
        ],
    ],

    'cta' => [
        'title' => 'That is the whole product',
        'body' => 'No feed, no groups, no swiping, no ranking. If it sounds small, that is because it is — the point is the people, not the software.',
        'button' => 'Register',
    ],
];
