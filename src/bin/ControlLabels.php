<?php

/*
 * How to Fix Trimmed Banking Labels in AbraFlexi
 * using mhujer/fio-api-php and spoje.net/abraflexi
 */

define('EASE_LOGGER', 'console|syslog|email');
define('EASE_APPNAME', 'ControlLabels');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$engine = new \AbraFlexi\Evidence(
    new \AbraFlexi\Adresar(),
    ["stitky = 'code:UPOMINKA1' OR stitky = 'code:UPOMINKA2' OR stitky = 'code:UPOMINKA3' OR stitky = 'code:ODPOJENO' OR stitky = 'code:NEPLATIC'",'limit' => 0]
);

$upominac = new \AdminZone\Upominac();
$engine->engine->logBanner();

foreach ($engine->getEvidenceObjects() as $address) {
    $custdebts = $upominac->getAllDebts(20, ['firma' => $address,'limit' => 0]);

    $score = $upominac->getCustomerScore($address);

    if ($score == 0) {
        $address->addStatusMessage(
            $address . ' ' . $address->getDataValue('nazev') . ' ' . $address->getDataValue('stitky') . ' SCORE: ' . $score,
            $upominac->enableCustomer($address->getData()) ? 'success' : 'error'
        );
    } else {
        $address->addStatusMessage(
            $address . ' ' . $address->getDataValue('nazev') . ' ' . $address->getDataValue('stitky') . ' SCORE: ' . $score,
            'warning'
        );
    }
}
