<?php

namespace AdminZone;

/**
 * AdminZone - Ručně spuštěné párování faktur.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

namespace AdminZone;

require_once 'includes/Init.php';

$oPage->onlyForUser();

$oPage->addItem(new ui\PageTop(_('Invoice Matching')));

////$engine = new \AbraFlexi\RW($id,['evidence' => $oPage->getRequestValue('evidence')]);
//exec('system killall python2');
//
//$command = 'python2 ./bin/runner.py php -f ./bin/ParujFakturyNew2OldW.php > /dev/null 2>&1 &';
//exec($command);
//$oPage->redirect('monitor.php');

$odden = 0;
$date1 = new \DateTime();
$date2 = new \DateTime(); //Parujeme do zacatku roku
$date2->modify('-1 month');

$doden = $date2->diff($date1)->format("%a");

$invoiceSteamer = new ParovacFaktur();
$invoiceSteamer->invoicer->logBanner('Invoice Matcher');
$invoiceSteamer->addStatusMessage(_('Zahajuji programové párování'));
while ($odden < $doden) {
    $invoiceSteamer->setStartDay($odden++);
    $invoiceSteamer->invoicesMatchingByBank();
}
$invoiceSteamer->addStatusMessage(_('Párování hotovo'));

$oPage->container->addItem($oPage->getStatusMessagesAsHtml());

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
