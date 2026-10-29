<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of LinkToAbraFlexiButton
 *
 * @author vitex
 */
class LinkToAbraFlexiButton extends \Ease\TWB\LinkButton
{
    /**
     *
     * @param \AbraFlexi\RO $abraflexi
     */
    public function __construct($abraflexi, $properties = [])
    {
        parent::__construct(
            $abraflexi->getAbraFlexiURL(),
            [new \Ease\Html\ImgTag(
                'images/abraflexi.png',
                _('Open in AbraFlexi'),
                $properties
            ), ' ', \AbraFlexi\EvidenceList::$name[$abraflexi->getEvidence()] . ' <strong>' . str_replace(
                'code:',
                '',
                $abraflexi
            ) . '</strong>'],
            'warning'
        );
    }
}
