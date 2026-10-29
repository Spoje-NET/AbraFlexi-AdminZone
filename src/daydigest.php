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

$oPage->addItem(new ui\PageTop(_('Spoje.Net')));

$reportDir = '/var/run/abraflexi-digest/';
$d = dir($reportDir);
while (false !== ($entry = $d->read())) {
    if (pathinfo($entry, PATHINFO_EXTENSION) == 'html') {
        $oPage->container->addItem(realpath($reportDir . $entry));

        $oPage->container->addItem(file_get_contents($reportDir . $entry));
    }
}
$d->close();

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
