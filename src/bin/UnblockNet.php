<?php

namespace AdminZone;

/**
 * AdminZone - Unblock internet access of customers who do not owe anymore.
 *
 * Access is controlled by AbraFlexi labels: removing ODPOJENO (and reminder
 * labels) from the customer address lets the network side reconnect the client.
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2017-2026 Vitex Software
 */

define('EASE_APPNAME', 'UnblockNet');
$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$reminder = new Upominac();
$reminder->invoicer->logBanner(\Ease\Shared::appName());

$debters = [];
$invcount = 0;
foreach ($reminder->getInvoicesStatus(["typDokl eq 'code:FAKTURA' or typDokl eq 'code:ZALOHA'", 'limit' => 0]) as $companyCode => $companyInfo) {
    ++$invcount;
    if ($companyInfo['balance'] < 0) {
        $debters[\AbraFlexi\Functions::uncode($companyCode)] = $companyInfo;
    }
}
$reminder->addStatusMessage($invcount . ' ' . _('invoices checked') . ', ' . count($debters) . ' ' . _('customers owing'));

$unblocked = $reminder->enableCustomers($debters);
$reminder->addStatusMessage(sprintf(_('%d customers unblocked'), $unblocked), $unblocked ? 'success' : 'info');
