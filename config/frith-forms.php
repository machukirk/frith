<?php

/*
|--------------------------------------------------------------------------
| Form copy — the floor
|--------------------------------------------------------------------------
|
| The words on each screen, as they ship. This is seeded into the database on
| install and edited in the admin panel from then on; it stays here as the
| fallback so a fresh install, or a form nobody has opened yet, still renders
| sentences rather than blanks.
|
| Structure is not here. Which screens exist, in what order, and how they
| behave is code — see App\Support\RegistrationFlow and the controllers.
|
*/

return [

    'founders-registration' => [
        'name' => 'Frith Founders registration',
        'description' => 'The six-screen registration.',

        'steps' => [
            'you' => [
                'heading' => 'What should we call you?',
                'standfirst' => 'This is what other families will see.',
                'is_private' => false,
                'fields' => [
                    'first_name' => [
                        'label' => 'Your name',
                        'help' => 'A first name or a nickname is fine.',
                    ],
                    'email' => [
                        'label' => 'Your email',
                        // Deliberately short: the consent line sits below and
                        // says what happens to it.
                        'help' => 'Where we will write to you.',
                    ],
                    'postcode_outcode' => [
                        'label' => 'Where are you based?',
                        'placeholder' => 'TN13',
                        'help' => 'Just the first part of your postcode. Other families only ever see your general area.',
                    ],
                ],
            ],

            'family' => [
                'heading' => 'Who is part of your family?',
                'standfirst' => 'Families come in all shapes and sizes. Share whatever feels relevant.',
                'is_private' => false,
                'fields' => [
                    'family_structures' => [
                        'label' => 'Your household',
                        'help' => 'Optional — this helps us understand family dynamics, and is never shown on your profile.',
                    ],
                    'children' => [
                        'label' => 'Your children',
                        'help' => 'Month and year only — we never ask for a date of birth.',
                    ],
                ],
            ],

            'hopes' => [
                'heading' => 'What are you hoping to find through Frith?',
                'standfirst' => 'Select as many as you wish.',
                'is_private' => false,
                'fields' => [
                    'hopes' => [
                        'label' => 'What you are hoping to find',
                        'help' => 'This helps us introduce you to families looking for the same kind of connection.',
                    ],
                ],
            ],

            'areas' => [
                'heading' => 'Which areas of family life would you like the most support with?',
                'standfirst' => 'Choose all that apply. You are registered from here — the more you share, the more meaningful your connections can be.',
                'is_private' => false,
                'fields' => [],
            ],

            'experiences' => [
                'heading' => 'What would you like to connect with other families about?',
                'standfirst' => 'Choose whatever feels relevant to you.',
                // The privacy line lives inside each accordion rather than at
                // the foot of the screen: it answers the worry at the moment
                // somebody is deciding whether to tick something.
                'is_private' => false,
                'fields' => [
                    'items' => [
                        'label' => 'Your experiences',
                        // Shown when they picked no areas on the screen before.
                        'help' => 'You did not choose any areas of family life, so there is nothing to ask about here. Go back a step if you would like to.',
                    ],
                    'privacy_note' => [
                        'label' => 'Your selections stay private and help us find families with similar experiences.',
                        'help' => 'Shown inside every area on this screen.',
                    ],
                ],
            ],

            'interests' => [
                'heading' => 'What does your family enjoy?',
                'standfirst' => 'Shared interests are often where friendships begin.',
                'is_private' => false,
                'fields' => [
                    'interests' => [
                        'label' => 'Things you enjoy together',
                    ],
                    'interests_other' => [
                        'label' => 'Tell us more',
                        'help' => 'If you chose Other above, or there is anything else your family enjoys, this is the place.',
                    ],
                    'activity_supports' => [
                        'label' => 'Things that help you enjoy activities',
                        'help' => 'Hosts use this to make meet-ups work for your family.',
                    ],
                    'connection_styles' => [
                        'label' => 'How would you prefer to connect?',
                    ],
                ],
            ],
        ],
    ],
];
