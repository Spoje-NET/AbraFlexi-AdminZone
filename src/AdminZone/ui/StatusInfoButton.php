<?php

/**
 * AbraFlexi-Bricks
 *
 * @author vitex
 */

namespace AdminZone\ui;

/**
 *
 */
class StatusInfoButton extends \AbraFlexi\Company
{
    /**
     * AbraFlexi Status
     * @var array
     */
    public $info = [];

    /**
     * Text To show on button
     * @var string
     */
    public $caption = null;

    /**
     * Try to connect to AbraFlexi
     */
    public function __construct($caption = null)
    {
        $this->caption = $caption;
        parent::__construct();
        $infoRaw = $this->getFlexiData();
        if (count($infoRaw)) {
            $this->info = $this->reindexArrayBy($infoRaw, 'dbNazev');
        }
    }

    /**
     * Draw result
     */
    public function draw()
    {
        $myCompany = $this->getCompany();
        if (array_key_exists($myCompany, $this->info)) {
            $return = new \Ease\TWB\LinkButton(
                $this->url . '/c/' . $myCompany,
                is_null($this->caption) ? $this->info[$myCompany]['nazev'] : $this->caption,
                'success btn-lg'
            );
        } else {
            $return = new \Ease\TWB\LinkButton(
                $this->url . '/c/' . $myCompany,
                is_null($this->caption) ? _('Chyba komunikace') : $this->caption,
                'danger btn-lg'
            );
        }
        $return->draw();
    }
}
