#!/usr/bin/php -f
<?php

/**
 * System.spoje.net - Zpracování zatím nezachycených úprav
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */

define('EASE_APPNAME', 'HistoryInitializer');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$hooker = new \AdminZone\HookReciever();
$hooker->defaultUrlParams = ['limit' => 1000];
$hooker->debug = true;
$globalChangeVersion = intval($hooker->getGlobalVersion());

$hooker->addStatusMessage(sprintf(
    _('Last processed change %d Last Availble Change: %d ( %d to process ) '),
    $hooker->lastProcessedVersion,
    $globalChangeVersion,
    $globalChangeVersion - $hooker->lastProcessedVersion
), 'info');

$hooker->lock();
try {
    while ($globalChangeVersion > $hooker->lastProcessedVersion) {
        $hooker->getColumnsFromAbraFlexi(
            '*',
            empty($hooker->lastProcessedVersion) ? [] :
            ['start' => $hooker->lastProcessedVersion + 1]
        );
        $hooker->takeChanges(json_decode($hooker->lastCurlResponse, true));
        if (count($hooker->changes)) {
            $hooker->addStatusMessage(sprintf(
                _('%d unprocessed changes found'),
                count($hooker->changes)
            ));
            $hooker->processAbraFlexiChanges();
            $hooker->addStatusMessage(sprintf(
                _('Done for now. Last processed change %d Last Availble Change: %d ( %d to process )'),
                $hooker->lastProcessedVersion,
                $hooker->getGlobalVersion(),
                $globalChangeVersion - $hooker->lastProcessedVersion
            ), 'success');
        } else {
            $hooker->addStatusMessage(_('No changes to process'));
            break;
        }
    }
} finally {
    $hooker->unlock();
}
$hooker->addStatusMessage(_('done'));
