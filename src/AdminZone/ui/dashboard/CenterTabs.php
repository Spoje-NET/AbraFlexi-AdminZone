<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui\dashboard;

/**
 * Description of Dashboard
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class CenterTabs extends \Ease\TWB\Container
{
    /**
     *
     * @var \Ease\Html\DivTag
     */
    public $income;

    /**
     *
     * @var \Ease\Html\DivTag
     */
    public $outcome;

    public function __construct($content = null)
    {
        parent::__construct($content);

        $centerer = new \AbraFlexi\RO(null, ['evidence' => 'stredisko']);
        $centers = $centerer->getColumnsFromAbraFlexi(['nazev', 'kod']);

        $centerTabs = new \Ease\TWB\Tabs('centers');
        $centerTabs->addTab(_('All Centers'), new Center());

        foreach ($centers as $center) {
            $centerTabs->addTab($center['nazev'], new Center($center['kod']));
        }

        $this->addItem($centerTabs);
    }
}
