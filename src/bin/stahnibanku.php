<?php

namespace AdminZone;

/**
 * Párování fakrur
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018 Spoje.Net
 */

define('EASE_APPNAME', 'StahniBanku');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$banker = new \AbraFlexi\Banka();

if (!$banker->stahnoutVypisyOnline()) {
    $banker->addStatusMessage('Banka Offline!', 'error');
}
