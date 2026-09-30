<?php

return [

    'meta' => [
        'title' => 'Terms of Service — Frith',
        'description' => 'The terms you agree to when you use Frith, written to be read rather than survived: who can join, what you agree to, how money works, and how to leave.',
    ],

    'head' => [
        'eyebrow' => 'Legal',
        'title' => 'Terms of Service',
        'doc_meta' => 'Last updated 9 September 2026 · about 6 minutes to read',
    ],

    'short_version' => [
        'label' => 'The short version',
        'body' => 'Be kind, be honest about who you are, and look after other people’s information as carefully as your own. Frith is free to join. We can remove someone who makes this place unsafe, and you can leave whenever you like.',
    ],

    'intro' => 'These are the terms you agree to when you use Frith. We have written them to be read, not to be survived. Where a term has a real consequence for you, we say what it is.',

    /*
     * Each section is a heading plus any of: paragraphs, a bulleted list, and a
     * single highlighted callout. They render in that order.
     */
    'sections' => [
        [
            'title' => 'Who can use Frith',
            'paragraphs' => [
                'You must be 18 or over, and you must be a parent, carer, guardian or other adult with a caring role for a child. We verify that you are a real adult before you can message anyone. Verification confirms identity — it is not a character reference, and we do not run DBS checks.',
                'One account per adult. Do not create an account on someone else’s behalf, and do not share your login with anyone, including your partner. If two adults in a household both want to be here, that is two accounts.',
            ],
        ],
        [
            'title' => 'What you agree to do',
            'items' => [
                'Be truthful about who you are and about your family.',
                'Treat other families the way you would want to be treated on a difficult day.',
                'Keep other families’ information private. What is shared with you on Frith stays on Frith.',
                'Never share information about someone else’s child — not their name, their diagnosis, their school, or their photograph.',
                'Tell us if you see something that worries you.',
            ],
        ],
        [
            'title' => 'What you agree not to do',
            'items' => [
                'Harass, threaten, bully or abuse another family.',
                'Advertise, sell, recruit, fundraise or promote anything.',
                'Give medical, legal or educational advice as though you were qualified to, unless you are and you say so.',
                'Screenshot or repost anyone else’s profile or messages anywhere.',
                'Try to work out where someone lives, or contact them outside Frith without being asked.',
                'Use Frith to find dates, partners or anything other than family friendship.',
            ],
        ],
        [
            'title' => 'Money',
            'paragraphs' => [
                'Frith is free to join and always will be for the basics — building a profile, joining the Journeys you choose, connecting with families who ask you too, and messaging them. Frith+ is an optional membership that adds more ways to look. Prices are shown before you pay and renewals are always emailed to you in advance.',
                'If you registered before we opened, Frith+ is free for your family for good. That is a promise, not an introductory offer.',
            ],
        ],
        [
            'title' => 'Removing an account',
            'paragraphs' => [
                'We can suspend or remove an account that breaks these terms. For anything involving a child’s safety we act first and ask questions afterwards, and we will always cooperate with the police or a local authority safeguarding team.',
                'You can leave at any time from your account. Your profile disappears from Journeys and search straight away. Messages you have already sent stay in the other family’s inbox — we cannot remove words from someone else’s conversation. Everything else is deleted within 30 days.',
            ],
        ],
        [
            'title' => 'What we do not promise',
            'callout' => 'Frith introduces families. We cannot promise that you will find someone near you, that anyone will reply, or that a connection will become a friendship. We also cannot vouch for how another family will behave. Please use the same judgement you would with any other parent you had just met.',
        ],
        [
            'title' => 'Changes to these terms',
            'paragraphs' => [
                'If we change something that affects you, we will email you at least 14 days before it takes effect and say plainly what has changed. We will not bury a material change in a version note.',
            ],
        ],
        [
            'title' => 'The legal bits',
            'paragraphs' => [
                'Frith is operated by Frith Community Ltd, registered in England and Wales. These terms are governed by the law of England and Wales. Nothing here removes rights you have as a consumer.',
            ],
        ],
    ],

    'closing' => 'Something here unclear? Write to :email and we will explain it in plainer words — and probably rewrite this page.',
];
