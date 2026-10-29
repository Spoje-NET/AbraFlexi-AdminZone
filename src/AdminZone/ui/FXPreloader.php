<?php

/**
 * AdminZone - Formulář příjm/výdaje pokladny.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

namespace AdminZone\ui;

class FXPreloader extends \Ease\Html\DivTag
{
    public function __construct($id = null)
    {
        parent::__construct(
            null,
            ['class' => 'loader', 'data-initialize' => 'loader', 'id' => $id]
        );
    }

    public function finalize(): void
    {
        \AdminZone\ui\WebPage::singleton()->includeCss(
            'twitter-bootstrap/css/fuelux.css',
            true
        );
        \AdminZone\ui\WebPage::singleton()->includeJavascript('/javascript/twitter-bootstrap/fuelux.js');
        \AdminZone\ui\WebPage::singleton()->addJavascript("$('#" . $this->getTagID() . "').loader();");
        \AdminZone\ui\WebPage::singleton()->addCSS('
#' . $this->getTagID() . '{
    position: absolute;
    top: 50%;
    left: 50%;
    margin-top: -50px;
    margin-left: -50px;
    width: 100px;
    height: 100px;
    visibility: hidden;
}​
            ');
    }
}
