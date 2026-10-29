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

$fetcher = new \AbraFlexi\FakturaVydana();

$oPage->container->addItem(new ui\OrdersListing(
    $fetcher,
    ['stitky' => 'code:API'],
    _('API Actions')
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
