<?php

declare(strict_types=1);

/**
 * AdminZone - save posted form into AbraFlexi record.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2026 Spoje.Net
 */

namespace AdminZone;

/**
 * Takes data posted by record form and stores them into AbraFlexi.
 */
class FormHandler
{
    /**
     * Save posted data (if any) using given AbraFlexi record object.
     *
     * @param \AbraFlexi\RW $record record to fill and sync
     *
     * @return null|bool null when nothing was posted, otherwise save result
     */
    public static function handlePost(ui\WebPage $oPage, \AbraFlexi\RW $record): ?bool
    {
        if (!$oPage->isPosted()) {
            return null;
        }

        $data = $_POST;
        unset($data['class'], $data['gotolist']);
        $record->takeData($data);
        $result = (bool) $record->sync();
        $record->addStatusMessage(
            $result ? _('Záznam byl uložen') : _('Záznam nebyl uložen'),
            $result ? 'success' : 'warning'
        );

        return $result;
    }
}
