<?php

namespace AdminZone;

/**
 * AdminZone - Client's history.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2017 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForUser();

$operation = $oPage->getRequestValue('operation');
$addressID = $oPage->getRequestValue('address');
$invoiceID = $oPage->getRequestValue('invoice');

$customer = new \AbraFlexi\Adresar(is_numeric($addressID) ? intval($addressID) : $addressID);

$oPage->addItem(new ui\PageTop(_('Orders') . ' ' . \AbraFlexi\Functions::uncode($customer->getRecordCode()) . ' ' . $customer->getDataValue('nazev')));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
