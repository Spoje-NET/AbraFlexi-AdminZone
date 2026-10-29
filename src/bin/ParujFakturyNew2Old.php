<?php

namespace AdminZone;

/**
 * Párování faktur
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2024 Spoje.Net
 */

define('EASE_APPNAME', 'ParujFaktury');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$odden = 0;
$date1 = new \DateTime();
$date2 = new \DateTime(); //Parujeme do zacatku roku
$date2->setDate($date2->format('Y'), 1, 1);

$doden = $date2->diff($date1)->format("%a");

$invoiceSteamer = new ParovacFaktur(\Ease\Shared::instanced()->configuration);
$invoiceSteamer->logBanner('Invoice Matcher');

while ($odden < $doden) {
    $invoiceSteamer->setStartDay($odden++);
    $invoiceSteamer->invoicesMatchingByBank();
}
$invoiceSteamer->addStatusMessage(_('Párování hotovo'));
