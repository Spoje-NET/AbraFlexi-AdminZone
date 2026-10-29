<?php

namespace AdminZone;

/**
 * System.spoje.net - Odeslání výpisu z banky
 *
 * @author     Vítězslav Dvořák <info@vitexsofware.cz>
 * @copyright  (G) 2017 Vitex Software
 */

define('EASE_APPNAME', 'FIO2Mail');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$accountantMail = constant('EASE_EMAILTO') . ',' . constant('ACCOUNTANT_MAIL');

$downloader = new \FioApi\Downloader(constant('FIO_RO_TOKEN'));

$start = new \DateTime();
$start->modify('first day of last month');
$end = new \DateTime();
$end->modify('last day of last month');
$period = new \DatePeriod($start, new \DateInterval('P1D'), $end);

$subject = sprintf(
    _('Výpis z účtu FIO Spoje.Net - %s to %s'),
    \strftime('%x', $period->getStartDate()->getTimestamp()),
    \strftime('%x', $period->getEndDate()->getTimestamp())
);

$client = $downloader->getClient();
//$url    = \FioApi\UrlBuilder::BASE_URL.'periods/'.constant('FIO_RO_TOKEN').'/'.$period->getStartDate()->format('Y-m-d').'/'.$period->getEndDate()->format('Y-m-d').'/transactions.pdf';


$mailer = new Mailer(
    $accountantMail,
    $subject,
    new \Ease\Html\DivTag(sprintf(
        'Pravidelný měsíční výpis od %s do %s odesílaný robotem.<p><br></p>',
        \strftime('%x', $period->getStartDate()->getTimestamp()),
        \strftime('%x', $period->getEndDate()->getTimestamp())
    ))
);

foreach (
    ["FIO_RO_TOKEN" => 'Hlavní účet', "FIO_ZALOHY_KLIENTU" => 'Zálohy klientů',
    "FIO_SPORICI" => 'Spořící', "FIO_SPORICI_EUR" => 'Spořící EUR'] as $tokenName => $accountName
) {
    $mailer->addStatusMessage($tokenName, 'debug');
    $url = \FioApi\UrlBuilder::BASE_URL . 'by-id/' . constant($tokenName) . '/' . $start->format('Y') . '/' . $start->format('n') . '/transactions.pdf';
    try {
        $pdfFilename = sys_get_temp_dir() . '/spojenet_' . strtolower($tokenName) . '-' . $start->format('Y') . '_' . $start->format('n') . '.pdf';
        /** @var ResponseInterface $response */
        $response = $client->request(
            'get',
            $url,
            ['verify' => $downloader->getCertificatePath()]
        );
        $result = true;
        if (file_put_contents($pdfFilename, $response->getBody()) !== false) {
            $mailer->addFile($pdfFilename, 'application/pdf');
            $mailer->addStatusMessage(
                sprintf(_('Attaching %s'), $pdfFilename),
                'success'
            );
        } else {
            $mailer->addStatusMessage(
                sprintf(_('Error saving %s'), $pdfFilename),
                'error'
            );
        }
        unlink($pdfFilename);
    } catch (\GuzzleHttp\Exception\BadResponseException $e) {
        switch ($e->getCode()) {
            case 409:
                $mailer->addStatusMessage(
                    $e->getCode() . ': ' . _('You can use one token for API call every 30 seconds'),
                    'error'
                );
                break;

            case 500:
                $mailer->addStatusMessage(
                    $e->getCode() . ': ' . _('Server returned 500 Internal Error (probably invalid token?)'),
                    'error'
                );
                break;
            case 404:
                $mailer->addStatusMessage($e->getCode() . ': ' . sprintf(
                    _('Url not found %s'),
                    $url
                ), 'error');
                break;
            default:
                break;
        }

        $result = false;
    }

    if ($result) {
        $mailer->addItem(new \Ease\Html\DivTag(sprintf(
            '<li>přikládám výpis: <strong>%s</strong></li>',
            $accountName
        )));
    } else {
        $mailer->addItem(new \Ease\Html\DivTag(sprintf(
            '<li>výpis <em>%s</em> není pro toto období k dispozici</li>',
            $accountName
        )));
    }

    sleep(30);
}

$mailer->send();

return $result;
