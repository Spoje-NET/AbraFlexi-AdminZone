<?php

namespace AdminZone;

/**
 * AdminZone - WebHook Acceptor & Saver to file cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 Spoje.Net
 */

define('EASE_APPNAME', 'WebHookAcceptor');
require_once 'includes/Init.php';

$hooker = new HookReciever();
//$hooker->debug = true;

$apiResponseRaw = $hooker->listen();

if (!empty($apiResponseRaw) && $hooker->takeApiChanges($apiResponseRaw)) {
    $hooker->saveWebhookData($hooker->onlyFreshHooks($hooker->changes));
}
