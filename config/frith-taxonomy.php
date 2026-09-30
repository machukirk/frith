<?php

/*
|--------------------------------------------------------------------------
| Experience taxonomy
|--------------------------------------------------------------------------
|
| The eight areas of family life, and the more detailed statements under each.
| Taken verbatim from "Data Collection - Streamlined experiences".
|
| The array keys are slugs, and slugs are what the database stores. Renaming
| one silently detaches every family who selected it, so treat them as
| permanent: change the label freely, never the key. Adding is safe.
|
| This lives in code rather than the CMS on purpose. The labels are content,
| but the keys are matching data — an editor renaming a key would quietly
| break the thing the whole product is for.
|
*/

return [

    'categories' => [

        'learning-education' => [
            'label' => 'Learning & Education',
            'description' => 'School, learning and education experiences',
            'items' => [
                'school-attendance' => 'Struggling to get to school, or anxiety about school',
                'school-environment' => 'Finding the school environment overwhelming',
                'learning-concentration' => 'Struggling with learning, concentration or working independently',
                'additional-support' => 'Needing extra support or adjustments at school',
                'homework-exams' => 'Finding homework, exams or assessments difficult',
                'school-friendships' => 'Finding friendships or belonging at school difficult',
                'education-options' => 'Exploring other education options — specialist, alternative provision or home education',
                'school-communication' => 'Finding communication with school difficult',
            ],
        ],

        'health-wellbeing' => [
            'label' => 'Health & Wellbeing',
            'description' => 'Physical health, emotional wellbeing and everyday care',
            'items' => [
                'anxiety' => 'Anxiety or worries that affect daily life',
                'low-mood' => 'Low mood, or struggling with emotional wellbeing',
                'sleep-eating' => 'Difficulties with sleep, eating or mealtimes',
                'personal-care' => 'Needing support with personal care, toileting or hygiene',
                'ongoing-health-needs' => 'Ongoing health needs, appointments or treatments',
                'pain-fatigue' => 'Pain, fatigue or reduced energy',
                'awaiting-health-support' => 'Waiting for health assessments or support',
                'carer-overwhelm' => 'Feeling overwhelmed by caring, or by supporting their wellbeing',
            ],
        ],

        'child-development' => [
            'label' => 'Child Development',
            'description' => 'Understanding their development and independence',
            'items' => [
                'speech-language' => 'Finding communication, speech or language difficult',
                'communicating-needs' => 'Struggling to communicate needs or feelings',
                'social-communication' => 'Finding social communication or understanding others difficult',
                'everyday-skills' => 'Needing support with everyday skills and independence',
                'organisation-routines' => 'Finding organisation, instructions or routines difficult',
                'learns-differently' => 'Learning differently from their peers, or needing extra time',
                'sensory-differences' => 'Sensory differences, or being overwhelmed by the environment',
                'understanding-development' => 'Learning more about their strengths, needs or development',
            ],
        ],

        'behaviour-relationships' => [
            'label' => 'Behaviour & Relationships',
            'description' => 'Supporting emotions, behaviour and family relationships',
            'items' => [
                'regulating-emotions' => 'Becoming overwhelmed easily, or struggling to regulate emotions',
                'emotional-outbursts' => 'Emotional outbursts, or big feelings',
                'change-transitions' => 'Finding change, transitions or uncertainty difficult',
                'behaviour-that-challenges' => 'Trying to understand and support behaviour that challenges us',
                'family-routines-affected' => 'Family routines or activities shaped around their needs',
                'social-relationships' => 'Finding social situations, friendships or relationships difficult',
                'supporting-siblings' => 'Supporting siblings or other family members through challenges',
                'feeling-judged' => 'Feeling judged or misunderstood by other people',
            ],
        ],

        'support-services' => [
            'label' => 'Support & Services',
            'description' => 'Navigating support, professionals and systems',
            'items' => [
                'awaiting-assessment' => 'Waiting for assessments, or exploring possible additional needs',
                'ehcp' => 'Applying for or managing an EHCP',
                'understanding-send' => 'Trying to understand SEND services and what support exists',
                'working-with-professionals' => 'Working with professionals — therapists, health or education teams',
                'local-groups' => 'Looking for local activities, groups or community support',
                'practical-financial-respite' => 'Looking for practical, financial or respite support',
                'systems-paperwork' => 'Finding systems, paperwork or waiting lists hard to navigate',
                'advice-from-parents' => 'Wanting advice from parents with similar experiences',
            ],
        ],

        'parenting-caregiving' => [
            'label' => 'Parenting & Caregiving',
            'description' => 'The parent and family experience',
            'items' => [
                'isolated' => 'Feeling isolated, and wanting to meet parents who understand',
                'overwhelmed' => 'Feeling overwhelmed by caring responsibilities',
                'balancing-work' => 'Balancing caring with work or other commitments',
                'learning-to-advocate' => 'Learning how to advocate for them',
                'right-decisions' => 'Worrying about making the right decisions',
                'adapting-routines' => 'Adapting routines and expectations around their needs',
                'wider-family-pressures' => 'Supporting siblings, or managing wider family pressures',
                'not-alone' => 'Wanting reassurance that we are not alone',
            ],
        ],

        'life-stages-transitions' => [
            'label' => 'Life Stages & Transitions',
            'description' => 'Changes, milestones and preparing for the future',
            'items' => [
                'starting-a-setting' => 'Starting nursery, school or a new setting',
                'moving-schools' => 'Moving between schools or education stages',
                'puberty-teenager' => 'Approaching puberty, or becoming a teenager',
                'greater-independence' => 'Preparing for greater independence',
                'adulthood-employment' => 'Thinking about adulthood, employment or the future',
                'adult-services' => 'Moving between children’s and adult services',
                'planning-future-support' => 'Planning future support',
                'worried-about-future' => 'Feeling uncertain or worried about the future',
            ],
        ],

        'identity-belonging' => [
            'label' => 'Identity & Belonging',
            'description' => 'Helping children feel understood and accepted',
            'items' => [
                'confidence' => 'Struggling with confidence or self-esteem',
                'feels-different' => 'Feeling different from their peers',
                'fitting-in' => 'Finding it hard to fit in or make connections',
                'masking' => 'Hiding parts of themselves to fit in (masking)',
                'understanding-themselves' => 'Helping them understand themselves',
                'accepted-valued' => 'Wanting them to feel accepted and valued',
                'celebrating-strengths' => 'Celebrating their strengths and individuality',
            ],
        ],
    ],

    /*
    | What the family enjoys. A different axis from the support areas above:
    | those are what is hard, these are what is good, and a match on the second
    | is what turns an introduction into a friendship.
    |
    | Same slug rule as everything else here — reword freely, never rekey.
    */
    'interests' => [
        'outdoors-nature' => [
            'label' => 'Outdoors & nature',
            'description' => 'Parks, walks, gardening, exploring, wildlife',
        ],
        'animals' => [
            'label' => 'Animals',
            'description' => 'Pets, farms, zoos, animal care',
        ],
        'sport-movement' => [
            'label' => 'Sport & movement',
            'description' => 'Sports, dancing, cycling, climbing, active play',
        ],
        'creative' => [
            'label' => 'Creative',
            'description' => 'Art, music, crafts, drama, making things',
        ],
        'games-technology-building' => [
            'label' => 'Games & building',
            'description' => 'Gaming, coding, puzzles, board games, construction',
        ],
        'books-reading' => [
            'label' => 'Books & stories',
            'description' => 'Reading, stories, imaginative play',
        ],
        'vehicles-collecting' => [
            'label' => 'Vehicles & collecting',
            'description' => 'Trains, cars, transport, collections, special interests',
        ],
        'water' => [
            'label' => 'Water',
            'description' => 'Swimming, beaches, paddling, water play',
        ],
        'food-eating-out' => [
            'label' => 'Food',
            'description' => 'Cooking, baking, trying new foods, cafés, restaurants and family-friendly places',
        ],
        'community' => [
            'label' => 'Community',
            'description' => 'Local groups, clubs, events and days out',
        ],
        'quiet-sensory' => [
            'label' => 'Quiet & sensory',
            'description' => 'Calm spaces, sensory play, relaxing activities',
        ],
        'other' => [
            'label' => 'Other',
            'description' => 'Tell us more',
        ],
    ],

    /*
    | What makes an activity work for them. Asked alongside the interests
    | rather than under support, because it is practical rather than personal —
    | it is what somebody needs to know before suggesting a Saturday.
    */
    'activity_supports' => [
        'smaller-groups' => 'Smaller groups',
        'familiar-places' => 'Familiar places',
        'quiet-environments' => 'Quiet environments',
        'being-active' => 'Being active',
        'parent-carer-nearby' => 'Having a parent/carer nearby',
        'clear-routines' => 'Clear routines',
        'similar-interests' => 'Meeting children with similar interests',
    ],

    /*
    | Finding your Frith. What they are hoping for, how they would rather go
    | about it, and who they would like to meet — the three things that turn
    | "here are families like yours" into an introduction somebody actually
    | wants. All optional, like everything else on that screen.
    */
    // The order the hi-fi puts them in: what somebody wants from other
    // families first, then what they can offer, then the practical.
    'hopes' => [
        'families-who-understand' => 'Families who understand our experiences',
        'friendships-for-my-child' => 'Friendships for me and my child',
        'local-families' => 'Local meet-ups and get-togethers',
        'similar-experience' => 'Advice from parents with lived experience',
        'supporting-others' => 'To support other families',
        'parents-to-talk-to' => 'Someone to talk to who gets it',
        'practical-advice' => 'Practical help with forms and processes',
        'something-else' => 'Something else',
        // Off the form, kept so the answers that point at them still resolve.
        'activity-ideas' => 'Ideas for activities and places to go',
        'belonging' => 'A sense of belonging and community',
    ],

    'connection_styles' => [
        'one-to-one' => 'One-to-one chats',
        'small-local-groups' => 'Small local groups',
        'family-meet-ups' => 'Family meet-ups',
        'online' => 'Online conversations',
        'sharing-experiences' => 'Sharing experiences and advice',
        'local-recommendations' => 'Finding local recommendations',
    ],

    /*
    | No longer asked. The redesigned registration has no screen for it, and
    | every option is archived — kept here so the answers families already gave
    | still resolve to a label rather than to a slug.
    */
    'family_preferences' => [
        'nearby' => 'Families nearby',
        'similar-age' => 'Families with children a similar age',
        'similar-interests' => 'Families with similar interests',
        'similar-day-to-day' => 'Families facing similar day-to-day experiences',
        'open-to-any' => 'I’m open to meeting any family who understands',
    ],

    /*
    | Who is part of your family. Multi-select: the data doc's wording is
    | "select all that apply", because families do not fit one box.
    */
    'family_structures' => [
        'two-parents' => 'Two parents or carers',
        'parenting-alone' => 'Parenting on my own',
        'blended' => 'Step or blended family',
        'extended' => 'Extended family plays an important role',
        'foster-adoptive' => 'Fostering or adoption',
        'other' => 'Other family structure',
    ],
];
