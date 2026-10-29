<?php

namespace AdminZone;

/**
 * Párování faktur
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2017 Spoje.Net
 */

define('EASE_APPNAME', 'ParujFaktury');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;
