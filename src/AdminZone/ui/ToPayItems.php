<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of OrdersListing
 *
 * @author vitex
 */
class ToPayItems extends MainPageMenu
{
    /**
     * Orders Listing
     *
     * @var $customer Customer
     */
    public function __construct($customer)
    {
        parent::__construct();
        $debts = $customer->getCustomerDebts();
        if (!empty($debts)) {
            $this->row->addItem(new \Ease\Html\H1Tag(_('Orders to pay')));
            foreach ($debts as $invoiceInfo) {
                $invoiceId = $invoiceInfo['id'];
                $customer->getInvoicer()->setMyKey($invoiceId);
                $this->addMenuItem(
                    $customer->getInvoicer()->getQrCodeBase64(200),
                    $invoiceInfo['kod'] . ' <strong>' . $invoiceInfo['sumCelkem'] . '</strong> ' . \AbraFlexi\Functions::uncode($invoiceInfo['mena']),
                    'document.php?id=' . $invoiceId . '&embed=true&evidence=' . $customer->getInvoicer()->getEvidence()
                );
            }
        }
    }
}
