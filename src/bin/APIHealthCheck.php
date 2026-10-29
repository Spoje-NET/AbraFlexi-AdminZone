<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone;

define('EASE_LOGGER', 'console|syslog|email');
define('EASE_APPNAME', 'APIChecker');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$oPage = new \Ease\WebPage();
$checker = new ui\HealthCehck();

$checker->addStatusMessage('AbraFlexi', $checker->checkAbraFlexi());
$checker->addStatusMessage('SubReg', $checker->checkSubreg());
// Ipex is monitored by Zabbix via MultiFlexi credential
$checker->addStatusMessage('IspConfig', $checker->checkIspConfig());
$checker->addStatusMessage('Redmine', $checker->checkRedmine());
$checker->addStatusMessage('Fio', $checker->checkFio());
