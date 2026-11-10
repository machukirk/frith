<?php

namespace App\Support;

/**
 * A brand asset URL that changes when the file does.
 *
 * Everything under public/brand is served with
 * `cache-control: max-age=31536000`, a year, under a filename that never
 * changes. So the logo can be replaced on the server and every visitor who
 * has been to the site before keeps the old one until their cache expires.
 * That is exactly what happened when the new wordmark went out: the file was
 * right, and browsers went on drawing the old shape.
 *
 * The built CSS does not have this problem because Vite fingerprints it. This
 * does the same job for hand-managed files, with the modified time as the
 * fingerprint: same file, same URL, cached hard; new file, new URL, fetched.
 */
class BrandAsset
{
    /** @var array<string, string> */
    private static array $resolved = [];

    public static function url(string $path): string
    {
        return self::$resolved[$path] ??= self::build($path);
    }

    /**
     * The same asset, but reachable by an email client.
     *
     * An image in an email is fetched by somebody else: Gmail's proxy, or the
     * recipient's client, with none of our cookies and none of our
     * credentials. A copy of the site usually sits behind basic auth, so that
     * fetch gets a 401 and the logo silently does not appear. The email is
     * fine; the host is not reachable.
     *
     * So from anything that is not frith.community, email images are pointed
     * at frith.community, which is public and carries the same file. It means
     * a test email looks exactly like a real one, which is most of the reason
     * for sending one.
     *
     * The ?v= fingerprint is still taken from the local file, so if a copy is
     * carrying a newer logo than production the email shows production's. That
     * is the right way round: the email has to load for whoever receives it.
     */
    public static function forMail(string $path): string
    {
        $url = self::url($path);

        if (SearchVisibility::isRealSite()) {
            return $url;
        }

        $host = SearchVisibility::canonicalHost();

        if ($host === '') {
            return $url;
        }

        return 'https://'.$host.'/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/')
            .(($query = parse_url($url, PHP_URL_QUERY)) ? '?'.$query : '');
    }

    private static function build(string $path): string
    {
        $url = asset($path);
        $full = public_path($path);

        // A missing file is somebody else's error to report. It should still
        // produce a URL rather than blow up mid-render.
        if (! is_file($full)) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'v='.filemtime($full);
    }

    /** Only for tests, which rewrite files between assertions. */
    public static function forget(): void
    {
        self::$resolved = [];
    }
}
