<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company
    |--------------------------------------------------------------------------
    */

    'company' => [
        'name' => 'Frith Community Ltd',
        'contact_email' => 'hello@frith.community',

        // Worth setting before the launch broadcast goes out. A registered
        // postal address in the footer of bulk mail is expected by the big
        // inbox providers and helps the message not look like spam.
        'postal_address' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Consent
    |--------------------------------------------------------------------------
    |
    | UK GDPR requires us to be able to show what a person agreed to, and when.
    | The version string is stored against every signup; the text is snapshotted
    | onto the row at the moment of consent so that editing this file can never
    | rewrite history. Bump the version whenever the wording below changes.
    |
    */

    'consent' => [
        'version' => '2026-08-07.v1',
        'text' => 'One email, when we launch. Nothing else, and you can leave at any time.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Waitlist behaviour
    |--------------------------------------------------------------------------
    */

    'waitlist' => [
        // How long a confirmation link stays valid.
        'confirmation_link_days' => 14,

        // Don't re-send a confirmation more than once per this many minutes,
        // so the endpoint can't be used to mailbomb someone else's address.
        'resend_cooldown_minutes' => 15,

        // Live MX lookup on the submitted address. Off in tests so the suite
        // doesn't depend on the network.
        'validate_email_dns' => env('FRITH_VALIDATE_EMAIL_DNS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Coming soon page copy
    |--------------------------------------------------------------------------
    |
    | Everything a non-developer might reasonably want to change lives here.
    | This is the block that moves into the CMS when the admin panel is built —
    | keys map straight onto content fields.
    |
    */

    'coming_soon' => [

        'meta' => [
            'title' => 'Frith — find your village',
            'description' => 'Frith connects parents and carers of children with SEND to other families nearby. Free to join. Launching across the UK in autumn 2026.',
        ],

        'status_badge' => 'Launching autumn 2026',

        'eyebrow' => 'A community for families with SEND',

        // The <br> is the designed line break. Rendered as two lines, not raw HTML.
        'headline' => ['You’re not alone.', 'Let’s find your village'],

        'standfirst' => 'Frith connects parents and carers of children with SEND to other families nearby. People who already know what an EHCP is.',

        'form' => [
            'label' => 'Your email',
            'placeholder' => 'you@example.com',
            'button' => 'Become a Frith Founder',
        ],

        // Shown under the button on the homepage.
        'founders_note' => 'Register now and Frith’s premium features stay free for your family, for good. Takes about two minutes.',

        // Null means use the artwork shipped in public/brand/img. Uploading a
        // replacement in the admin panel stores a path here.
        'hero_image' => null,
        'hero_image_alt' => 'A parent and child sitting on a hillside, watching the sun set over open countryside',

        'cards' => [
            [
                'icon' => 'people',
                'heading' => 'Families near you',
                'body' => 'Not a forum or a feed. Parents and carers a few miles away, matched on what your family is actually dealing with.',
            ],
            [
                'icon' => 'leaf',
                'heading' => 'People who have been there',
                'body' => 'Ask about an annual review or a tribunal and get an answer from someone who has filled in that form themselves.',
            ],
            [
                'icon' => 'heart',
                'heading' => 'Checked before they can message',
                'body' => 'Every adult is verified, a real person reads every report, and you choose what you share and with whom.',
            ],
        ],

        'status' => [
            'heading' => 'Where we’re up to',
            'standfirst' => 'We would rather tell you the plain version than a launch-day surprise.',
            'tagline' => 'Find Your Village.',
            'points' => [
                [
                    'lead' => 'We’re launching across the UK in autumn 2026.',
                    'rest' => 'If that slips, we will email you and say so.',
                ],
                [
                    'lead' => 'We match you on what your family is dealing with, not on a diagnosis.',
                    'rest' => 'Two families can have very different paperwork and the same Tuesday morning.',
                ],
                [
                    'lead' => 'Frith is free to join',
                    'rest' => 'and always will be for the basics.',
                ],
                [
                    'lead' => 'No ads, and we don’t sell your data.',
                    'rest' => 'Frith is a small independent team, and a real person reads anything you send us.',
                ],
            ],
        ],

        'note' => 'Frith is built for families navigating SEND first, because that is where the isolation is sharpest. It is being built for every family in time. If you are waiting on an assessment, or have no diagnosis at all, you are as welcome as anyone here.',

        'success' => [
            'heading' => 'Check your email.',
            'body' => 'We’ve sent a link to confirm it’s really you. Once you’ve clicked it you’re on the list, and we’ll email you when Frith launches.',
            'footnote' => 'Nothing arrived within a few minutes? Have a look in your spam folder, or write to us and a person will sort it out.',
        ],

        'confirmed' => [
            'heading' => 'You’re on the list.',
            'body' => 'That’s everything — there’s nothing else to do. We’ll email you once, when Frith launches. If you’d rather talk to a person before then, write to us any time.',
        ],

        'unsubscribed' => [
            'heading' => 'You’re off the list.',
            'body' => 'We won’t email you about the launch. Nothing you did was wrong, and you’re welcome back whenever you like.',
        ],
    ],
];
