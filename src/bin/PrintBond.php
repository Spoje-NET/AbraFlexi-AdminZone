<?php

namespace AdminZone;

/**
 * AdminZone - CleanUP emails with ,.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018 Spoje.Net
 */

define('EASE_APPNAME', 'PrintBond');
define('EASE_LOGGER', 'syslog|console');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$invoiceID = 'VF2-5080/2019';

$invoice = new \AbraFlexi\FakturaVydana(
    \AbraFlexi\Functions::code($invoiceID),
    ['defaultUrlParams' => ['relations' => 'vazby']]
);

print_r($invoice->getData());
