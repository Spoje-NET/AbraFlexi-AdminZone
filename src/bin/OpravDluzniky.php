<?php

namespace AdminZone;

define('EASE_APPNAME', 'OpravDluzniky');
define('EASE_LOGGER', 'console|syslog|email');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$invoicer = new \AbraFlexi\FakturaVydana();

$cond = ['datSplat lte \'' . \AbraFlexi\RW::dateToFlexiDate(new \DateTime()) . '\' AND (stavUhrK is null OR stavUhrK eq \'stavUhr.castUhr\') AND storno eq false'];

$faDatakturyRaw = $invoicer->getColumnsFromAbraFlexi('*', $cond);

$invoicer->addStatusMessage("Faktur: " . count($faDatakturyRaw));
$customer = new \AbraFlexi\Adresar();

foreach ($faDatakturyRaw as $faData) {
    $faDataktury[$faData['firma']][$faData['id']] = $faData;
}

$fcount = 0;

foreach ($faDataktury as $firma => $fakturyFirmy) {
    $upominky['datUp1'] = false;
    $upominky['datUp2'] = false;
    $upominky['datSmir'] = false;

    foreach ($fakturyFirmy as $fakturaData) {
        if (!empty($fakturaData['datUp1'])) {
            $upominky['datUp1'] = true;
        }
        if (!empty($fakturaData['datUp2'])) {
            $upominky['datUp2'] = true;
        }
        if (!empty($fakturaData['datSmir'])) {
            $upominky['datSmir'] = true;
        }
    }


    $stitkyRaw = $customer->getColumnsFromAbraFlexi(
        ['stitky', 'nazev'],
        ['id' => $firma]
    );
    $stitkyKlienta = \AbraFlexi\Stitek::listToArray($stitkyRaw[0]['stitky']);

    $invoicer->addStatusMessage($firma . ' ' . $stitkyRaw[0]['nazev'], 'debug');

    $labelsCountOriginal = count($stitkyKlienta);

    if ($upominky['datUp1']) {
        $stitkyKlienta['UPOMINKA1'] = 'UPOMINKA1';
    } else {
        unset($stitkyKlienta['UPOMINKA1']);
    }
    if ($upominky['datUp2']) {
        $stitkyKlienta['UPOMINKA2'] = 'UPOMINKA2';
    } else {
        unset($stitkyKlienta['UPOMINKA2']);
    }
    if ($upominky['datSmir']) {
        $stitkyKlienta['UPOMINKA3'] = 'UPOMINKA3';
    } else {
        unset($stitkyKlienta['UPOMINKA3']);
    }

    if ($labelsCountOriginal != count($stitkyKlienta)) {
        $customer->insertToAbraFlexi(['id' => $firma, 'stitky@removeAll' => true,
            'stitky' => $stitkyKlienta]);
    }
    $fcount++;
    $invoicer->addStatusMessage($fcount . ' z ' . count($faDataktury));
}
