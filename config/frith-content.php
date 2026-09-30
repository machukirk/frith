<?php

/*
|--------------------------------------------------------------------------
| Site copy — the floor
|--------------------------------------------------------------------------
|
| One file per page in config/content, keyed by its slug with hyphens turned
| into underscores: config/content/how_it_works.php is the copy for
| /how-it-works. This file only assembles them.
|
| These are the defaults. They are seeded into the pages table on install and
| edited in the admin panel from then on, and they stay here as the fallback
| so a fresh install, or a page nobody has opened yet, still renders sentences
| rather than blanks.
|
| Structure is not here. Which sections a page has and in what order is code,
| because a section is a designed thing rather than a free-form block.
|
*/

return collect(glob(__DIR__.'/content/*.php'))
    ->mapWithKeys(fn (string $file) => [basename($file, '.php') => require $file])
    ->all();
