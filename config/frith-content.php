<?php

/*
|--------------------------------------------------------------------------
| Site copy — the floor
|--------------------------------------------------------------------------
|
| Every word on the public pages, as it ships. This is seeded into the pages
| table on install and edited in the admin panel from then on; it stays here
| as the fallback, so a fresh install or a page nobody has opened yet still
| renders sentences rather than blanks.
|
| Taken from the hi-fi. Structure is code — which sections a page has and in
| what order — because a section is a designed thing, not a free-form block.
|
*/

return [

    'footer' => [
        'tagline' => 'Families Finding Families',
        'blurb' => 'Frith is a limited company. We are not venture-funded and we do not sell your data.',
        'registration' => 'Registered in England & Wales',
        'safeguarding' => 'Safeguarding concerns are read by a person, usually within 24 hours.',
    ],

    'home' => [

        'meta' => [
            'title' => 'Frith — find your people, find your village',
            'description' => 'Frith helps those raising children connect with others who understand their experiences — matching families around what they have in common, not just where they live.',
        ],

        'hero' => [
            'eyebrow' => 'Shared experiences. Real connection.',
            'headline' => ['Find your people.', 'Find your village'],
            'standfirst' => 'Frith helps those raising children connect with others who understand their experiences — matching families around what they have in common, not just where they live.',
            'primary_cta' => 'Register',
            'secondary_cta' => 'How it works',
            'note' => 'Register as a First Frith Family and you’ll never pay for Frith+ membership.',
            'image_alt' => 'A parent and child sitting on a hillside, watching the sun set over open countryside',
        ],

        'pillars' => [
            [
                'icon' => 'people',
                'title' => 'Families near you',
                'body' => 'Not a forum or a feed. Parents a few miles away, matched on what your family is actually dealing with.',
            ],
            [
                'icon' => 'leaf',
                'title' => 'People who have been there',
                'body' => 'Ask about an annual review and get an answer from someone who has filled in that form themselves.',
            ],
            [
                'icon' => 'shield',
                'title' => 'Checked before they can message',
                'body' => 'Every adult is verified, a real person reads every report, and you choose what you share.',
            ],
        ],

        'how_it_works' => [
            'eyebrow' => 'How it works',
            'title' => 'Three steps, and you decide at every one',
            'standfirst' => 'Frith never puts you in front of a stranger without your say. Connections only happen when both families ask.',
            'steps' => [
                [
                    'title' => 'Tell us what you are navigating',
                    'body' => 'A few questions about your family and the things you would like support with. Your answers stay private.',
                ],
                [
                    'title' => 'We introduce you to families nearby',
                    'body' => 'We look at what you have in common: your experiences first, then your children’s ages, then how close you are.',
                ],
                [
                    'title' => 'You both choose to connect',
                    'body' => 'If you ask to connect and they ask too, we introduce you and you can message. If only one of you asks, nothing happens.',
                ],
            ],
            'note' => 'No swiping, no ranking, no one deciding who is worth your time but you.',
        ],

        'journeys' => [
            'eyebrow' => 'Journeys',
            'title' => 'Find the families going through the same thing',
            'standfirst' => 'A Journey is a group of families navigating one particular thing. You choose which to join, and you can change them whenever you like.',
            'items' => [
                ['title' => 'Waiting for an autism assessment', 'families' => 7],
                ['title' => 'Starting the EHCP process', 'families' => 9],
                ['title' => 'Sleep challenges', 'families' => 11],
                ['title' => 'Dads of SEND children', 'families' => 7],
                ['title' => 'School refusal', 'families' => 6],
                ['title' => 'Primary to secondary', 'families' => 8],
                ['title' => 'Tribunal & appeals', 'families' => 4],
                ['title' => 'Newly diagnosed', 'families' => 5],
                ['title' => 'Waiting for a diagnosis', 'families' => 5],
            ],
            'note' => 'These are all nine. You can be in as many as you like, and leave any of them at any time.',
            'link' => 'See who is in each Journey',
        ],

        'visibility' => [
            'eyebrow' => 'What others see',
            'title' => 'You choose what you share, and most of it stays private',
            'standfirst' => 'The things that help us match you are not the things other families see. That is deliberate.',
            'shown' => [
                'title' => 'Other families see',
                'standfirst' => 'Four things, and nothing else.',
                'items' => [
                    ['label' => 'Your family name', 'detail' => 'The name you choose, not your legal one.'],
                    ['label' => 'Your rough area', 'detail' => 'The first part of your postcode, never the whole thing.'],
                    ['label' => 'The Journeys you are in', 'detail' => 'Only the ones you chose to join.'],
                    ['label' => 'How much you have in common', 'detail' => 'A number, not a list.'],
                ],
            ],
            'hidden' => [
                'title' => 'Never shown to anyone',
                'standfirst' => 'Used to find your matches, then kept private.',
                'items' => [
                    ['label' => 'Your answers about family life', 'detail' => 'What you told us you are navigating.'],
                    ['label' => 'Your exact location', 'detail' => 'We only ever work in rough areas.'],
                    ['label' => 'Your email address', 'detail' => 'Never shown, never sold, never shared.'],
                    ['label' => 'Anything about your child beyond their age', 'detail' => 'No names, no diagnoses, no photographs.'],
                ],
            ],
        ],

        'cta' => [
            'title' => 'Ready to find your village?',
            'body' => 'Free to join. Verified adults only. Takes about a minute — and registering before we open makes Frith+ free for your family, for good.',
            'button' => 'Register',
        ],

        'faq' => [
            'eyebrow' => 'Questions',
            'title' => 'The things people ask us first',
            'items' => [
                [
                    'question' => 'Is Frith really free?',
                    'answer' => 'Yes. Joining, building your profile, choosing your Journeys, connecting with families who ask you too, and messaging them are all free and always will be. Frith+ is an optional membership that adds more ways to look — and if you register before we open, it stays free for your family for good.',
                ],
                [
                    'question' => 'What if there is nobody near me yet?',
                    'answer' => 'We will tell you honestly rather than showing you families two hours away. As more families in your area register, we let you know.',
                ],
                [
                    'question' => 'Who can see that I am on Frith?',
                    'answer' => 'Only other verified families, and only the four things listed above. Frith profiles are not public and are not indexed by search engines.',
                ],
                [
                    'question' => 'What do you do with what I tell you about my family?',
                    'answer' => 'We use it to find families like yours, and nothing else. It is never shown on your profile, never sold, and never shared with anyone.',
                ],
                [
                    'question' => 'Do you hold information about my children?',
                    'answer' => 'Only the month and year they were born, so we can match you with families at a similar stage. No names, no diagnoses, no photographs.',
                ],
                [
                    'question' => 'How do you keep Frith safe?',
                    'answer' => 'Every adult is verified before they can message anyone. A real person reads every report. You can block or report anyone at any time, and you never have to explain why.',
                ],
                [
                    'question' => 'When does Frith open?',
                    'answer' => 'Autumn 2026. If that moves, we will email you and say so.',
                ],
                [
                    'question' => 'Do you sell my data or show ads?',
                    'answer' => 'No, and we never will. Frith is a small independent company, not a venture-funded one, and our members are not the product.',
                ],
                [
                    'question' => 'Can I leave?',
                    'answer' => 'At any time, from your account settings, and we delete what we hold. You do not have to give a reason.',
                ],
            ],
            'footer' => 'Something not answered here? Write to :email — a real person will reply, usually the same day.',
        ],
    ],
];
