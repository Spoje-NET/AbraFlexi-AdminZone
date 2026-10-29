<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui\dashboard;

/**
 * Description of QuartalBlock
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class QuartalBlock extends Block
{
    public function __construct(
        $heading = null,
        $q1,
        $q2,
        $q3,
        $q4,
        $type = 'default',
        $footer = null
    ) {
        parent::__construct(
            $heading,
            'info',
            null,
            Block::arrayToPricesTable(self::getYearSum([$q1, $q2, $q3, $q4]))
        );

        $q12 = new \Ease\TWB\Row();
        $q12->addColumn(6, new Block(_('Q1'), 'info', $q1));
        $q12->addColumn(6, new Block(_('Q2'), 'info', $q2));
        $this->addItem($q12);

        $q34 = new \Ease\TWB\Row();
        $q34->addColumn(6, new Block(_('Q3'), 'info', $q3));
        $q34->addColumn(6, new Block(_('Q4'), 'info', $q4));
        $this->addItem($q34);
    }

    /**
     *
     * @param array $quartalsData
     *
     * @return array year total for each currency
     */
    public static function getYearSum($quartalsData)
    {
        $yearSum = [];
        foreach ($quartalsData as $quartalData => $prices) {
            foreach ($prices as $currency => $value) {
                if (array_key_exists($currency, $yearSum)) {
                    $yearSum[$currency] += $value;
                } else {
                    $yearSum[$currency] = $value;
                }
            }
        }
        return $yearSum;
    }
}
