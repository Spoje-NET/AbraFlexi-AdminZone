<?php

namespace AdminZone;

/**
 * AdminZone - CleanUP emails with ,.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018 Spoje.Net
 */

define('EASE_APPNAME', 'MatchFixer');
define('EASE_LOGGER', 'syslog|email|console');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;
