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

$oPage->addItem(new ui\PageTop(_('Contract')));

$contract = new \AbraFlexi\FakturaVydana($oPage->getRequestValue('id'));

$contractForm = new ui\ContractForm($contract);

$oPage->container->addItem($contractForm);

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
