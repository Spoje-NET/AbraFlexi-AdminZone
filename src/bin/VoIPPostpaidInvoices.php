<?php

/*
 * Inportuje provolané z IPEXu a vystavuje faktury do AbraFlexi
 */

namespace AdminZone;

define('EASE_APPNAME', 'IPEXPostPaid');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$defaultLocale = 'cs_CZ';
setlocale(LC_ALL, $defaultLocale);
putenv("LC_ALL=$defaultLocale");

$ipexer = new IPEX();

$ipexer->processIpexInvoices($ipexer->getIpexInvoices());
