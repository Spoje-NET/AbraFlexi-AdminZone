<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of LabelGroup
 *
 * @author vitex
 */
class LabelGroup extends \Ease\Html\Span
{
    /**
     * AbraFlexi
     *
     * @param \AbraFlexi\RO $abraflexi
     */
    public function __construct($abraflexi)
    {
        $labels = [];
        $stitky = $abraflexi->getDataValue('stitky');
        parent::__construct();
        if (!empty($stitky)) {
            $stitkyArr = explode(',', $stitky);
            foreach ($stitkyArr as $stitek) {
                $this->addItem(new \Ease\TWB\Label(
                    'info',
                    new \Ease\Html\ATag(
                        'labels.php?label=' . trim($stitek) . '&evidence=' . $abraflexi
                        ->getEvidence(),
                        trim($stitek)
                    )
                ));
            }
        }
    }
}
