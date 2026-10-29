<?php

namespace AdminZone;

/**
 * AdminZone - Unblock Net: remove disconnect labels from customers who do not owe.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2026 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForUser();

$oPage->addItem(new ui\PageTop(_('Unblock Net')));

exec('system killall python2');

$command = 'python2 ./bin/runner.py php -f ./bin/UnblockNetW.php > /dev/null 2>&1 &';
exec($command);
$oPage->redirect('monitor.php');

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
