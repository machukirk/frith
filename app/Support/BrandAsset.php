<?php

namespace App\Support;

/**
 * A brand asset URL that changes when the file does.
 *
 * Everything under public/brand is served with `cache-control: max-age=31536000`
 * — a year — under a filename that never changes. So the logo can be replaced
 * on the server and every visitor who has been to the site before keeps the old
 * one until their cache expires. That is exactly what happened when the new
 * wordmark went out: the file was right, and browsers went on drawing the old
 * shape.
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

    private static function build(string $path): string
    {
        $url = asset($path);
        $full = public_path($path);

        // A missing file is somebody else's error to report — it should still
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
