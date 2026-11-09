<?php

/*
|--------------------------------------------------------------------------
| Outcode to post town
|--------------------------------------------------------------------------
|
| The designs render a family's location as "Tonbridge area" rather than
| "TN9 area", because a town is a thing a parent recognises and an outcode is
| not. This is the table that does it.
|
| It is deliberately incomplete. There are around 2,900 postcode districts in
| the UK and naming one wrongly is worse than not naming it at all: a family
| shown the wrong town either stops trusting the rest of the screen or drives
| to the wrong place. So this holds the districts we are confident about, and
| App\Support\Postcode falls back to the outcode for everything else.
|
| What is here: SS, the Southend pilot, where the first families will be. Plus
| the Kent districts the hi-fi uses in its own examples.
|
| Before the UK-wide launch this wants filling from the Royal Mail postcode
| address file, or ONS's free postcode directory, rather than by hand.
|
| Note for whoever does that: the hi-fi labels TN13 as "Tonbridge area" on the
| profile screen. TN13 is Sevenoaks. The design's own other screens get it
| right ("The Ahmeds, Sevenoaks area"), so the profile frame is the mistake.
|
*/

return [

    // --- The Southend pilot ---------------------------------------------------
    'SS0' => 'Westcliff-on-Sea',
    'SS1' => 'Southend-on-Sea',
    'SS2' => 'Southend-on-Sea',
    'SS3' => 'Shoeburyness',
    'SS4' => 'Rochford',
    'SS5' => 'Hockley',
    'SS6' => 'Rayleigh',
    'SS7' => 'Benfleet',
    'SS8' => 'Canvey Island',
    'SS9' => 'Leigh-on-Sea',
    'SS11' => 'Wickford',
    'SS12' => 'Wickford',
    'SS13' => 'Basildon',
    'SS14' => 'Basildon',
    'SS15' => 'Basildon',
    'SS16' => 'Basildon',
    'SS17' => 'Stanford-le-Hope',

    // --- The districts the designs use ----------------------------------------
    'TN1' => 'Tunbridge Wells',
    'TN2' => 'Tunbridge Wells',
    'TN3' => 'Tunbridge Wells',
    'TN4' => 'Tunbridge Wells',
    'TN9' => 'Tonbridge',
    'TN10' => 'Tonbridge',
    'TN11' => 'Tonbridge',
    'TN13' => 'Sevenoaks',
    'TN14' => 'Sevenoaks',
    'TN15' => 'Sevenoaks',

];
