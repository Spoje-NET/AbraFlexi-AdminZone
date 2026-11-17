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

namespace AdminZone;

/**
 * Basic editing of a product picture: rotation by right angles, cropping and a thumbnail.
 *
 * Works on the bytes of the picture and gives the bytes of the same format back (JPEG, PNG, GIF
 * or WebP), so the result can be stored as the attachment of the pricelist item as it is.
 * Needs the GD extension and no connection to AbraFlexi.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
final class ImageEditor
{
    /**
     * Formats the editor can read and write, MIME => file extension.
     */
    public const FORMATS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Longest side of the thumbnail in pixels.
     */
    public const THUMBNAIL_SIZE = 150;

    /**
     * Refuse pictures bigger than this many pixels (the decoded picture takes about 4 bytes each).
     */
    public const MAX_PIXELS = 30_000_000;

    /**
     * Smallest side of a crop in pixels.
     */
    public const MIN_CROP = 8;

    /**
     * MIME type of the picture or null when it is not a picture the editor handles.
     */
    public static function mimeOf(string $body): ?string
    {
        $info = $body === '' ? false : @getimagesizefromstring($body);

        if ($info === false || !isset(self::FORMATS[$info['mime']])) {
            return null;
        }

        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }

        return $info['mime'];
    }

    /**
     * Turn the picture clockwise by a multiple of 90 degrees.
     *
     * @throws \InvalidArgumentException when the picture cannot be handled or the angle is not a right angle
     */
    public static function rotate(string $body, int $degrees): string
    {
        if ($degrees % 90 !== 0) {
            throw new \InvalidArgumentException(_('The picture can be turned by right angles only'));
        }

        $mime = self::requireMime($body);
        $turn = (($degrees % 360) + 360) % 360;

        if ($turn === 0) {
            return $body;
        }

        $image = self::decode($body);
        // GD turns counter-clockwise
        $rotated = imagerotate($image, (float) (360 - $turn), 0);

        if ($rotated === false) {
            throw new \InvalidArgumentException(_('The picture could not be turned'));
        }

        return self::encode(self::keepAlpha($rotated, $mime), $mime);
    }

    /**
     * Cut a rectangle out of the picture.
     *
     * The rectangle is given as fractions (0..1) of the picture, so the browser need not know the
     * real size of the picture.
     *
     * @param float $x      left edge
     * @param float $y      top edge
     * @param float $width  width
     * @param float $height height
     *
     * @throws \InvalidArgumentException when the picture cannot be handled or the rectangle is outside of it
     */
    public static function crop(string $body, float $x, float $y, float $width, float $height): string
    {
        $mime = self::requireMime($body);

        foreach ([$x, $y, $width, $height] as $value) {
            if (!is_finite($value)) {
                throw new \InvalidArgumentException(_('The crop area is not valid'));
            }
        }

        if ($x < 0 || $y < 0 || $width <= 0 || $height <= 0 || $x + $width > 1.0001 || $y + $height > 1.0001) {
            throw new \InvalidArgumentException(_('The crop area is outside of the picture'));
        }

        $image = self::decode($body);
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        $left = (int) round($x * $imageWidth);
        $top = (int) round($y * $imageHeight);
        $cutWidth = min((int) round($width * $imageWidth), $imageWidth - $left);
        $cutHeight = min((int) round($height * $imageHeight), $imageHeight - $top);

        if ($cutWidth < self::MIN_CROP || $cutHeight < self::MIN_CROP) {
            throw new \InvalidArgumentException(_('The crop area is too small'));
        }

        $cropped = imagecrop($image, ['x' => $left, 'y' => $top, 'width' => $cutWidth, 'height' => $cutHeight]);

        if ($cropped === false) {
            throw new \InvalidArgumentException(_('The picture could not be cropped'));
        }

        return self::encode(self::keepAlpha($cropped, $mime), $mime);
    }

    /**
     * Shrink the picture so its longer side is at most $size pixels (a smaller picture is kept).
     *
     * @throws \InvalidArgumentException when the picture cannot be handled
     */
    public static function thumbnail(string $body, int $size = self::THUMBNAIL_SIZE): string
    {
        $mime = self::requireMime($body);
        $image = self::decode($body);
        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) <= $size) {
            return $body;
        }

        $scale = $size / max($width, $height);
        $scaled = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

        if ($scaled === false) {
            throw new \InvalidArgumentException(_('The thumbnail could not be made'));
        }

        return self::encode(self::keepAlpha($scaled, $mime), $mime);
    }

    /**
     * Size of the picture in pixels as [width, height] or null when it is not a picture.
     *
     * @return null|array{0: int, 1: int}
     */
    public static function size(string $body): ?array
    {
        $info = self::mimeOf($body) === null ? false : getimagesizefromstring($body);

        return $info === false ? null : [(int) $info[0], (int) $info[1]];
    }

    private static function requireMime(string $body): string
    {
        $mime = self::mimeOf($body);

        if ($mime === null) {
            throw new \InvalidArgumentException(_('Only JPEG, PNG, GIF and WebP pictures can be edited'));
        }

        return $mime;
    }

    private static function decode(string $body): \GdImage
    {
        $image = @imagecreatefromstring($body);

        if ($image === false) {
            throw new \InvalidArgumentException(_('The picture could not be read'));
        }

        return $image;
    }

    /**
     * Make the transparency of PNG, GIF and WebP survive the edit.
     */
    private static function keepAlpha(\GdImage $image, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
    }

    private static function encode(\GdImage $image, string $mime): string
    {
        ob_start();

        $written = match ($mime) {
            'image/jpeg' => imagejpeg($image, null, 90),
            'image/png' => imagepng($image),
            'image/gif' => imagegif($image),
            default => imagewebp($image, null, 90),
        };
        $body = (string) ob_get_clean();

        if (!$written || $body === '') {
            throw new \InvalidArgumentException(_('The picture could not be saved'));
        }

        return $body;
    }
}
