#!/usr/bin/php -f
<?php

/**
 * System.spoje.net - Synchronizace čísla změny AbraFlexi
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */

define('EASE_APPNAME', 'ChangeVersionSyncer');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$hooker = new \AdminZone\HookReciever();
$hooker->defaultUrlParams = ['limit' => 1000];
$globalChangeVersion = intval($hooker->getGlobalVersion());
$lastProcessedVersion = $hooker->getLastCachedVersion();

$hooker->addStatusMessage(sprintf(
    _('Current saved change version: %s'),
    $globalChangeVersion
));

$hooker->addStatusMessage(sprintf(
    _('Current AbraFlexi change version: %s'),
    $lastProcessedVersion
));

if ($globalChangeVersion != $lastProcessedVersion) {
    $hooker->addStatusMessage(sprintf(
        _('Difference %d found'),
        $globalChangeVersion - $lastProcessedVersion
    ), 'warning');
    $hooker->saveLastProcessedVersion($globalChangeVersion);
    $hooker->addStatusMessage(_('Synced'), 'success');
}
