<?php

/*
 * How to Fix Trimmed Banking Labels in AbraFlexi
 * using mhujer/fio-api-php and spoje.net/abraflexi
 */

define('EASE_LOGGER', 'console|syslog|email');
define('EASE_APPNAME', 'GetAllVCFs');
$destdir = '/tmp/vcf/';

if (!file_exists($destdir)) {
    mkdir($destdir);
}

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$adresy = new \AbraFlexi\Adresar();

$idsRaw = $adresy->getColumnsFromAbraFlexi(['id']);

$count = 0;
foreach ($idsRaw as $idRaw) {
    $adresy->setMyKey($idRaw['id']);
    $result = $adresy->downloadInFormat('vcf', $destdir);

    system("iconv -f UTF-8 -t CP1250 $result -o  " . str_replace(
        'adresar',
        'win',
        $result
    ));
    unlink($result);

    $adresy->addStatusMessage(
        $count . '/' . count($idsRaw) . ' ' . $result,
        ($adresy->lastResponseCode == 200) ? 'success' : 'warning'
    );
}
