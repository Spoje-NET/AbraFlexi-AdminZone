<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of CallsListing
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class CallsListing extends \Ease\Html\TableTag
{
    /**
     * Calls Listing table
     *
     * @param array $calls
     * @param array $properties
     */
    public function __construct($calls = null, $properties = [])
    {
        parent::__construct(null, $properties);
        if (!is_null($calls)) {
            $this->populate($calls);
        }
    }

    /**
     *
     * @param array $calls
     */
    public function populate($calls)
    {
        //cislo_int
        //cislo_ext
        //destinace
        //datetime
        //odchozi
        //placeny
        //stav
        //delka
        //jednotka
        //cena
        //sazba_dph
        //pocet
        //cdr_flags
        //zakaznik
        //zakaznik_id
        $this->addRowHeaderColumns([
            _('Calling number'),
            _('Called number'),
            _('Destination'),
            _('When'),
            _('Direction'),
            _('Payed'),
            _('State'),
            _('Duration'),
//            _('Unit'),
            _('Price'),
            _('Vat value')
        ]);
        foreach ($calls as $callData) {
            $this->addRowColumns([
                $callData['cislo_int'],
                $callData['cislo_ext'],
                $callData['destinace'],
                $callData['datetime'],
                ($callData['odchozi'] == 'Y') ? _('Outcomming') : _('Incoming'),
                ($callData['placeny'] == 'Y') ? _('Yes') : _('No'),
                ($callData['stav'] == 'Zodpovězeno') ? _('Answered') : _('Not Answered'),
                $this->secsToStr(intval($callData['delka'])),
//                $callData['jednotka'],
                $callData['cena'],
                $callData['sazba_dph']
            ]);
        }
    }

    /**
     * Seconds to human readable
     *
     * @param long $duration
     *
     * @return string
     */
    function secsToStr($duration)
    {
        $periods = array(
            _('day') => 86400,
            _('hour') => 3600,
            _('minute') => 60,
            _('second') => 1
        );

        $parts = array();

        foreach ($periods as $name => $dur) {
            $div = floor($duration / $dur);

            if ($div == 0) {
                continue;
            } elseif ($div == 1) {
                $parts[] = $div . " " . $name;
            } else {
                $parts[] = $div . " " . $name . "s";
            }
            $duration %= $dur;
        }

        $last = array_pop($parts);

        if (empty($parts)) {
            return $last;
        } else {
            return join(', ', $parts) . " " . _('And') . " " . $last;
        }
    }
}
