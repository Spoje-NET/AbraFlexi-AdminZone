<?php

namespace AdminZone;

/**
 * VendorZone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2021 VitexSoftware v.s.cz
 */

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$class = $oPage->getRequestValue('class');
$filter = $oPage->getRequestValue('filter');

/**
 * @var \AbraFlexi\RW Data Source
 */
$engine = new $class();

if (is_array($filter)) {
    $engine->defaultUrlParams = $filter;
}

unset($_REQUEST['class']);
unset($_REQUEST['_']);

$dataRaw = $engine->getColumnsFromAbraFlexi('*', $filter);

header('Content-Type: application/json');
echo json_encode(['data' => $dataRaw, 'recordsTotal' => count($dataRaw)]);
