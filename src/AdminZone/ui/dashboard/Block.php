<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui\dashboard;

/**
 * Description of Block
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Block extends \Ease\TWB\Panel
{
    public function __construct(
        $heading = null,
        $type = 'info',
        $body = null,
        $footer = null
    ) {
        if (is_array($body)) {
            $body = self::arrayToPricesTable($body);
        }
        parent::__construct($heading, $type, $body, $footer);
    }

    /**
     *
     * @param array $prices
     *
     */
    public static function arrayToPricesTable($prices)
    {
        $pageItemTable = new \Ease\Html\TableTag(null, ['class' => 'bigCurrency table table-condensed']);
        $pageItems = [];
        foreach ($prices as $fieldName => $fieldValue) {
            $pageItems[$fieldName] = self::formatCurrency($fieldValue) . ' ' . $fieldName;
        }
        $pageItemTable->addRowColumns($pageItems, ['style' => 'padding: 0px; margin 0px;']);
        return $pageItemTable;
    }

    /**
     * Format Czech Currency
     *
     * @param float $price
     *
     * @return string
     */
    public static function formatCurrency($price)
    {
    }
}
