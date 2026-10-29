<?php

namespace AdminZone;

/**
 * AdminZone - Proxy pro lokální JS.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

require_once 'includes/Init.php';

$engine = new \AbraFlexi\RO();
$engine->setAgenda($oPage->getRequestValue('evidence'));
echo json_encode($engine->getFlexiData('', $oPage->getRequestValue('request')));
