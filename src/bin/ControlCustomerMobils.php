<?php

namespace AdminZone;

use AbraFlexi\Bricks\Customer;

define('EASE_APPNAME', 'ContorlCustomersCellPhones');
define('EASE_LOGGER', 'console|syslog|email');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$customer = new Customer();
$clients = $customer->getCustomerList();
$customer->addStatusMessage(sprintf(_('%s Clients Found'), count($clients)));
$position = 0;
$oPage = new \Ease\WebPage();

foreach ($clients as $cid => $clientIDs) {
    $company = new \AbraFlexi\Adresar((int) $clientIDs['id']);
    $mobil = $company->getAnyPhoneNumber();
    if (is_null($mobil)) {
        $status = 'error';
    } else {
        $status = 'warning';
        $mobils = $company->lastResult['adresar'][0];

        if (array_key_exists('kontakty', $mobils) && count($mobils['kontakty'])) {
            foreach ($mobils['kontakty'] as $kontakt) {
                if (($kontakt['primarni'] == 'true') && strlen(trim($kontakt['mobil']))) {
                    $status = 'success';
                    break;
                }
            }
        }
    }
    $company->addStatusMessage(
        sprintf(
            '#%d/%d: %-40s %-40s %s %s',
            $position++,
            count($clients),
            \AbraFlexi\Functions::uncode($company->getRecordIdent()),
            $company->getDataValue('nazev'),
            $company->getApiURL(),
            $mobil
        ),
        $status
    );
}

$oPage->addItem($oPage->getStatusMessagesAsHtml());

file_put_contents('/tmp/mobils.txt', $oPage->draw());
