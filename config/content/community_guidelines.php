<?php

/*
|--------------------------------------------------------------------------
| Community Guidelines
|--------------------------------------------------------------------------
|
| Read end to end by the few families who need it, so it is written as one
| piece of prose rather than a set of panels. The reporting stages are the
| exception: people scan for what happens and when.
|
*/

return [

    'meta' => [
        'title' => 'Community Guidelines — Frith',
        'description' => 'What we expect of families on Frith, what is not allowed, and exactly what happens when you report someone — read by a person, usually within 24 hours.',
    ],

    'head' => [
        'eyebrow' => 'Community',
        'title' => 'Community Guidelines',
        'updated' => 'Last updated 9 September 2026 · about 5 minutes to read',
    ],

    'summary' => [
        'label' => 'The short version',
        'body' => 'Frith works because people are kind to each other. Here is what we expect, and what we will do if it does not happen.',
    ],

    'intro' => 'Most families never need this page. It exists so that the few who do know exactly where they stand — and so you know what will happen if you report someone.',

    'expect' => [
        'title' => 'What we expect',
        'items' => [
            [
                'lead' => 'Assume the hard day.',
                'body' => 'If a message reads sharply, it was probably typed at 11pm by someone who had had enough. Give people the benefit of the doubt you would want.',
            ],
            [
                'lead' => 'Share your own experience, not someone else’s.',
                'body' => 'Your story is yours to tell. Another family’s child is not.',
            ],
            [
                'lead' => 'Say what worked for you, not what they should do.',
                'body' => '"We found this helped" lands very differently from "you need to".',
            ],
            [
                'lead' => 'Let people go quiet.',
                'body' => 'No reply is not a snub. Nobody here owes anybody a conversation.',
            ],
            [
                'lead' => 'Keep Frith on Frith.',
                'body' => 'Do not screenshot, repost or discuss another family anywhere else.',
            ],
        ],
    ],

    'not_allowed' => [
        'title' => 'What is not allowed',
        'standfirst' => 'These will get an account removed. Most of them will get it removed the same day.',
        'items' => [
            'Anything that puts a child at risk, or any attempt to contact a child.',
            'Harassment, threats, abuse, or repeated contact after being asked to stop.',
            'Sharing another family’s information — including their identity, their child’s details, or their messages.',
            'Pretending to be someone you are not, or misrepresenting your family.',
            'Selling, advertising, recruiting, fundraising or promoting.',
            'Using Frith to look for dates or partners.',
            'Presenting yourself as a qualified professional when you are not.',
        ],
    ],

    'reporting' => [
        'title' => 'What happens when you report someone',
        'stages' => [
            [
                'label' => 'Straight away',
                'body' => 'The family is blocked. They cannot see you, find you, or message you — whatever we decide about the report, and they are never told you reported them.',
            ],
            [
                'label' => 'Within 24 hours',
                'body' => 'A person reads it. Not a queue and not an algorithm. If a child may be at risk we act immediately.',
            ],
            [
                'label' => 'Then',
                'body' => 'We may do nothing, send a warning, remove a Journey, suspend the account, or remove it. Serious matters go to the police or the local authority.',
            ],
            [
                'label' => 'We will tell you',
                'body' => 'That we have read it and that we have acted. We will not share what we decided about another family — their privacy holds too, and we would protect yours the same way.',
            ],
        ],
    ],

    'blocking' => [
        'title' => 'If you simply do not want to talk to someone',
        'body' => 'Use Block. No reason needed and nothing is reported. Leaving a conversation should never require accusing anyone of anything.',
    ],

    'appeals' => [
        'title' => 'If we get it wrong',
        'body' => 'Tell us. We have suspended the wrong account before and we will again. Write to :email and a person — not a form — will look at it again.',
    ],
];
