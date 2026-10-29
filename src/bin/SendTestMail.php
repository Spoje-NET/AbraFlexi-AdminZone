#!/usr/bin/php -f
<?php

/**
 * System.spoje.net - Odeslání kontrolního mailu
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */

define('EASE_APPNAME', 'TestMailer');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$bláboly = json_decode(file_get_contents('http://api.blabot.net/?version=10b&amp;dictonary=2'));

$testMail = new \Ease\Mailer(
    isset($argv[1]) ? $argv[1] : constant('EASE_EMAILTO'),
    'Příliš žluťoučký kůň úpěl ďábelské ódy',
    $bláboly->blabot->result[0]
);
$testMail->addItem("\n" . __FILE__);
if ($testMail->send()) {
    $testMail->addStatusMessage('Testovací mail odeslán');
} else {
    $testMail->addStatusMessage('Testovací mail nebyl odeslán', 'error');
}
