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
 * Pictures of a pricelist item as AbraFlexi keeps them: attachments of the item whose note says
 * what the picture is ("image" is the large one, "thumbnail" the small one). ClientZone reads
 * them the same way, so what is stored here is what customers see.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
final class ProductImages
{
    public const IMAGE = 'image';
    public const THUMBNAIL = 'thumbnail';

    /**
     * Kinds of pictures, which the note of the attachment holds.
     */
    public const TYPES = [self::IMAGE, self::THUMBNAIL];

    public static function isType(string $type): bool
    {
        return \in_array($type, self::TYPES, true);
    }

    /**
     * The picture of the item.
     *
     * @return null|array{body: string, mime: string} null when the item has no such picture
     */
    public static function get(int $itemId, string $type = self::IMAGE): ?array
    {
        $cenik = self::item($itemId);
        $url = $cenik->getAbraFlexiURL();

        foreach (self::attachments($cenik, $type) as $attachment) {
            if ($cenik->doCurlRequest($url.'/prilohy/'.$attachment['id'].'/content', 'GET') !== 200) {
                continue;
            }

            $body = (string) $cenik->lastCurlResponse;
            $mime = ImageEditor::mimeOf($body);

            if ($mime !== null) {
                return ['body' => $body, 'mime' => $mime];
            }
        }

        return null;
    }

    /**
     * Store the picture as the one of the item; the previous one of the same kind is removed.
     *
     * The new attachment is added first, so a failure leaves the old picture in place.
     */
    public static function store(int $itemId, string $type, string $body): bool
    {
        $mime = ImageEditor::mimeOf($body);

        if ($mime === null || !self::isType($type)) {
            return false;
        }

        $cenik = self::item($itemId);
        $old = array_column(self::attachments($cenik, $type), 'id');

        try {
            $added = \AbraFlexi\Priloha::addAttachment($cenik, $type.'.'.ImageEditor::FORMATS[$mime], $body, $mime);
        } catch (\AbraFlexi\Exception $exc) {
            error_log('AdminZone product picture upload failed: '.$exc->getMessage());

            return false;
        }

        $marked = new \AbraFlexi\Priloha(null, $cenik->getConnectionOptions());

        if (!$marked->sync(['id' => $added->getMyKey(), 'poznam' => $type, 'exportNaEshop' => true])) {
            $added->deleteFromAbraFlexi();

            return false;
        }

        foreach ($old as $id) {
            $obsolete = new \AbraFlexi\Priloha((int) $id, $cenik->getConnectionOptions());
            $obsolete->deleteFromAbraFlexi();
        }

        return true;
    }

    /**
     * Store the picture and the thumbnail made from it.
     */
    public static function storeWithThumbnail(int $itemId, string $body): bool
    {
        return self::store($itemId, self::IMAGE, $body)
            && self::store($itemId, self::THUMBNAIL, ImageEditor::thumbnail($body));
    }

    private static function item(int $itemId): \AbraFlexi\Cenik
    {
        $cenik = new \AbraFlexi\Cenik($itemId, ['nativeTypes' => false]);

        if ((int) $cenik->getMyKey() !== $itemId) {
            throw new \RuntimeException(sprintf('Pricelist item %d not found', $itemId));
        }

        return $cenik;
    }

    /**
     * @return array<int, array{id: int|string, poznam: string}>
     */
    private static function attachments(\AbraFlexi\Cenik $cenik, string $type): array
    {
        $cenik->defaultUrlParams['detail'] = 'custom:id,poznam';

        return array_values(array_filter(
            \AbraFlexi\Priloha::getAttachmentsList($cenik),
            static fn (array $attachment): bool => ($attachment['poznam'] ?? '') === $type,
        ));
    }
}
