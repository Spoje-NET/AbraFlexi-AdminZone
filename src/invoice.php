<?php

namespace AdminZone;

/**
 * AdminZone - Faktura
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

require_once './includes/Init.php';

$oPage->onlyForLogged();

$oPage->addItem(new ui\PageTop(_('Faktura')));

$invoice = new \AbraFlexi\FakturaVydana();

$invoiceForm = new ui\InvoiceForm($invoice);

$oPage->container->addItem($invoiceForm);

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
