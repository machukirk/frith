<?php

return [

    'meta' => [
        'title' => 'Meeting another family for the first time — Frith',
        'description' => 'Meet somewhere public, tell someone where you’ll be, and leave if it doesn’t feel right — plus what Frith checks before an adult can message you.',
    ],

    'head' => [
        'eyebrow' => 'Safety',
        'title' => 'Meeting another family for the first time',
        'updated' => 'Last updated 9 September 2026 · about 4 minutes to read',
    ],

    'short_version' => [
        'label' => 'The short version',
        'body' => 'Meet somewhere public. Tell someone where you’ll be. Bring your own transport if you can. You can change your mind at any point, right up to the moment you arrive — and you never owe anyone an explanation.',
    ],

    'intro' => 'Most first meet-ups on Frith are a coffee, a walk, or a play session at a soft-play centre. They are usually unremarkable, which is the point. These are the things we’d ask you to do anyway.',

    'before_you_go' => [
        'title' => 'Before you go',
        'items' => [
            'Pick somewhere public with other people around. A café, a park, a library, a soft-play centre.',
            'Tell a friend or family member where you are going and roughly when you’ll be back.',
            'Keep the arrangements inside Frith until you have met. You don’t need to share your phone number or address to agree a time and place.',
            'If you are bringing your children, it is fine to say so and fine not to. Some families prefer to meet on their own first.',
        ],
    ],

    // :action is the name of the control on the other family's profile, so the
    // sentence and the button it points at cannot drift apart.
    'not_right' => [
        'title' => 'If something doesn’t feel right',
        'body' => 'Leave. You do not need a reason, and you do not need to be polite about it. Then use :action from the family’s profile or from the message thread. Blocking takes effect straight away, whatever we decide about the report.',
        'action' => 'Report and block',
    ],

    // :number is the emergency number, kept separate so it is emphasised
    // wherever an editor moves it in the sentence.
    'emergency' => [
        'body' => 'If you believe a child is at immediate risk, contact the police on :number first, then tell us. We will always cooperate with a safeguarding investigation.',
        'number' => '999',
    ],

    'checks' => [
        'title' => 'What Frith does and doesn’t check',
        'body' => 'Every adult on Frith is identity-verified before they can message anyone, and connections only happen when both families have asked. We do not run DBS checks, and verification is not a character reference. Please use the same judgement you would with any other parent you had just met.',
    ],

    'actions' => [
        'report' => 'Report a concern',
        'guidelines' => 'Read our Community Guidelines',
    ],
];
