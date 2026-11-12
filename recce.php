<?php

/**
 * Renders any page of the site as it actually ships, for design comparison.
 *
 *   ddev exec php recce.php /privacy
 *   ddev exec php recce.php /village sarah@example.test
 *
 * Goes through the real HTTP kernel, so middleware, layering and the live
 * database all apply. Prints the HTML to stdout.
 */
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$path = $argv[1] ?? '/';
$email = $argv[2] ?? null;

$request = Illuminate\Http\Request::create($path, 'GET');

if ($email) {
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $family = App\Models\Registration::query()->where('email', $email)->first();

    if (! $family) {
        fwrite(STDERR, "No registration for {$email}\n");
        exit(1);
    }

    Illuminate\Support\Facades\Auth::guard('founder')->login($family);
    $request->setUserResolver(fn () => $family);
}

$response = $kernel->handle($request);

fwrite(STDERR, 'HTTP '.$response->getStatusCode()."\n");
echo $response->getContent();
