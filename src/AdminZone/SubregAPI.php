<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone;

/**
 * Description of SubregClient
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class SubregAPI extends \Subreg\Client
{
    /**
     *
     * @param type $config
     */
    public function __construct($config = [])
    {
        $systemConfig = [
            "location" => \Ease\Shared::cfg('SUBREG_LOCATION'),
            "uri" => \Ease\Shared::cfg('SUBREG_URI'),
            "login" => \Ease\Shared::cfg('SUBREG_LOGIN'),
            "password" => \Ease\Shared::cfg('SUBREG_PASSWORD')
        ];

        parent::__construct(array_merge($systemConfig, $config));
    }
}
