<?php

/*
|--------------------------------------------------------------------------
| Reporting & complaints
|--------------------------------------------------------------------------
|
| One of the five policy pages, so it renders inside the policy shell with
| the other four listed beside it.
|
| ':email' anywhere below is replaced with frith.company.contact_email, so
| the address is written down once and an editor never has to chase it.
| support@frith.community is a second inbox with no config key of its own,
| so it is spelled out.
|
*/

return [

    'meta' => [
        'title' => 'Reporting & complaints — Frith',
        'description' => 'Report a family from their profile or your conversation. A person reads every report, usually within 24 hours, and blocking works immediately.',
    ],

    'head' => [
        'eyebrow' => 'Safety',
        'title' => 'Reporting & complaints',
        'meta' => 'Last updated 9 September 2026 · about 4 minutes to read',
    ],

    'summary' => [
        'label' => 'The short version',
        'body' => 'Report a family from their profile or your conversation. A person reads every report, usually within 24 hours. Blocking works immediately, whatever we decide. If a child may be at immediate risk, call 999 first.',
    ],

    // The lead is set in bold ahead of the rest of the sentence.
    'urgent' => [
        'lead' => 'If you believe a child is at immediate risk, contact the police on 999 first, then tell us at support@frith.community.',
        'body' => 'Do not wait for us to reply. We will always cooperate fully with a safeguarding investigation.',
    ],

    'how_to_report' => [
        'title' => 'Reporting a family',
        'paragraphs' => [
            'There is a Safety button in every conversation and a Report and block link on every profile. You choose a reason, add anything you want to, and send. You do not have to explain yourself well for us to take it seriously.',
            'The family is blocked the moment you submit. They are not told that you reported them, and they never will be.',
        ],
    ],

    'what_we_do' => [
        'title' => 'What we do with it',
        'items' => [
            'A person reads it, usually within 24 hours, seven days a week.',
            'We look at the account, the conversation, and any earlier reports.',
            'We decide: no action, a warning, removal from a Journey, suspension, or removal from Frith.',
            'Anything involving a child’s safety goes to the police or the local authority safeguarding team.',
            'We email you to say we have read it and acted.',
        ],
        'note' => 'We will not tell you what we decided about another family. That is their privacy, and we would protect yours in the same way. We know that is frustrating when you have reported something serious.',
    ],

    'complaints' => [
        'title' => 'Complaining about a decision we made',
        'standfirst' => 'If we have suspended your account, removed a post, or turned down something you asked for, you can ask us to look again.',
        'steps' => [
            [
                'title' => 'Email :email',
                'body' => 'Tell us what happened and what you would like us to do. There is no form and no reference number.',
            ],
            [
                'title' => 'A different person reviews it',
                'body' => 'Not the person who made the original decision. Within five working days.',
            ],
            [
                'title' => 'We reply with a reason',
                'body' => 'Whether we change our mind or not, you get an explanation rather than a policy quote.',
            ],
        ],
    ],

    'escalation' => [
        'title' => 'If you are still not happy',
        'body' => 'For a data complaint you can go to the Information Commissioner’s Office. For anything else, tell us and we will keep talking — there is no appeal panel above us, because Frith is small enough that the person who replies is the person who decides.',
    ],

    'where_to_write' => [
        'title' => 'Where to write',
        'cards' => [
            [
                'title' => 'Safety',
                'email' => 'support@frith.community',
                'response' => 'Within 24 hours, seven days',
            ],
            [
                'title' => 'Anything else',
                'email' => ':email',
                'response' => 'Usually the same day',
            ],
            [
                'title' => 'Your data',
                'email' => 'support@frith.community',
                'response' => 'Within 30 days',
            ],
        ],
    ],
];
