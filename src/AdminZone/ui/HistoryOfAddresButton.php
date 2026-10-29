<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of HistoryOfAddresButton
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class HistoryOfAddresButton extends \Ease\TWB\LinkButton
{
    /**
     * Link Button to Cutomer's invoices
     *
     * @param \AbraFlexi\Adresar $address
     */
    public function __construct($address)
    {
        parent::__construct(
            'customerhistory.php?address=' . $address,
            _('Customer History'),
            'info'
        );
    }
}
