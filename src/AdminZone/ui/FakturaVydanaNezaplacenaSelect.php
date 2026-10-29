<?php

/**
 * AdminZone - Stránka Webu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

namespace AdminZone\ui;

class FakturaVydanaNezaplacenaSelect extends \Ease\Html\DdTag
{
    public function finalize(): void
    {
        \AdminZone\ui\WebPage::singleton()->includeJavaScript('js/vyberfaktury.js');
        $this->setTagID('FakturaVydanaNezaplacena');
    }
}
