<?php

namespace AdminZone;

/**
 * Udrzba Smluv
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018 Spoje.Net
 */

define('EASE_APPNAME', 'UdrzbaSmluv');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$contractor = new Smlouva();
$contractor->fixInconsistentDeclined();
