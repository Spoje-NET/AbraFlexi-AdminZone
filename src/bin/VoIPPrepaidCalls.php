<?php

/*
 * Odesílá přehled provolaného z IPEXu
 */

namespace AdminZone;

define('EASE_APPNAME', 'IPEXPrepid');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$defaultLocale = 'cs_CZ';
setlocale(LC_ALL, $defaultLocale);
putenv("LC_ALL=$defaultLocale");

$grabber = new \IPEXB2B\ApiClient('', ['section' => 'invoices']);
$grabber->setUrlParams(['monthOffset' => -1]);
$invoicesRaw = $grabber->requestData('prepaid');
$adresar = new \AbraFlexi\Adresar();
$adresar->logBanner(_('Ipex Call Protocol'));

foreach ($invoicesRaw as $invoiceRaw) {
    $sumCelkem = 0;
    $klientExtID = 'ext:ipex:' . $invoiceRaw['customerId'];

    if ($adresar->recordExists($klientExtID)) {
        $adresar->loadFromAbraFlexi($klientExtID);
        $email = Mailer::getNotificationEmailAddres($adresar);
        $startDate = \IPEXB2B\ApiClient::ipexDateTimeToDateTime($invoiceRaw['dateStart']);
        $endDate = \IPEXB2B\ApiClient::ipexDateTimeToDateTime($invoiceRaw['dateEnd']);

        $caller = new \IPEXB2B\Calls();

        $startDate = new \DateTime();
        $startDate->modify('-1 month');
        $now = new \DateTime();
        $calls = $caller->getCallsForCustomer(
            $startDate,
            $invoiceRaw['customerId']
        );
        if ($calls) {
            $range = strftime('%D', $startDate->getTimestamp()) . ' - ' . strftime(
                '%D',
                $now->getTimestamp()
            );
            $report = new \Ease\Container(new \Ease\Html\H2Tag('Calls listing'));
            $report->addItem(new \Ease\Html\PTag($range));
            $report->addItem(new ui\CallsListing(
                $calls,
                ['style' => 'font-size: small']
            ));

            $mpdfTmpDir = sys_get_temp_dir() . '/mpdf';

            if (!file_exists($mpdfTmpDir)) {
                mkdir($mpdfTmpDir);
            }
            $html2pdf = new \Mpdf\Mpdf([
                'default_font_size' => 8,
                'default_font' => 'dejavusans',
                'tempDir' => $mpdfTmpDir
            ]);
            $html2pdf->setDefaultFont("Helvetica");
            $html2pdf->writeHTML((string) $report);
            $pdfFilename = $mpdfTmpDir . '/' . $invoiceRaw['customerId'] . '_' . _('Calls') . '_' . $startDate->format('Y-m-d') . '_' . $now->format('Y-m-d') . '.pdf';

            $html2pdf->Output($pdfFilename, \Mpdf\Output\Destination::FILE);

            $postman = new Mailer(
                $email,
                _('Prepaid Calls listing') . ' ' . $range,
                _('Prepaid Calls for last month')
            );
            $postman->addFile($pdfFilename, 'application/pdf');

            unlink($pdfFilename);

            $postman->send();
        }
    } else {
        $engine->addStatusMessage(
            $invoiceRaw['customerName'] . ' without extID',
            'warning'
        );
        $engine->addStatusMessage(
            constant('SYSTEM_URL') . '/ipex.php?customerId=' . $invoiceRaw['customerId'] . "&extid=" . $invoiceRaw['externId'],
            'debug'
        );
    }
}
