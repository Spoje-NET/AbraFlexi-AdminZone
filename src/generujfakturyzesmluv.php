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

$oPage->addItem(new ui\PageTop(_('Generate Invoices from contracts')));

//$engine = new \AbraFlexi\RW($id,['evidence' => $oPage->getRequestValue('evidence')]);
exec('system killall python2');

$command = 'python2 ./bin/runner.py php -f ./bin/GenerujFakturyZeSmluvW.php > /dev/null 2>&1 &';
exec($command);
$oPage->redirect('monitor.php');

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
