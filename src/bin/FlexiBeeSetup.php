#!/usr/bin/php -f
<?php

/**
 * System.spoje.net - Vyčistí databázi histore
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019 Vitex Software
 */

define('EASE_APPNAME', 'AbraFlexiSetup');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;
