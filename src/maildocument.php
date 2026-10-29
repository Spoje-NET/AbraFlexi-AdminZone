<?php

namespace AdminZone;

/**
 * AdminZone
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

require_once 'includes/Init.php';

$evidence = $oPage->getRequestValue('evidence');
$documentID = $oPage->getRequestValue('id');

if (!empty($documentID) && !empty($evidence)) {
    $fetcher = new \AbraFlexi\RO(is_numeric($documentID) ? intval($documentID) : $documentID, ['evidence' => $evidence]);

    $kod = \AbraFlexi\Functions::uncode($fetcher->getRecordCode());

    $documentURI = dirname(\Ease\WebPage::getUri()) . '/getpdf.php?id=' . $documentID . '&evidence=' . $evidence . '&embed=true';

    $fetcher->unsetDataValue('kod');

    try {
        $fetcher->sendByMail(
            $oUser->getUserEmail(),
            sprintf(_('%s %s %s'), $fetcher->getDataValue('poznam'), $fetcher->getDataValue('nazev'), $kod),
            sprintf(_('your document %s is in attachment'), new \Ease\Html\ATag($documentURI, $kod))
        );
    } catch (\AbraFlexi\Exception $exc) {
        $oPage->addStatusMessage($exc->getMessage(), 'warning');
    }
    $oPage->redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php');
} else {
    die(_('Wrong call'));
}
