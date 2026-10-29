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

$oPage->addItem(new ui\PageTop(_('Orders to do')));

$fetcher = new \AbraFlexi\RO(null, ['evidence' => 'objednavka-prijata']);

$oPage->container->addItem(new ui\OrdersListing(
    $fetcher,
    ["(stavUzivK ne 'stavDoklObch.hotovo') and (stavUzivK ne 'stavDoklObch.storno') ", 'limit' => 0],
    _('My Orders')
));

//(stavUzivK eq 'stavDoklObch.schvaleno') and  and

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
