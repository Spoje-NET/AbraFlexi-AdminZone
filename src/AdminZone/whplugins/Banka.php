<?php

namespace AdminZone\whplugins;

/**
 * Description of Adresar
 *
 * @author vitex
 */
class Banka extends \AdminZone\WebHookHandler
{
    public ?string $evidence = 'banka';

    
    /**
     * Match Payment when create
     */
    public function create()
    {
        if ($this->getDataValue('typPohybuK') == 'typPohybu.prijem') {
            if ($this->getDataValue('sparovano') === false) {
                $steamer = new \AdminZone\ParovacFaktur(\Ease\Shared::singleton()->configuration);
                $steamer->matchPayment($this->getData());
            }
        }
    }
}
