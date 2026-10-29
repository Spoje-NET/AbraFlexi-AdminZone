<?php

namespace AdminZone;

/**
 * System.Spoje.Net - WebHook Acceptor & Saver to SQL Cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 Spoje.Net
 */
define('EASE_APPNAME', 'WebHookAcceptor');
require_once __DIR__ . '/../src/includes/Init.php';

$hooker = new HookReciever();
//$hooker->debug = true;

$apiResponseRaw = $hooker->listen();

include('webhookdata.php');

if (!empty($apiResponseRaw) && $hooker->takeApiChanges($apiResponseRaw)) {
    $hooker->saveWebhookData($hooker->onlyFreshHooks($hooker->changes));
}
