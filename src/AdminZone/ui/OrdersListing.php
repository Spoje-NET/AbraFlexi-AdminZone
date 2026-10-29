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
class OrdersListing extends \Ease\TWB\Panel
{

    /**
     * Orders Listing
     *
     * @param \AbraFlexi\RO $fetcher     Proforma/Invoice/Order
     * @param array                  $conditions  Conditions
     * @param string                 $caption     Panel caption
     */
    public function __construct($fetcher, $conditions = [], $caption = 'List')
    {
        $fmt = new \NumberFormatter('cs_CZ', \NumberFormatter::CURRENCY);
        $documents = $fetcher->getColumnsFromAbraFlexi('full', $conditions);
        $celkem = 0;
        $penale = 0;
        $unsettled = 0;
        $evidence = $fetcher->getEvidence();
        parent::__construct(
                $caption . ' - ' . \AbraFlexi\EvidenceList::$evidences[$evidence]['evidenceName'],
                'info'
        );
        if (!empty($documents)) {
            foreach ($documents as $orderData) {
                $this->addItem(new OrderListingItem($orderData, $evidence));
                if ($orderData['storno'] === false) {
                    if (array_key_exists('datSplat', $orderData)) {
                        $ods = \AbraFlexi\FakturaVydana::overdueDays($orderData['datSplat']);
                    } else {
                        $ods = 0;
                    }

                    if ((array_key_exists('datuhr', $orderData) === false || $orderData['datUhr']->isNull) && ($ods > 0 )) {
                        $penale += (($orderData['sumCelkem'] / 100) * 0.05) * $ods;
                    }

                    $celkem += $orderData['sumCelkem'];
                    if (array_key_exists('juhSum', $orderData)) {
                        $unsettled += ($orderData['sumCelkem'] - $orderData['juhSum']);
                    }
                }
            }
            $this->addToFooter = new \Ease\TWB\Row(null, ['style' => 'background-color: lightgray;']);
            $this->addToFooter->addColumn('2', count($documents));
            $this->addToFooter->addColumn('2', $fmt->formatCurrency($celkem, 'Kč'));
            $this->addToFooter->addColumn('2', _('Unsettled') . ': ' . $fmt->formatCurrency($unsettled, "Kč"));

            $this->addToFooter->addColumn('2', '(penale  ' . $fmt->formatCurrency($penale, "Kč") . ')');
        }
    }
}
