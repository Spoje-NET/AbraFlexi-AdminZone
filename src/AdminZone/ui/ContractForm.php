<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of ContractForm
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class ContractForm extends AbraFlexiForm
{
    public function __construct(
        $engine,
        $formAction = null,
        $formMethod = 'post',
        $formContents = null,
        $tagProperties = null
    ) {
        parent::__construct(
            $engine,
            $formAction,
            $formMethod,
            $formContents,
            $tagProperties
        );

        $this->addInput('kod');
        $this->addInput('lastUpdate');
        $this->addInput('nazev');
        $this->addInput('popis');
        $this->addInput('poznam');
        $this->addInput('smlouvaOd');
        $this->addInput('smlouvaDo');
    }
}
