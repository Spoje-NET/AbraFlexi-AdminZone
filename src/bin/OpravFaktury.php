<?php

namespace AdminZone;

define('EASE_APPNAME', 'OpravFaktury');
define('EASE_LOGGER', 'console|syslog|email');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$odden = 0;
$date1 = new \DateTime();
$date2 = new \DateTime("01-05-2017");
$date3 = new \DateTime("06-06-2017");

$odden = $date2->diff($date1)->format("%a");
$doden = $date3->diff($date1)->format("%a");

$invoicer = new \AbraFlexi\FakturaVydana();

$cond = "typDokl='code:FAKTURA' AND ( datVyst gt '" . \AbraFlexi\RW::timestampToFlexiDate(mktime(
    0,
    0,
    0,
    date("m"),
    date("d") - ( $odden + 1 ),
    date("Y")
)) . "' AND  datVyst lt '" . \AbraFlexi\RW::timestampToFlexiDate(mktime(
    0,
    0,
    0,
    date("m"),
    date("d") - ( $doden - 1 ),
    date("Y")
)) . "' )";

$invoicer->addStatusMessage($cond, 'debug');

$faktury = $invoicer->getColumnsFromAbraFlexi(['id', 'kod'], $cond);

$invoicer->addStatusMessage("Faktur: " . count($faktury));

foreach ($faktury as $fcount => $fa) {
    $invoicer->dataReset();
    $invoicer->loadFromAbraFlexi((int) $fa['id']);
    $polozky = $invoicer->getDataValue('polozkyFaktury');
    if (!count($polozky)) {
        $invoicer->addStatusMessage(
            _('Faktura bez polozek') . ' ' . $fa['id'],
            'warning'
        );
        continue;
    }
    $datUcto = $invoicer->getDataValue('datUcto');
    $datVyst = $invoicer->getDataValue('datVyst');
    $invoicer->dataReset();
    $invoicer->setMyKey((int) $fa['id']);
//    $invoicer->setDataValue('duzpPuv', $duzpPuv);
//    $invoicer->setDataValue('duzpUcto', $duzpPuv);
    $invoicer->setDataValue('datVyst', $datUcto);
    $itemsToSave = 0;
    foreach ($polozky as $polozka) {
        if ((strlen($polozka['cenik']) && ($polozka['ucetni'] == 'false')) || strlen($polozka['datUcto'])) {
            $invoicer->addArrayToBranch(
                ['id' => (int) $polozka['id'], 'ucetni' => false,
                'kopDatUcto' => true, 'datUcto' => $datUcto, 'poznam' => 'ucetni oprava 1/2'],
                'polozkyFaktury'
            );
            $itemsToSave++;
        }
    }
    if ($itemsToSave) {
        $invoicer->insertToAbraFlexi();

        foreach ($polozky as $polozka) {
            if ((strlen($polozka['cenik']) && ($polozka['ucetni'] == 'false')) || strlen($polozka['datUcto'])) {
                $invoicer->addArrayToBranch(
                    ['id' => (int) $polozka['id'], 'ucetni' => true,
                    'kopDatUcto' => true, 'poznam' => 'ucetni opraveno 2/2 ' . date("c")],
                    'polozkyFaktury'
                );
            }
        }
        $invoicer->insertToAbraFlexi();
        $invoicer->addStatusMessage(
            $fa['id'] . ' ' . $fa['kod'] . ' ' . $datVyst,
            ($invoicer->lastResponseCode == 201) ? 'success' : 'warning'
        );
    }

    $invoicer->addStatusMessage($fcount . ' z ' . count($faktury));
}
