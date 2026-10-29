<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of LabelBox
 *
 * @author vitex
 */
class LabelBox extends Pillbox
{
    public function __construct($abraflexi, $properties = [])
    {
        parent::__construct(
            \AbraFlexi\Stitek::getLabels($abraflexi),
            \AbraFlexi\Stitek::getAvailbleLabels($abraflexi),
            $properties
        );
    }
}
