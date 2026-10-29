#!/usr/bin/php -f
<?php

namespace AdminZone;

/**
 * System.spoje.net - Odeslání Upomínek
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017-2023 Vitex Software
 */

define('EASE_APPNAME', 'Upominkovac');
define('EASE_LOGGER', 'syslog|console');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$reminder = new \AdminZone\Upominac();
$reminder->invoicer->logBanner(\Ease\Shared::appName());
$reminder->processAllDebts($reminder->getDebths());
