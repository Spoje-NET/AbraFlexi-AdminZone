<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of IntegrationOverview
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class IntegrationOverview extends \Ease\TWB5\Panel
{
    /**
     * Show Customer membership in 3rd party systems
     *
     * @param \AbraFlexi\Adresar $address Customer
     */
    public function __construct($address)
    {
        parent::__construct(_('Integrations'), 'info');

        $addressCode = \AbraFlexi\Functions::uncode((string) $address->getRecordCode());

        foreach (\AdminZone\ExtIdRegistry::all() as $key => $service) {
            $serviceID = $address->getExternalID($key);

            if (empty($serviceID)) {
                $this->addItem(new \Ease\TWB5\LinkButton(
                    $service->pairingUrl($addressCode),
                    $service->label(),
                    'default'
                ));

                $register = $service->registerLink((string) $address->getMyKey());

                if ($register !== null) {
                    $this->addItem(new \Ease\TWB5\LinkButton($register[0], $register[1], 'warning'));
                }
            } else {
                $this->addItem(new \Ease\TWB5\LinkButton(
                    $service->accountUrl((string) $serviceID) ?? $service->pairingUrl($addressCode),
                    $service->label(),
                    'success'
                ));
            }
        }
    }
}
