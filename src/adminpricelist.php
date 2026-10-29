<?php

namespace AdminZone;

/**
 * AdminZone - Přehled ceníku
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

require_once 'includes/Init.php';
$oPage->onlyForUser();

$oPage->addItem(new ui\PageTop(_('Pricelist Admin')));

$oPage->container->addItem(new ui\AdminPricelist(\Ease\Shared::cfg('PROD_CAT'), ['ESHOP', 'AKTIVNI']));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
