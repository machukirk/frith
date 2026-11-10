<?php

namespace Tests\Feature;

use App\Support\BrandAsset;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BrandAssetTest extends TestCase
{
    /**
     * The email logo has to be a PNG with real transparency.
     *
     * An earlier version was rendered by compositing the SVG onto white, which
     * produced a file with an alpha channel where every pixel was opaque. It
     * looked correct in isolation and wrong in the email: a white rectangle on
     * the Linen background. Nothing else catches that.
     *
     * @return array<string, array<int, string>>
     */
    public static function emailLogos(): array
    {
        return [
            'full colour' => ['frith-logo-horizontal-fullcolour-email.png'],
            'reversed' => ['frith-logo-horizontal-reversed-email.png'],
        ];
    }

    #[Test]
    #[DataProvider('emailLogos')]
    public function the_email_logo_has_a_transparent_background(string $file): void
    {
        $path = public_path('brand/logo/png/'.$file);

        $this->assertFileExists($path);

        $image = imagecreatefrompng($path);
        $this->assertNotFalse($image, 'PNG could not be read');

        $width = imagesx($image);
        $height = imagesy($image);

        // GD reports alpha 0-127, where 127 is fully transparent.
        $corners = [[0, 0], [$width - 1, 0], [0, $height - 1], [$width - 1, $height - 1]];

        foreach ($corners as [$x, $y]) {
            $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;
            $this->assertSame(127, $alpha, "Corner ({$x}, {$y}) of {$file} is not transparent");
        }

        // A corner could be transparent by luck on a badly cropped file, so
        // check the image is mostly background rather than a solid block.
        $transparent = 0;
        $sampled = 0;

        for ($y = 0; $y < $height; $y += 3) {
            for ($x = 0; $x < $width; $x += 3) {
                $sampled++;
                if (((imagecolorat($image, $x, $y) >> 24) & 0x7F) === 127) {
                    $transparent++;
                }
            }
        }

        imagedestroy($image);

        $this->assertGreaterThan(
            0.5,
            $transparent / $sampled,
            "{$file} is mostly opaque: the background is probably baked in",
        );
    }

    #[Test]
    public function the_email_template_points_at_the_png_and_not_the_svg(): void
    {
        // No major email client renders SVG.
        $template = file_get_contents(resource_path('views/components/mail/layout.blade.php'));

        $this->assertStringContainsString('frith-logo-horizontal-fullcolour-email.png', $template);
        $this->assertStringNotContainsString('.svg', $template);
    }

    #[Test]
    public function an_email_image_points_at_the_real_site_from_a_copy(): void
    {
        // An image in an email is fetched by Gmail's proxy or by the
        // recipient's client, with none of our credentials. A copy of the site
        // sits behind basic auth, so that fetch 401s and the logo silently
        // does not appear. Found by Matt on 2026-11-10: the staging Founder
        // email arrived with no logo.
        config([
            'frith.site.canonical_host' => 'frith.community',
            'app.url' => 'https://frith.community',
        ]);

        $this->app->instance('request', Request::create('https://staging.example.com/'));
        BrandAsset::forget();

        $url = BrandAsset::forMail('brand/logo/png/frith-logo-horizontal-fullcolour-email.png');

        $this->assertStringStartsWith('https://frith.community/brand/logo/png/', $url);
        $this->assertStringContainsString('?v=', $url);
    }

    #[Test]
    public function the_real_site_serves_its_own_email_images(): void
    {
        config([
            'frith.site.canonical_host' => 'frith.community',
            'app.url' => 'https://frith.community',
        ]);

        $this->app->instance('request', Request::create('https://frith.community/'));
        BrandAsset::forget();

        $this->assertSame(
            BrandAsset::url('brand/logo/png/frith-logo-horizontal-fullcolour-email.png'),
            BrandAsset::forMail('brand/logo/png/frith-logo-horizontal-fullcolour-email.png'),
        );
    }

    #[Test]
    public function the_email_layout_uses_an_image_an_email_client_can_fetch(): void
    {
        // The whole email system has exactly one image. If it ever gains
        // another, it has to go through forMail() too.
        $layout = file_get_contents(resource_path('views/components/mail/layout.blade.php'));

        $this->assertSame(1, substr_count($layout, '<img'));
        $this->assertStringContainsString('BrandAsset::forMail(', $layout);
        $this->assertStringNotContainsString('BrandAsset::url(', $layout);

        // And it must stay a PNG: no major client renders SVG in email.
        $this->assertStringContainsString('.png', $layout);
    }
}
