#!/usr/bin/php -f
<?php

/**
 * System.spoje.net - Zpracování zatím nezachycených úprav
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */

define('EASE_APPNAME', 'API_Action');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

if ($argc > 1) {
    $id = is_numeric($argv[1]) ? intval($argv[1]) : $argv[1];
    $evidence = isset($argv[2]) ? $argv[2] : 'faktura-vydana';
    $engine = new AbraFlexi\RW($id, ['evidence' => $evidence]);

    $handlerClassName = \AbraFlexi\RO::evidenceToClassName($evidence);
    $handlerClassFile = 'System/whplugins/' . $handlerClassName . '.php';
    if (file_exists($handlerClassFile)) {
        include_once $handlerClassFile;
    }

    $handlerClass = '\\SpojeNet\\System\\whplugins\\' . $handlerClassName;
    if (class_exists($handlerClass)) {
        $handler = new $handlerClass($id, []);
        foreach ($handler->orderModulesForInvoiceItems() as $orderModul) {
            $orderModul->settled($engine);
        }
    }
} else {
    die($argv[0] . ' [ID] <evidence>');
}
