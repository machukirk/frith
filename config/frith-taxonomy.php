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
    | Who is part of your family. Multi-select: the data doc's wording is
    | "select all that apply", because families do not fit one box.
    */
    'family_structures' => [
        'two-parents' => 'Two parents/carers',
        'parenting-alone' => 'I am parenting on my own',
        'blended' => 'Step-family/blended family',
        'extended' => 'Extended family plays an important role',
        'foster-adoptive' => 'Foster/adoptive family',
        'other' => 'Other family structure',
    ],
];
