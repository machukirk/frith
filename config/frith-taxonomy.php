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
                'school-attendance' => 'My child struggles with attending school or feels anxious about school',
                'school-environment' => 'My child finds the school environment overwhelming',
                'learning-concentration' => 'My child struggles with learning, concentration or independent work',
                'additional-support' => 'My child needs additional support or adjustments in education',
                'homework-exams' => 'My child finds homework, exams or assessments difficult',
                'school-friendships' => 'My child finds friendships, social situations or belonging at school difficult',
                'education-options' => 'We are exploring different education options (specialist, alternative provision or home education)',
                'school-communication' => 'We are finding communication with school or education providers challenging',
            ],
        ],

        'health-wellbeing' => [
            'label' => 'Health & Wellbeing',
            'description' => 'Physical health, emotional wellbeing and everyday care',
            'items' => [
                'anxiety' => 'My child experiences anxiety or worries that affect daily life',
                'low-mood' => 'My child struggles with emotional wellbeing or low mood',
                'sleep-eating' => 'My child has difficulties with sleep, eating or mealtimes',
                'personal-care' => 'My child needs support with personal care, toileting or hygiene',
                'ongoing-health-needs' => 'My child has ongoing health needs, medical appointments or treatments',
                'pain-fatigue' => 'My child experiences pain, fatigue or reduced energy',
                'awaiting-health-support' => 'We are waiting for health assessments or support',
                'carer-overwhelm' => 'I feel overwhelmed by caring responsibilities or supporting my child’s wellbeing',
            ],
        ],

        'child-development' => [
            'label' => 'Child Development',
            'description' => 'Understanding your child’s development and independence',
            'items' => [
                'speech-language' => 'My child finds communication, speech or language difficult',
                'communicating-needs' => 'My child struggles to communicate their needs or feelings',
                'social-communication' => 'My child finds social communication or understanding others difficult',
                'everyday-skills' => 'My child needs support developing everyday skills and independence',
                'organisation-routines' => 'My child finds organisation, instructions or routines difficult',
                'learns-differently' => 'My child learns differently from their peers or needs extra time to develop skills',
                'sensory-differences' => 'My child experiences sensory differences or becomes overwhelmed by their environment',
                'understanding-development' => 'We are learning more about our child’s strengths, needs or development',
            ],
        ],

        'behaviour-relationships' => [
            'label' => 'Behaviour & Relationships',
            'description' => 'Supporting emotions, behaviour and family relationships',
            'items' => [
                'regulating-emotions' => 'My child becomes overwhelmed easily or struggles to regulate emotions',
                'emotional-outbursts' => 'My child experiences emotional outbursts or big feelings',
                'change-transitions' => 'My child finds changes, transitions or uncertainty difficult',
                'behaviour-that-challenges' => 'We are trying to understand and support behaviours that challenge us',
                'family-routines-affected' => 'Our family routines or activities are affected by our child’s needs',
                'social-relationships' => 'My child finds social situations, friendships or relationships difficult',
                'supporting-siblings' => 'We are supporting siblings or other family members through challenges',
                'feeling-judged' => 'Our family sometimes feels judged or misunderstood by others',
            ],
        ],

        'support-services' => [
            'label' => 'Support & Services',
            'description' => 'Navigating support, professionals and systems',
            'items' => [
                'awaiting-assessment' => 'We are waiting for assessments or exploring possible additional needs',
                'ehcp' => 'We are applying for or managing an EHCP',
                'understanding-send' => 'We are trying to understand SEND services and available support',
                'working-with-professionals' => 'We are working with professionals (e.g. therapists, health or education teams)',
                'local-groups' => 'We are looking for local activities, groups or community support',
                'practical-financial-respite' => 'We are looking for practical, financial or respite support',
                'systems-paperwork' => 'We find systems, paperwork or waiting lists difficult to navigate',
                'advice-from-parents' => 'We would value advice from parents with similar experiences',
            ],
        ],

        'parenting-caregiving' => [
            'label' => 'Parenting & Caregiving',
            'description' => 'The parent and family experience',
            'items' => [
                'isolated' => 'I feel isolated and would like to connect with parents who understand',
                'overwhelmed' => 'I feel overwhelmed by caring responsibilities',
                'balancing-work' => 'I am balancing caring responsibilities with work or other commitments',
                'learning-to-advocate' => 'I am learning how to advocate for my child',
                'right-decisions' => 'I worry about making the right decisions for my child',
                'adapting-routines' => 'Our family is adapting routines and expectations around our child’s needs',
                'wider-family-pressures' => 'We are supporting siblings or managing wider family pressures',
                'not-alone' => 'I would like reassurance that we are not alone',
            ],
        ],

        'life-stages-transitions' => [
            'label' => 'Life Stages & Transitions',
            'description' => 'Changes, milestones and preparing for the future',
            'items' => [
                'starting-a-setting' => 'My child is starting nursery, school or a new setting',
                'moving-schools' => 'My child is moving between schools or education stages',
                'puberty-teenager' => 'My child is approaching puberty or becoming a teenager',
                'greater-independence' => 'My child is preparing for greater independence',
                'adulthood-employment' => 'We are thinking about adulthood, employment or future opportunities',
                'adult-services' => 'We are moving between children’s and adult services',
                'planning-future-support' => 'We are planning future support for our child',
                'worried-about-future' => 'I feel uncertain or worried about what the future holds',
            ],
        ],

        'identity-belonging' => [
            'label' => 'Identity & Belonging',
            'description' => 'Helping children feel understood and accepted',
            'items' => [
                'confidence' => 'My child struggles with confidence or self-esteem',
                'feels-different' => 'My child feels different from their peers',
                'fitting-in' => 'My child finds fitting in or making connections difficult',
                'masking' => 'My child hides parts of themselves to fit in (masking)',
                'understanding-themselves' => 'We are helping our child understand themselves',
                'accepted-valued' => 'We want our child to feel accepted and valued',
                'celebrating-strengths' => 'We want to celebrate our child’s strengths and individuality',
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
