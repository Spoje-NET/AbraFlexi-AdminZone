<?php

namespace AdminZone;

/**
 * AdminZone - Přehled ceníku
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2024 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->addItem(new ui\PageTop(_('Pricelist')));

$oPage->container->addItem(new ui\Pricelist(\Ease\Shared::cfg('PROD_CAT',10), ['ESHOP', 'AKTIVNI']));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
