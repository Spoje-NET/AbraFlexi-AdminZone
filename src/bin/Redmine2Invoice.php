<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone;

define('EASE_LOGGER', 'console|syslog|email');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$redminer = new Importer\RedmineRestClient();

$invoicer = new FakturaVydana();

$month_ini = new \DateTime("first day of last month");
$month_end = new \DateTime("last day of last month");

$invoicer->takeItemsFromArray($redminer->getTimeEntries(
    constant('REDMINE_PROJECT'),
    $month_ini->format('Y-m-d'),
    $month_end->format('Y-m-d')
));

echo $invoicer->apiURL;
