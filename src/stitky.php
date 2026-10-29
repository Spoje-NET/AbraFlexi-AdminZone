<?php

namespace AdminZone;

/**
 * AdminZone - Stránka firmy/adresy.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForUser();

$oPage->container->addItem(new ui\LabelEditor(
    new \AbraFlexi\Adresar('code:VITEX'),
    ['id' => 'stitkovac']
));

$oPage->draw();
