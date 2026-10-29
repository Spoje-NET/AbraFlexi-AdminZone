<?php

namespace AdminZone;

/**
 * AdminZone - zjednodušený vyhledávač
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

require_once 'includes/Init.php';

$searcher = new \AbraFlexi\Cenik();

$searchColumn = $oPage->getRequestValue('column');
$searchTerm = $oPage->getRequestValue('q');

$found = $searcher->getColumnsFromAbraFlexi(['id', 'kod', 'nazev'], ["$searchColumn like similar '$searchTerm'"]);

header('Content-Type: application/json');
echo json_encode($found);
