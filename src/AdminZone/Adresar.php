<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone;

/**
 * Description of Adresar
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Adresar extends \AbraFlexi\Adresar
{
    /**
     *
     * @param type $init
     * @param type $options
     */
    public function __construct($init = null, $options = array())
    {
        parent::__construct($init, $options);
    }

    public function takeData(array $data): int
    {
        if ($data) {
            foreach ($data as $column => $value) {
                switch ($column) {
                    case 'stat':
                        $data[$column] = \AbraFlexi\Functions::code($value);
                        break;

                    default:
                        break;
                }
            }
        }

        return parent::takeData($data);
    }

    function sltvRegData()
    {
        return array(
            'partnerid' => $this->getRecordCode(),
            'email' => $this->getDataValue('email'),
            'fullName' => $this->getDataValue('nazev'),
            'address' => $this->getDataValue('ulice'),
            'city' => $this->getDataValue('mesto'),
            'postcode' => $this->getDataValue('psc')
        );
    }
}
