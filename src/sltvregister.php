<?php

namespace AdminZone;

/**
 * VendorZone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2019 VitexSoftware v.s.cz
 */

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$aid = $oPage->getRequestValue('id');

$oPage->addItem(new ui\PageTop(_('SledovaniTV Registrator')));

$address = new Adresar($aid);

$sledovaniTVID = $address->getExternalID('sltv');

if (empty($sledovaniTVID)) {
    $registrator = new SledovaniTV();

    $response = $registrator->identifyUser($address->getRecordCode(), $address->getDataValue('email'), $address->getDataValue('email'), $address->getDataValue('nazev'), $address->getDataValue('ulice'), $address->getDataValue('mesto'), preg_replace('/\s+/', '', $address->getDataValue('psc')), null, null, $_SERVER['REMOTE_ADDR']);

    $oPage->addItem(new \Ease\TWB\LinkButton(
        $response['url'],
        $response['token'],
        $response['status'] == 1 ? 'success' : 'warning'
    ));
} else {
    $oPage->addStatusMessage(sprintf(
        _('Already registered as %s'),
        $sledovaniTVID
    ), 'warning');
    $oPage->container->addItem(sprintf(
        _('Already registered as %s'),
        $sledovaniTVID
    ), 'warning');
}

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
