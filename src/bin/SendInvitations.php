#!/usr/bin/php -f
<?php

/**
 * System.spoje.net - Odeslání nastaví hesla kontaktům AbraFlexi a rozešle pozvánky
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

function prepareAccount($customer)
{
}

function invite($customer)
{
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
}

$customer = new \AbraFlexi\Bricks\Customer();
$clients = $customer->getCustomerList();

foreach ($clients as $client) {
    $customer->address->loadFromAbraFlexi($client);
    prepareAccount($customer);
}




exit();
