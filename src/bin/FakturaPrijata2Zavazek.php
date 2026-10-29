#!/usr/bin/php -f
<?php

/**
 * System.spoje.net - Vyčistí databázi histore
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019 Vitex Software
 */

namespace AdminZone;

define('EASE_APPNAME', 'AbraFlexiSetup');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$copyer = new \AbraFlexi\Bricks\Convertor(
    new \AbraFlexi\FakturaPrijata('code:00000001/19'),
    new Zavazek(['typDokl' => 'code:OST-ZÁVAZKY', 'stitky' => 'SYSTEM', 'ucetni' => true]),
    new FakturaPrijata_to_Zavazek()
);

$copyer->convertDocument();
$zavazek = $copyer->getOutput();
$zavazek->sync();
