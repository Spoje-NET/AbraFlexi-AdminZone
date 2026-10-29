<?php

namespace AdminZone;

/**
 * AdminZone - Zdroj dat našeptávače kódů produktů
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForLogged();

header('Content-type: application/json');
$fetcher = new \AbraFlexi\Cenik();

$kod = $oPage->getRequestValue('kod');

if (is_null($kod)) {
    $items = [];
} else {
    $itemsRaw = $fetcher->getColumnsFromAbraFlexi(
        ['kod', 'nazev'],
        ['detail' => 'custom:kod,nazev', "( kod like similar '" . $kod . "') or ( nazev like similar '" . $kod . "' )"]
    );

    foreach ($itemsRaw as $item) {
        $items[] = ['value' => $item['kod'], 'label' => $item['kod'] . ': ' . $item['nazev']];
    }
}
echo json_encode($items);
