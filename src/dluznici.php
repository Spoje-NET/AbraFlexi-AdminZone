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

$oPage->addItem(new ui\PageTop(_('Debtors')));

$fetcher = new \AbraFlexi\FakturaVydana();

$oPage->container->addItem(new ui\OrdersListing(
    $fetcher,
    ['datSplat lte \'' . new \AbraFlexi\Date() . '\' AND (stavUhrK is null OR stavUhrK eq \'stavUhr.castUhr\') AND storno eq false', 'limit' => 0],
    _('Debtor')
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
