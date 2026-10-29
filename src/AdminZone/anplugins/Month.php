<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\anplugins;

/**
 * Description of PreviousMonth
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Month
{
    /**
     * 1-12
     * @var int
     */
    public $month;

    /**
     * 2015+
     * @var int
     */
    public $year;

    /**
     *
     * @var \AbraFlexi\RO
     */
    private $engine;

    /**
     *
     * @param int                    $month
     * @param int                    $year
     * @param \AbraFlexi\RW $engine
     */
    public function __construct($month, $year, $engine)
    {
        $this->year = $year;
        $this->month = $month;
        $this->engine = $engine;
    }

    public function run($conds = [])
    {
        return $this->engine->getSumFromAbraFlexi($conds);
    }
}
