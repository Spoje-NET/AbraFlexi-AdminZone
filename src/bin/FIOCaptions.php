<?php

/*
 * How to Fix Trimmed Banking Labels in AbraFlexi
 * using mhujer/fio-api-php and spoje.net/abraflexi
 */

define('EASE_LOGGER', 'console|syslog|email');
define('EASE_APPNAME', 'FioCaptions');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$banker = new \AbraFlexi\Banka();
$downloader = new \FioApi\Downloader(constant('FIO_RO_TOKEN'));

try {
    $transactionList = $downloader->downloadSince(new \DateTime('-1 day'));
    foreach ($transactionList->getTransactions() as $transaction) {
        $id = $transaction->getId();
        $message = $transaction->getUserMessage();
        if (strlen($message > 20)) {
            $banker->setFilter(['vypisCisDokl' => '00' . $id]);
            $banker->insertToAbraFlexi(['id' => 'ext:fio:' . $id, 'popis' => $message]);
            $banker->addStatusMessage(
                sprintf(
                    _('Updating the Banking Label %s to %s'),
                    $id,
                    $message
                ),
                ($banker->lastResponseCode == 201) ? 'success' : 'error'
            );
        }
    }
} catch (\GuzzleHttp\Exception\BadResponseException $e) {
    if ($e->getCode() == 409) {
        $banker->addStatusMessage(
            $e->getCode() . ': ' . _('You can use one token for API call every 30 seconds'),
            'error'
        );
    }
    if ($e->getCode() == 500) {
        $banker->addStatusMessage($e->getCode() . ': ' . _(
            'Server returned 500 Internal Error (probably invalid token?)',
            'error'
        ));
    }
}
