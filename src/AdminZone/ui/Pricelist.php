<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of Pricelist
 *
 * @author vitex
 */
class Pricelist extends \Ease\TWB\Panel
{
    /**
     *
     * @var \AbraFlexi\Cenik
     */
    public $pricer = null;

    /**
     *
     * @param int   $category  number of category
     * @param array $labels
     */
    public function __construct($category, $labels = [])
    {
        $skiplist = [];
        $order = new \AdminZone\OrderItem();
        $orderable = [];
        $user = \Ease\Shared::user();
        foreach ($order->plugins as $pluginName => $plugin) {
            if (isset($plugin->reqExtPrefix)) {
                if ($user->getUserID()) {
                    $prefixID = $user->getAdresar()->getExternalID($plugin->reqExtPrefix);
                    if (empty($prefixID)) {
                        $skiplist[$plugin->productCode] = $pluginName;
                    }
                } else {
                    $skiplist[$plugin->productCode] = $pluginName;
                }
            }


            $orderable[$plugin->productCode] = $pluginName;
        }

        if (count($labels)) {
            foreach ($labels as $lid => $label) {
                $labels[$lid] = 'stitky=\'code:' . $label . '\'';
            }
            $labelCond = implode(' and ', $labels);
            $category .= ' and ' . $labelCond;
        }

        $this->pricer = new \AbraFlexi\Cenik();
        $this->pricer->defaultUrlParams['detail'] = 'custom:kod,nazev,cenaZaklBezDph,cenaZaklVcDph';
        $items = $this->pricer->getColumnsFromAbraFlexi(
            ['id', 'kod', 'nazev', 'cenaZaklBezDph', 'cenaZaklVcDph'],
            ['(in subtree ' . $category . ')']
        );

        parent::__construct(
            _('Pricelist'),
            'info',
            null,
            new \Ease\TWB\Label(
                'info',
                sprintf(_('%s items found'), empty($items) ? 0 : count($items))
            )
        );

        if (!empty($items)) {
            $headerRow = new \Ease\TWB\Row(null);
            $headerRow->addColumn(1);
            $headerRow->addColumn(2, new \Ease\TWB\Label('info', _('Item name')));
            $headerRow->addColumn(
                2,
                new \Ease\TWB\Label('info', _('Price without VAT'))
            );
            $headerRow->addColumn(
                2,
                new \Ease\TWB\Label('info', _('Price with VAT'))
            );
            $headerRow->addColumn(2, new \Ease\TWB\Label('info', _('Actions')));
            $this->addItem($headerRow);
            $this->addItem('<p><hr/></p>');
            foreach ($items as $item) {
                if (array_key_exists($item['kod'], $skiplist)) {
                    continue;
                }
                if (array_key_exists($item['kod'], $orderable)) {
                    $item['service'] = $orderable[$item['kod']];
                } else {
                    $item['service'] = 'Pricelist&kod=' . $item['kod'];
                }

                $this->addItem($this->pricelistRow($item));
            }
        }
    }

    public function pricelistRow($pricelistItemData)
    {
        $row = new \Ease\TWB\Row();
        $this->pricer->setMyKey($pricelistItemData['id']);
        $row->addColumn(
            1,
            new ProductImageThumbnail(
                $this->pricer,
                ['class' => 'thumbnail', 'style' => 'height: 40px']
            )
        );
        $row->addColumn(2, $pricelistItemData['nazev']);
        $row->addColumn(2, $pricelistItemData['cenaZaklBezDph']);
        $row->addColumn(2, round($pricelistItemData['cenaZaklVcDph']));
        if (array_key_exists('service', $pricelistItemData)) {
            $row->addColumn(
                2,
                new \Ease\TWB\LinkButton(
                    'orderform.php?service=' . $pricelistItemData['service'],
                    _('Add to cart'),
                    'success', ['id' => 'addToCartBtn']
                )
            );
        }

        return $row;
    }
}
