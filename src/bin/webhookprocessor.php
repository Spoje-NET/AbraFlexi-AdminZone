<?php

namespace AdminZone;

/**
 * AdminZone - Cached WebHooks processor
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2019-2024 Spoje.Net
 */

define('EASE_APPNAME', 'WHProcessor');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$hooker = new HookReciever();

//$hooker->debug = true;
$lockerPid = $hooker->locked();
if ($lockerPid == 0) {
    $hooker->lock();
    try {
        $hooker->processCachedChanges(100);
    } finally {
        $hooker->unlock();
    }
} else {
    $hooker->addStatusMessage(sprintf(
        _('Waiting for PID %d to be done'),
        $lockerPid
    ), 'debug');
}
