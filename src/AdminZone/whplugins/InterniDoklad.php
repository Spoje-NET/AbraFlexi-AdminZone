<?php

namespace AdminZone\whplugins;

/**
 * Description of FakturaPrijata
 *
 * @author vitex
 */
class InterniDoklad extends \AdminZone\WebHookHandler
{
    /**
     * Order Data
     * @var array
     */
    public $orderData = null;

    /**
     *
     * @var string
     */
    public ?string $evidence = 'interni-doklad';


    /**
     * Invoice was inserted. What to do now ?
     *
     * @return boolean Change was processed. Ok remeber it
     */
    public function create()
    {
        switch (\AbraFlexi\Functions::uncode($this->getDataValue('typDokl'))) {
            case 'ZBYTEK NÁKLAD':
            case 'ZBYTEK VÝNOS':
                $this->addStatusMessage($this->getRecordIdent(), 'warning');
                break;

            default:
                break;
        }

        return true;
    }
}
