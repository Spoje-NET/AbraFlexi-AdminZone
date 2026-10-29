#!/usr/bin/php -f
<?php

/**
 * System.spoje.net - Vyčistí databázi histore
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2019 Vitex Software
 */

define('EASE_APPNAME', 'CMD');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

if ($argc == 2) {
    $engine->setObjectName('CMD Message');

    function stdin_stream()
    {
        while ($line = fgets(STDIN)) {
            yield $line;
        }
    }

    foreach (stdin_stream() as $line) {
        $engine->addStatusMessage(
            trim($line),
            isset($argv[1]) ? $argv[1] : 'info'
        );
    }
} else {
    echo "Usage " . __FILE__ . ' [message type: success|error|warning|info|debug] ';
}
