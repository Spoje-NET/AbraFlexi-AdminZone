<?php

namespace AdminZone;

define('EASE_LOGGER', 'console|syslog|email');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$invoicer = new \AbraFlexi\FakturaVydana(null, ['evidence' => 'faktura-vydana-polozka']);
$items = $invoicer->getColumnsFromAbraFlexi(['cenik', 'nazev']);
$products = [];
foreach ($items as $item) {
    $itemIdent = !empty($item['cenik']) ? \AbraFlexi\Functions::uncode($item['cenik']) : $item['nazev'];
    if (array_key_exists($itemIdent, $products)) {
        $products[$itemIdent]++;
    } else {
        $products[$itemIdent] = 1;
    }
}

arsort($products);

print_r($products);
