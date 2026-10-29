<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of ProductImage
 *
 * @author vitex
 */
class ProductImage extends \Ease\Html\ImgTag
{
    public function __construct($product, string $type = 'image', array $tagProperties = [])
    {
        parent::__construct(
            'prdimg.php?type=' . $type . '&id=' . $product->getMyKey(),
            (string)$product->getDataValue('nazev'),
            $tagProperties
        );
    }
}
