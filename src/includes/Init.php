<?php

/**
 * AdminZone - Init aplikace.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2024 Spoje.Net
 */

namespace AdminZone;

require_once __DIR__ . '/../../vendor/autoload.php';
if (!defined('EASE_APPNAME')) {
    define('EASE_APPNAME', 'AdminZone');
}

new \Ease\Locale('cs_CZ', __DIR__ . '/../../i18n', 'adminzone');

try {
    session_start();
} catch (Exception $exc) {
    echo $exc->getTraceAsString();
}


/**
 * Objekt uživatele User nebo Anonym
 * @global \Ease\User
 */
$oUser = \Ease\Shared::user(null, 'AdminZone\User');

\Ease\Shared::init([
    'ABRAFLEXI_LOGIN', 'ABRAFLEXI_PASSWORD', 'ABRAFLEXI_URL', 'ABRAFLEXI_COMPANY',
// 'ABRAFLEXI_TYP_FAKTURY', 'ABRAFLEXI_CENIK',
    'LABEL_OVERPAY', 'LABEL_INVOICE_MISSING', 'LABEL_UNIDENTIFIED', 'FOREGIN_ENPOINT',
    'ZABBIX_SERVER', 'ZABBIX_HOST', 'ZABBIX_SOURCE',
        ], __DIR__ . '/../../.env', true);

// Web requests act as the signed-in operator (AbraFlexi authSessionId token)
if (method_exists($oUser, 'getAuthSessionId') && $oUser->getAuthSessionId()) {
    putenv('ABRAFLEXI_AUTHSESSID=' . $oUser->getAuthSessionId());
}

$loggers = ['syslog'];
if (\Ease\Shared::cfg('ZABBIX_SERVER')) {
    $loggers[] = '\AdminZone\LogToZabbix';
}

if (PHP_SAPI == 'cli') {
    $loggers[] = 'console';
} else {
    /* @var $oPage \Sys\WebPage */
    $oPage = new ui\WebPage('');
}

define('EASE_LOGGER', implode('|', $loggers));

$engine = new AbraFlexiEngine();
