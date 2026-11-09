<?php

/*
|--------------------------------------------------------------------------
| Journeys
|--------------------------------------------------------------------------
|
| The nine groups a family can join. A Journey is one thing a family is
| going through, not a diagnosis: the whole product links people by
| experience, and these are the experiences broad enough that a few hundred
| families will recognise themselves in one.
|
| The floor, in the same way config/content/* is the floor for a page. The
| seeder copies these into the database once, and after that the admin panel
| owns the wording. Slugs are the exception: they are in URLs and in the
| membership table, so they never move.
|
| The blurbs below are mine and want a read from somebody who has been
| through these. They are editable in the panel.
|
*/

return [

    'autism-assessment' => [
        'title' => 'Waiting for an autism assessment',
        'blurb' => 'Families somewhere between a referral and an appointment. Most people here want someone who understands the waiting itself: the forms, the school conversations, and the not-knowing.',
    ],

    'ehcp' => [
        'title' => 'Starting the EHCP process',
        'blurb' => 'The first draft, the evidence, the deadlines nobody explains. Families here are somewhere in the paperwork and would rather not work it out alone.',
    ],

    'sleep' => [
        'title' => 'Sleep challenges',
        'blurb' => 'Bedtimes that take hours, nights that end at four, and the particular tiredness that comes with both. What helps one child rarely helps the next, but the exhaustion is the same.',
    ],

    'dads' => [
        'title' => 'Dads of SEND children',
        'blurb' => 'Fathers and father figures raising a child with additional needs. Most groups and most school gates are not set up for them, so this one is.',
    ],

    'school-refusal' => [
        'title' => 'School refusal',
        'blurb' => 'Mornings that start with dread and often do not end in a classroom. Families here are dealing with the school, the attendance letters, and a child who is telling them something.',
    ],

    'primary-to-secondary' => [
        'title' => 'Primary to secondary',
        'blurb' => 'The jump that worries parents long before it happens. Transition plans, new buildings, and a child who had just about got used to the old one.',
    ],

    'tribunal' => [
        'title' => 'Tribunal and appeals',
        'blurb' => 'Families who have been refused something and decided to argue. Long, slow and wearing, and much easier beside somebody who has done it.',
    ],

    'newly-diagnosed' => [
        'title' => 'Newly diagnosed',
        'blurb' => 'The weeks after a diagnosis, when everything is the same and nothing is. Families here are working out what it changes and what it does not.',
    ],

    'waiting-for-diagnosis' => [
        'title' => 'Waiting for a diagnosis',
        'blurb' => 'Knowing something is going on without a name for it yet. The lists are long, and in the meantime there is still a child who needs support today.',
    ],

];
