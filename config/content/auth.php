<?php

/*
|--------------------------------------------------------------------------
| The auth screens — /login, /forgot, /link-sent, /verified
|--------------------------------------------------------------------------
|
| Held together in one file because they are one journey, and the wording on
| each only makes sense next to the others.
|
*/

return [

    'meta' => [
        'title' => 'Log in — Frith',
        'description' => 'Log in to Frith.',
    ],

    'login' => [
        'title' => 'Welcome back',
        'standfirst' => 'Log in to see your village.',
        'email' => 'Email address',
        'password' => 'Password',
        'forgotten' => 'Forgotten it?',
        'button' => 'Log in',
        'divider' => 'or',
        'link_button' => 'Email me a link instead',
        'note' => 'A parent registering at 1am on a phone will not remember a password a fortnight later. The link is the kinder default.',
        'no_account' => 'Not registered yet?',
        'register' => 'Register',
    ],

    'forgot' => [
        'title' => 'Forgotten your password?',
        'standfirst' => 'Give us the email address you registered with and we will send you a link to set a new one.',
        'email' => 'Email address',
        'button' => 'Email me a link',
        'back' => 'Back to log in',
    ],

    'link_sent' => [
        'title' => 'Check your email',
        // :email is filled in with the address they gave.
        'standfirst' => 'We have sent a link to :email. It works once, and for the next hour.',
        'standfirst_generic' => 'If that address is registered with us, a link is on its way. It works once, and for the next hour.',
        'trouble' => 'Nothing arrived? Check your spam folder, or write to :email and a person will help.',
        'back' => 'Back to log in',
    ],

    'verified' => [
        'title' => 'You’re all set',
        'standfirst' => 'Your email is confirmed and you are logged in — no need to sign in again. We’ll let you know when there’s a connection worth seeing.',
        'unfinished' => 'Still need to complete the details in your profile?',
        'unfinished_link' => 'Pick up where you left off',
        'button' => 'Back to the homepage',
    ],
];
