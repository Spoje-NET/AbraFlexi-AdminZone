<?php

namespace AdminZone;

/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */

define('EASE_APPNAME', 'UpomenNepodepSmlouvy');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$reminder = new \AdminZone\Upominac();
$reminder->invoicer->logBanner();
$reminder->processAllContracts($reminder->getContracts());
