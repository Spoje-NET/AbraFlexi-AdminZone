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

$address = new \AbraFlexi\Adresar('code:VITEX');

$oPage->container->addItem(new ui\LabelSwitches($address));

$oPage->draw();
