<?php

declare(strict_types=1);

namespace AdminZone;

/**
 * AdminZone - Stránka firmy/adresy.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2026 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$address_id = $oPage->getRequestValue('id');

if (!strstr((string) $address_id, 'code:')) {
    $address_id = (int) $address_id;
}

$address = new Adresar(
    $address_id,
    ['defaultUrlParams' => ['relations' => 'kontakt']],
);

FormHandler::handlePost($oPage, $address);

$oPage->addToHeader(new ui\PageTop(_('Address') . ': ' . $address->getDataValue('nazev')));

$oPage->container->addItem(new \Ease\Html\DivTag([
    new \Ease\Html\H1Tag($address->getDataValue('kod') . ' - ' . $address->getDataValue('nazev'), ['class' => 'h3']),
    new ui\LinkToAbraFlexiButton($address),
], ['class' => 'd-flex justify-content-between align-items-center mt-4 mb-3']));

$row = new \Ease\Html\DivTag(null, ['class' => 'row g-3']);
$row->addItem(new \Ease\Html\DivTag(new \Ease\TWB5\Panel(_('Address'), 'info', new ui\AddressForm($address, 'adresar.php')), ['class' => 'col-lg-6']));
$row->addItem(new \Ease\Html\DivTag(new ui\IntegrationOverview($address), ['class' => 'col-lg-6']));
$oPage->container->addItem($row);

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
