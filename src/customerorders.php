<?php

namespace AdminZone;

/**
 * AdminZone - Hlavní strana.
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

$oPage->container->addItem(new \Ease\Html\H1Tag(\AbraFlexi\Functions::uncode($customer->getRecordCode()) . ' ' . new \Ease\Html\ATag(
    'adresar.php?id=' . $customer->getRecordCode(),
    $customer->getDataValue('nazev')
)));

$fetcher = new \AbraFlexi\FakturaVydana();

$oPage->container->addItem(new ui\OrdersListing(
    $fetcher,
    ['firma' => $customer],
    _('Invoices')
));

$fetcher2 = new \AbraFlexi\RO(
    null,
    ['evidence' => 'objednavka-prijata']
);

$oPage->container->addItem(new ui\OrdersListing(
    $fetcher2,
    ['firma' => $customer],
    _('Orders')
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
