<?php

declare(strict_types=1);

/**
 * This file is part of the AdminZone package
 *
 * https://github.com/VitexSoftware/AbraFlexi-AdminZone
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\AdminZone;

use AdminZone\ImageEditor;
use PHPUnit\Framework\TestCase;

class ImageEditorTest extends TestCase
{
    /**
     * A 40x20 picture, left half red and right half blue.
     */
    private static function picture(string $mime = 'image/png', bool $alpha = false): string
    {
        $image = imagecreatetruecolor(40, 20);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefilledrectangle($image, 0, 0, 19, 19, (int) imagecolorallocatealpha($image, 255, 0, 0, $alpha ? 64 : 0));
        imagefilledrectangle($image, 20, 0, 39, 19, (int) imagecolorallocatealpha($image, 0, 0, 255, $alpha ? 64 : 0));

        ob_start();

        match ($mime) {
            'image/jpeg' => imagejpeg($image, null, 100),
            'image/gif' => imagegif($image),
            'image/webp' => imagewebp($image, null, 100),
            default => imagepng($image),
        };

        return (string) ob_get_clean();
    }

    private static function pixel(string $body, int $x, int $y): array
    {
        $image = imagecreatefromstring($body);
        $rgba = imagecolorsforindex($image, imagecolorat($image, $x, $y));

        return [$rgba['red'], $rgba['green'], $rgba['blue']];
    }

    public function testMimeOf(): void
    {
        foreach (array_keys(ImageEditor::FORMATS) as $mime) {
            $this->assertSame($mime, ImageEditor::mimeOf(self::picture($mime)));
        }

        $this->assertNull(ImageEditor::mimeOf(''));
        $this->assertNull(ImageEditor::mimeOf('<svg xmlns="http://www.w3.org/2000/svg"/>'));
        $this->assertNull(ImageEditor::mimeOf('not a picture'));
    }

    public function testRotateClockwiseSwapsSides(): void
    {
        $rotated = ImageEditor::rotate(self::picture(), 90);

        $this->assertSame([20, 40], ImageEditor::size($rotated));
        // red was left, clockwise it goes to the top
        $this->assertSame([255, 0, 0], self::pixel($rotated, 10, 5));
        $this->assertSame([0, 0, 255], self::pixel($rotated, 10, 35));
    }

    public function testRotateCounterClockwiseAndFull(): void
    {
        $left = ImageEditor::rotate(self::picture(), -90);

        $this->assertSame([0, 0, 255], self::pixel($left, 10, 5));
        $this->assertSame([40, 20], ImageEditor::size(ImageEditor::rotate(self::picture(), 180)));
        $this->assertSame([255, 0, 0], self::pixel(ImageEditor::rotate(self::picture(), 180), 35, 10));
        $this->assertSame(self::picture(), ImageEditor::rotate(self::picture(), 360));
    }

    public function testRotateRefusesOddAngle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ImageEditor::rotate(self::picture(), 45);
    }

    public function testRotateKeepsFormat(): void
    {
        foreach (array_keys(ImageEditor::FORMATS) as $mime) {
            $this->assertSame($mime, ImageEditor::mimeOf(ImageEditor::rotate(self::picture($mime), 90)));
        }
    }

    public function testRotateKeepsTransparency(): void
    {
        $rotated = imagecreatefromstring(ImageEditor::rotate(self::picture('image/png', true), 90));
        $rgba = imagecolorsforindex($rotated, imagecolorat($rotated, 10, 5));

        $this->assertGreaterThan(0, $rgba['alpha']);
    }

    public function testCropTakesTheRectangle(): void
    {
        $cropped = ImageEditor::crop(self::picture(), 0.5, 0, 0.5, 1);

        $this->assertSame([20, 20], ImageEditor::size($cropped));
        $this->assertSame([0, 0, 255], self::pixel($cropped, 2, 2));
    }

    public function testCropRefusesWrongArea(): void
    {
        foreach ([[-0.1, 0, 0.5, 0.5], [0.6, 0, 0.5, 0.5], [0, 0, 0, 0.5], [0, 0, 0.5, 2], [0, 0, 0.01, 0.01], [NAN, 0, 0.5, 0.5]] as $area) {
            try {
                ImageEditor::crop(self::picture(), ...$area);
                $this->fail('Crop '.json_encode($area).' should be refused');
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testNotAPictureIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ImageEditor::crop('<svg/>', 0, 0, 1, 1);
    }

    public function testThumbnail(): void
    {
        $big = imagecreatetruecolor(600, 300);
        ob_start();
        imagepng($big);
        $body = (string) ob_get_clean();

        $this->assertSame([150, 75], ImageEditor::size(ImageEditor::thumbnail($body)));

        $small = self::picture();
        $this->assertSame($small, ImageEditor::thumbnail($small));
    }
}
