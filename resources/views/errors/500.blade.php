{{--
    Something broke at our end.

    Deliberately plain, and deliberately not reading anything out of the
    request: this page renders when something has already gone wrong, so the
    less it depends on the less likely it is to fail as well. No page content
    lookup, no navigation config, no database.
--}}
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Something has gone wrong at our end — Frith</title>
    @vite('resources/scss/main.scss')
</head>
<body>

    <main id="main">
        <section class="wrap outcome">
            <h1 class="heading outcome__title">Something has gone wrong at our end</h1>

            <p class="outcome__body">
                Nothing you did. We have been told about it, and someone is looking.
            </p>

            <div class="note note--standalone outcome__note">
                If you were part way through writing something, we have kept it. Go
                back and it will still be there.
            </div>

            <div class="outcome__actions">
                <a class="button button--primary" href="/">Try again</a>
            </div>
        </section>
    </main>

</body>
</html>
