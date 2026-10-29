<?php

namespace AdminZone;

/**
 * AdminZone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2017 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$oPage->addItem(new ui\PageTop(_('VoIP Products')));

$servicer = new \IPEXB2B\Services();

$servicer->loadFromIPEX(['customerId' => $oUser->getAdresar()->getExternalId('ipex')]);

if ($servicer->getDataCount()) {
    $oPage->container->addItem(new \Ease\Html\H2Tag(_('Your services')));

    $caller = new \IPEXB2B\Calls();

    foreach ($servicer->getData() as $serviceData) {
        $oPage->container->addItem(new \Ease\Html\H3Tag($serviceData['number']));
        $oPage->container->addItem(($serviceData['status'] == 'active') ? _('Active') : _('Inactive'));
        $oPage->container->addItem(' ' . $serviceData['paymentType'] . ' ');
        $oPage->container->addItem(' ' . $serviceData['numberType'] . ' ');

        $startDate = new \DateTime();
        $startDate->modify('-1 month');
        $calls = $caller->getCallsForNumber(
            $startDate,
            $serviceData['number']
        );
        if ($calls) {
            $oPage->container->addItem(new ui\CallsListing(
                $calls,
                ['class' => 'table']
            ));
        }

        if ($serviceData['paymentType'] == 'prepaid') {
            $oPage->container->addItem(new \Ease\TWB\LinkButton(
                'orderform.php?service=VoIPcredit',
                _('Order Credit'),
                'success'
            ));
        }

        $oPage->container->addItem(new \Ease\TWB\LinkButton(
            'mycallspdf.php',
            _('Calls PDF'),
            'info'
        ));
    }
} else {
    $oUser->addStatusMessage('IPEX Account is missing for you');
}



$oPage->addItem(new ui\PageBottom());

$oPage->draw();
