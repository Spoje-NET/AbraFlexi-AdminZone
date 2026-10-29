<?php

namespace AdminZone;

use AbraFlexi\Bricks\GateKeeper;

/**
 * AdminZone
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

require_once 'includes/Init.php';

$embed = $oPage->getRequestValue('embed');
$id = $oPage->getRequestValue('id');
$evidence = $oPage->getRequestValue('evidence');

$document = new \AbraFlexi\RO(
    is_numeric($id) ? intval($id) : $id,
    ['evidence' => $evidence]
);

if (
    !is_null($document->getMyKey()) && GateKeeper::isAccessibleBy(
        $document,
        $oUser
    )
) {
    $document->setEvidence('uzivatelsky-dotaz');
    $changes = $document->performRequest('code:FAKTURY-ZMENY/call.json?id_dokladu=' . $id);
} else {
    die(_('Wrong call'));
}
