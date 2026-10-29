<?php

namespace AdminZone;

/**
 * AdminZone - předloha stránky.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

require_once './includes/Init.php';

$oPage->onlyForUser();

$oPage->addItem(new ui\PageTop(_('Execute API Actions')));

$id = intval($oPage->getRequestValue('id'));

//$engine = new \AbraFlexi\RW($id,['evidence' => $oPage->getRequestValue('evidence')]);
exec('system killall python2');

$command = 'python2 ./bin/runner.py php -f ./bin/ExecuteApiAction.php ' . $id . ' > /dev/null 2>&1 &';
exec($command);
$oPage->redirect('monitor.php');

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
