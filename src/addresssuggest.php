<?php

declare(strict_types=1);

namespace AdminZone;

/**
 * AdminZone - AbraFlexi address suggestions (Select2 ajax source).
 *
 * @copyright  2018-2026 Spoje.Net
 */

require_once 'includes/Init.php';

header('Content-Type: application/json');

if (!$oUser->isLogged()) {
    http_response_code(403);
    echo json_encode(['results' => []]);

    exit;
}

$term = trim((string) $oPage->getRequestValue('term'));
$results = [];

if (mb_strlen($term) >= 2) {
    $safe = str_replace("'", "''", $term);
    $adresar = new \AbraFlexi\Adresar();

    foreach ($adresar->getColumnsFromAbraFlexi(['kod', 'nazev'], ["kod like similar '".$safe."' or nazev like similar '".$safe."'", 'limit' => 20, 'order' => 'nazev']) ?: [] as $address) {
        $results[] = ['id' => $address['kod'], 'text' => $address['kod'].' — '.$address['nazev']];
    }
}

echo json_encode(['results' => $results]);
