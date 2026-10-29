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
class IntegrationOverview extends \Ease\TWB\Panel
{
    /**
     * Show Customer membership in 3rd party systems
     *
     * @param \AbraFlexi\Adresar $address Customer
     */
    public function __construct($address)
    {
        parent::__construct(
            _('Integrations'),
            'info',
            null,
            new \Ease\TWB\LinkButton(
                'adr2subreg.php?adrid=' . $address->getMyKey(),
                _('Register Contact On Subreg'),
                'warning'
            )
        );

        $subregID = $address->getExternalID('subreg');
        $ipexID = $address->getExternalID('ipex');
        $sledovaniTVID = $address->getExternalID('sltv');

        if (is_null($subregID)) {
            $this->addItem(new \Ease\TWB\LinkButton(
                'extids.php?service=subreg',
                'SubReg',
                'default'
            ));
        } else {
            $this->addItem(new \Ease\TWB\LinkButton(
                'https://subreg.cz/cz/users/search/?iduser=' . $subregID . '&submit=Odeslat',
                'SubReg',
                'success'
            ));
        }

        if (is_null($ipexID)) {
            $this->addItem(new \Ease\TWB\LinkButton(
                'extids.php?service=ipex',
                'Ipex',
                'default'
            ));
        } else {
            $this->addItem(new \Ease\TWB\LinkButton(
                'https://b2b.ipex.cz/customer/dashboard/' . $ipexID,
                'Ipex',
                'success'
            ));
        }

        if (is_null($sledovaniTVID)) {
            $this->addItem(new \Ease\TWB\LinkButton(
                'sltvregister.php?id=' . $address,
                'SledovaniTV',
                'default'
            ));
        } else {
            $this->addItem(new \Ease\TWB\LinkButton(
                'https://sledovanitv.cz/partner/user/edit/' . $sledovaniTVID,
                'SledovaniTV',
                'success'
            ));
        }
    }
}
