<?php

namespace App\Support;

/**
 * Where a page's copy actually appears, for the three that have no page.
 *
 * Most rows in the Pages resource are a URL: /privacy edits the page at
 * /privacy. Three are not. The log-in screens, the end of registration and
 * the four lines at the bottom of every page are all copy somebody edits, and
 * none of them is a slug a visitor can type.
 *
 * Giving them rows made the copy editable, which was the point, and left the
 * admin saying three untrue things: the list showed them as living at /auth,
 * /footer and /join, the View button went nowhere, and the preview pane
 * looked for a pages.<slug> view and 404d. This is the one place that knows
 * the difference.
 */
class EditablePage
{
    /**
     * The route whose screen shows this copy, for a row that is not a page.
     *
     * @var array<string, string>
     */
    private const SHOWN_ON = [
        'auth' => 'login',
        'join' => 'register.welcome',
        // Every page carries the footer, so any of them demonstrates it. The
        // home page is the one somebody would check it against.
        'footer' => 'home',
    ];

    /** The view that renders this copy, or null if no single view does. */
    public static function view(string $slug): ?string
    {
        $view = match ($slug) {
            'auth' => 'pages.auth.login',
            // The end of registration needs a registration in the session to
            // render, which a preview pane does not have.
            'join' => null,
            'footer' => 'pages.home',
            default => "pages.{$slug}",
        };

        return $view !== null && view()->exists($view) ? $view : null;
    }

    /** Where to send somebody who presses View, or null if nowhere sensible. */
    public static function url(string $slug): ?string
    {
        $route = self::SHOWN_ON[$slug] ?? $slug;

        return app('router')->has($route) ? route($route) : null;
    }

    /**
     * What the admin list says underneath the name.
     *
     * A real path for a real page. For the other three, where the words turn
     * up, because "/footer" is not an address and telling an editor it is
     * sends them looking for a page that does not exist.
     */
    public static function whereItShows(string $slug): string
    {
        return match ($slug) {
            'home' => '/',
            'auth' => 'The log-in screens',
            'join' => 'The screen at the end of registration',
            'footer' => 'The bottom of every page',
            default => '/'.$slug,
        };
    }
}
