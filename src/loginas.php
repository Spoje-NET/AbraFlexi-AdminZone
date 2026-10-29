<?php

namespace AdminZone;

use AbraFlexi\Bricks\Customer;

/**
 * AdminZone - Přihlásí operátora jako uživatele.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2025 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForUser();

$address_id = $oPage->getRequestValue('id');
if (!strstr($address_id, 'code:')) {
    $address_id = intval($address_id);
}

$customer = new Customer();

$customer->getAdresar()->loadFromAbraFlexi($address_id);

$contacts = $customer->getAdresar()->getDataValue('kontakty');
if (count($contacts)) {
    $contact = current($contacts);

    $customer->getKontakt()->takeData($contact);

    if ($customer->loginSuccess()) {
        $customer->addStatusMessage(' ' . $customer->getAdresar()->getDataValue('nazev'));
        \Ease\Shared::user($customer);
    }
} else {
    $customer->addStatusMessage(
        sprintf(_('No contacts'), serialize($contactId)),
        'warning'
    );
}


$oPage->redirect('index.php');
