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
class Center extends \Ease\TWB\Row
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

    /**
     * Show Dashboard items for one center if specified
     *
     * @param string $center center Code
     */
    public function __construct($center = null)
    {
        parent::__construct();

        $this->income = $this->addColumn(
            6,
            new \Ease\Html\H3Tag(
                _('Income'),
                ['style' => ' text-align: center;']
            )
        );
        $this->outcome = $this->addColumn(
            6,
            new \Ease\Html\H3Tag(
                _('Outcome'),
                ['style' => ' text-align: center;']
            )
        );

        $this->income->addItem(new Block(_('Current Month'), 'info', ['CZK' => 50000, 'EU' => 500]));
        $this->income->addItem(new Block(_('Previous Month'), 'warning', ['CZK' => 50000, 'EU' => 500]));
        $this->income->addItem(new QuartalBlock(_('This Year') . ' ' . date('Y'), ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500]));
        $this->income->addItem(new QuartalBlock(_('Previous Year') . ' ' . (date('Y') - 1), ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500]));

        $this->outcome->addItem(new Block(_('Current Month'), 'default', ['CZK' => 50000, 'EU' => 500]));
        $this->outcome->addItem(new Block(_('Previous Month'), 'danger', ['CZK' => 50000, 'EU' => 500]));
        $this->outcome->addItem(new QuartalBlock(_('This Year') . ' ' . date('Y'), ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500]));
        $this->outcome->addItem(new QuartalBlock(_('Previous Year') . ' ' . (date('Y') - 1), ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500], ['CZK' => 50000, 'EU' => 500]));
    }
}
