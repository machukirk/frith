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
        'description' => 'The seven-screen registration, plus the optional detail questions.',

        'steps' => [
            'you' => [
                'heading' => 'What should we call you?',
                'standfirst' => 'Just a first name or a nickname — whatever you would like other families to call you.',
                'is_private' => false,
                'fields' => [
                    'first_name' => [
                        'label' => 'Your name',
                        'help' => 'This is what other families see. A first name or a nickname is fine.',
                    ],
                    'email' => [
                        'label' => 'Your email',
                        // Deliberately short: the consent line sits directly
                        // below and says what happens to it.
                        'help' => 'Where we will write to you.',
                    ],
                ],
            ],

            'location' => [
                'heading' => 'Where are you based?',
                'standfirst' => 'Just the first part of your postcode — the bit before the space.',
                'is_private' => false,
                'fields' => [
                    'postcode_outcode' => [
                        'label' => 'First part of your postcode',
                        'placeholder' => 'SS9',
                        'help' => 'We use this to find families near you. It is never shown on your profile, and we never ask for the rest of it.',
                    ],
                ],
            ],

            'family' => [
                'heading' => 'Who is part of your family?',
                'standfirst' => 'Families come in all shapes and sizes. Choose as many as fit — or skip it.',
                'is_private' => false,
                'fields' => [],
            ],

            'children' => [
                'heading' => 'How many children, and how old are they?',
                'standfirst' => 'Month and year is all we ask. It keeps their age right without us holding a date of birth.',
                'is_private' => false,
                'fields' => [
                    'children' => ['label' => 'Born'],
                ],
            ],

            'support' => [
                'heading' => 'Which areas of family life would you like the most support with?',
                'standfirst' => 'Choose as many as fit. This is how we find you families who understand, so there are no wrong answers.',
                'is_private' => true,
                'fields' => [],
            ],

            'interests' => [
                'heading' => 'Interests & activities',
                'standfirst' => 'Shared interests are often where friendships begin. Tell us what your family enjoys.',
                'is_private' => false,
                'fields' => [
                    'interests_other' => [
                        'label' => 'Tell us more',
                        'help' => 'If you chose Other above, or there is anything else your family enjoys, this is the place.',
                    ],
                    'activity_supports' => [
                        'label' => 'Are there things that help you enjoy activities?',
                    ],
                ],
            ],

            'finding' => [
                'heading' => 'Finding your Frith',
                'standfirst' => 'Help us understand the kind of support and friendships you’re looking for.',
                'is_private' => false,
                'fields' => [
                    'hopes' => ['label' => 'What are you hoping to find through Frith?'],
                    'connection_styles' => ['label' => 'How would you prefer to connect?'],
                    'family_preferences' => ['label' => 'What type of families would you like to connect with?'],
                ],
            ],
        ],
    ],
];
