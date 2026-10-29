<?php

namespace SNLms;

define('EASE_LOGGER', 'console|syslog');
define('EASE_APPNAME', 'Proforma2Faktura');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$invoicer = new \AdminZone\FakturaVydana();
$invoicesThisMonth = $invoicer->getThisMonthIDs('ZALOHA');

print_r($invoicesThisMonth);
