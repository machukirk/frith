<?php

namespace Tests\Feature;

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
     * looked correct in isolation and wrong in the email — a white rectangle on
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

        // GD reports alpha 0–127, where 127 is fully transparent.
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
            "{$file} is mostly opaque — the background is probably baked in",
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
}
