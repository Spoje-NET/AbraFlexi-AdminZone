<?php

/**
 * Description of SysAbraFlexiStatus.
 *
 * @author vitex
 */

namespace AdminZone;

class AbraFlexiStatus extends \AbraFlexi\Company
{
    use \Ease\Glue;

    /**
     * AbraFlexi Status
     * @var array
     */
    public $info = null;

    /**
     * Try to connect to AbraFlexi
     */
    public function __construct()
    {
        parent::__construct();
        $this->info = $this->getFlexiData();
    }

    /**
     * Draw result
     */
    public function draw()
    {
        if (count($this->info)) {
            $infos = \Ease\Functions::reindexArrayBy($this->info, 'dbNazev');
            if (isset($infos[constant('ABRAFLEXI_COMPANY')])) {
                $return = new \Ease\TWB\LinkButton(
                    constant('ABRAFLEXI_URL') . '/c/' . constant('ABRAFLEXI_COMPANY'),
                    $infos[constant('ABRAFLEXI_COMPANY')]['nazev'],
                    'success'
                );
            } else {
                $return = new \Ease\TWB\LinkButton(
                    constant('ABRAFLEXI_URL'),
                    _('Zvolená firma neexistuje'),
                    'warning'
                );
            }
        } else {
            $return = new \Ease\TWB\LinkButton(
                constant('ABRAFLEXI_URL'),
                _('Chyba komunikace'),
                'danger'
            );
        }
        $return->draw();
    }
}
