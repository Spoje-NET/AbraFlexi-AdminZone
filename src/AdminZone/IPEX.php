<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone;

/**
 * Description of IPEX
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class IPEX extends \Ease\Sand
{
    /**
     *
     * @var float
     */
    public $invoicingLimit = 200.0;

    /**
     *
     * @var FakturaVydana
     */
    public $invoicer = null;

    /**
     *
     * @var \DateTime
     */
    public $since = null;

    /**
     *
     * @var \DateTime
     */
    public $until = null;

    /**
     *
     * @var string
     */
    private $counter = "";

    /**
     * Get IPEX invoices data for last month
     *
     * @return array
     */
    public function getIpexInvoices()
    {
        $grabber = new \IPEXB2B\ApiClient('', ['section' => 'invoices']);

        $this->since = new \DateTime("first day of last month");
        $this->until = new \DateTime("last day of last month");
//        $grabber->setUrlParams(['dateFrom' => $this->since->format(\DateTime::ATOM),'dateTo' => $this->until->format(\DateTime::ATOM)]);

        $grabber->setUrlParams(['monthOffset' => -1]);

        return $grabber->requestData('postpaid');
    }

    /**
     *
     * @param array $invoicesRaw
     */
    public function processIpexInvoices($invoicesRaw)
    {
        $adresar = new \AbraFlexi\Adresar();
        $adresar->logBanner(_('IPEX invoice import'));
        $position = 0;
        foreach ($invoicesRaw as $invoiceRaw) {
            $this->counter = '#' . ++$position . '/' . count($invoicesRaw) . ' ';
            $sumCelkem = 0;
            $klientExtID = 'ext:ipex:' . $invoiceRaw['customerId'];

            if ($adresar->recordExists($klientExtID)) {
                $order = $this->createOrder($invoiceRaw);

                if (!is_null($order)) {
                    $calls = $this->getUnivoicedCalls($klientExtID);
                    if ($this->uninvoicedAmount($calls) > $this->invoicingLimit) {
                        $this->createInvoice($calls, $invoiceRaw['customerId']);
                    }
                }
            } else {
                $this->noIpexExtID($invoiceRaw);
            }
        }
    }

    /**
     * Get IPEX orders not in "done" state
     * @param string $klientExtID
     *
     * @return array Orders with IPEX_POSTPAID item
     */
    public function getUnivoicedCalls($klientExtID)
    {
        $ipexOrders = [];
        foreach ($this->getUsersPreparedOrders($klientExtID) as $order) {
            if (!empty($order['polozkyDokladu'])) {
                foreach ($order['polozkyDokladu'] as $orderItem) {
                    if ($orderItem['kod'] == 'IPEX_POSTPAID') {
                        $ipexOrders[$order['kod']] = $order;
                    }
                }
            }
        }
        return $ipexOrders;
    }

    /**
     *
     * @param string $klientExtID
     *
     * @return array
     */
    public function getUsersPreparedOrders($klientExtID)
    {
        $result = [];
        $this->getInvoicer()->defaultUrlParams['order'] = 'datVyst@A';
        $orders = $this->getInvoicer()->getColumnsFromAbraFlexi(
            [
            'kod',
            'mena',
            'firma',
            'smerKod',
            'datVyst',
            'specSym',
            'sumCelkem',
            'sumCelkemMen',
            'polozkyDokladu(kod,nazev,cenaMj)'
                ],
            ['firma' => $klientExtID, 'stavUzivK' => 'stavDoklObch.pripraveno', 'typDokl' => 'code:OBP_VOIP'],
            'id'
        );

        if ($this->getInvoicer()->lastResponseCode == 200) {
            if (empty($orders)) {
                $result = [];
            } else {
                $result = $orders;
            }
        }
        return $result;
    }

    /**
     *
     * @param string $klientExtID
     *
     * @return string
     */
    public function foundLastInvoice($klientExtID)
    {
        return $this->getInvoicer()->getPrevRecordID(['firma' => $klientExtID]);
    }

    /**
     * Count Given Orders Total
     *
     * @param array $ordersData
     *
     * @return float
     */
    public function uninvoicedAmount($ordersData)
    {
        $amount = 0;
        foreach ($ordersData as $orderCode => $orderData) {
            $amount += floatval($orderData['sumCelkem']);
        }
        return $amount;
    }

    /**
     * Create Order from IPEX
     *
     * @param array $invoiceRaw
     *
     * @return \AdminZone\FakturaVydana
     */
    function createOrder($invoiceRaw)
    {
        $startDate = new \DateTime($invoiceRaw['dateStart']);
        $endDate = new \DateTime($invoiceRaw['dateEnd']);

        $order = $this->getInvoicer([
            'typDokl' => 'code:OBP_VOIP',
            'stavUzivK' => 'stavDoklObch.pripraveno',
            'zaokrNaSumK' => 'zaokrNa.zadne',
            'zaokrNaDphK' => 'zaokrNa.zadne',
//            'datVyst' =>
            'popis' => _('IPEX Postpaid')
        ]);
        $order->setEvidence('objednavka-prijata');
        $order->setDataValue('stitky', 'SYSTEM');
        $order->setDataValue('firma', 'ext:ipex:' . $invoiceRaw['customerId']);

        $pricelistItem = [
            'cenaMj' => $invoiceRaw['price'],
            'nazev' => 'Telefonní služby od ' . self::formatDate($startDate) . ' do ' . self::formatDate($endDate),
            'cenik' => \AbraFlexi\Functions::code('IPEX_POSTPAID'),
            'stitky' => 'API_IPEX'
        ];
        $order->setDataValue('popis', $pricelistItem['nazev']);

        if (floatval($invoiceRaw['price'])) {
            $order->addArrayToBranch($pricelistItem, 'polozkyDokladu');
            $order->sync();
            $order->setDataValue('firma', 'ext:ipex:' . $invoiceRaw['customerId']);
            $order->addStatusMessage(
                $this->counter . $invoiceRaw['customerName'] . ' ' . $invoiceRaw['price'] . ' CZK ',
                'success'
            );

            $pdfCallLog = $this->pdfCallLog($invoiceRaw['customerId'], $order->getDataValue('nazFirmy'));

            $callLogFilename = sys_get_temp_dir() . '/' . str_replace(
                [' ', ':'],
                ['_', '-'],
                \Ease\Functions::rip($order->getDataValue('popis'))
            ) . '.pdf';

            file_put_contents($callLogFilename, $pdfCallLog);
            \AbraFlexi\Priloha::addAttachmentFromFile($order, $callLogFilename);

            $this->sendCallListByMail($order, $invoiceRaw['customerId'], $callLogFilename);

            unlink($callLogFilename);

            return $order;
        } else {
            $order->addStatusMessage($this->counter . $invoiceRaw['customerName'] . ' 0,-', 'debug');
        }
    }

    public static function formatDate($dateTime)
    {
        return strftime("%y %m. %d. %T %Z ", $dateTime->getTimestamp());
    }

    /**
     * Gives you call log as PDF
     *
     * @param int    $ipexCustomerID   internal IPEX id
     * @param string $customerName     to use in filename
     * @param int    $offset           months back to include in list
     *
     * @return string binary PDF
     */
    public function pdfCallLog($ipexCustomerID, $customerName, $offset = 1)
    {
        $caller = new \IPEXB2B\Calls();

        $startDate = new \DateTime();
        $startDate->modify(' -' . $offset . ' month');
        $now = new \DateTime();
        $calls = $caller->getCallsForCustomer($startDate, $ipexCustomerID);
        if ($calls) {
            $report = new \Ease\Container(new \Ease\Html\H2Tag(_('Calls listing')));
            $report->addItem(new \Ease\Html\PTag(strftime(
                '%D',
                $startDate->getTimestamp()
            ) . ' - ' . strftime(
                '%D',
                $now->getTimestamp()
            )));
            $report->addItem(new ui\CallsListing(
                $calls,
                ['style' => 'font-size: small']
            ));

            $tmpdir = sys_get_temp_dir() . '/mpdf/';

            if (!file_exists($tmpdir)) {
                mkdir($tmpdir);
            }
            $html2pdf = new \Mpdf\Mpdf([
                'default_font_size' => 8,
                'default_font' => 'dejavusans',
                'tempDir' => sys_get_temp_dir() . '/mpdf/'
            ]);
            $html2pdf->setDefaultFont("Helvetica");
            $html2pdf->writeHTML((string) $report);
            return $html2pdf->Output('', 'S');
        }
    }

    /**
     * Save PDF Call log to file in system temp dir
     *
     * @param int    $ipexCustomerID
     * @param string $customerName
     * @param int    $offset        months
     *
     * @return string saved callLog filename
     */
    public function savePdfCallLog($ipexCustomerID, $customerName, $offset = 1)
    {
        $startDate = new \DateTime();
        $startDate->modify(' -' . $offset . ' month');
        $now = new \DateTime();

        $pdfFilename = sys_get_temp_dir() . '/' . urlencode(str_replace(
            ' ',
            '_',
            \Ease\Functions::rip($customerName)
        )) . '_' . _('Calls') . '_' . $startDate->format('Y-m-d') . '_' . $now->format('Y-m-d') . '.pdf';
        if (
            file_put_contents(
                $pdfFilename,
                $this->pdfCallLog($ipexCustomerID, $pdfFilename, $offset)
            )
        ) {
            return $pdfFilename;
        }
    }

    /**
     *
     * @param \AbraFlexi\FakturaVydana $order
     */
    public function addCallLogAsItems($order)
    {
        $caller = new \IPEXB2B\Calls();

        $calls = $caller->getCallsForCustomer(
            $startDate,
            $invoiceRaw['customerId']
        );

        $callsByNumber = [];

        if ($calls) {
            foreach ($calls as $callsData) {
                $callsByNumber[$callsData['cislo_int']][] = $callsData;
            }

            foreach ($callsByNumber as $internalNumber => $numberCalls) {
                $order->addArrayToBranch(['typPolozkyK' => 'typPolozky.text', 'nazev' => '======================== ' . $internalNumber . ' ========================']);
                foreach ($numberCalls as $callsData) {
                    $pricelistItem = [
                        'typPolozkyK' => 'typPolozky.text',
                        'nazev' => trim($callsData['cislo_ext'] . ' , ' . $callsData['destinace'] . ' , ' . $callsData['stav'] . ' , ' . str_replace(
                            '00:00:00',
                            '',
                            $callsData['datetime'] . ' , ' . $callsData['cena'] . ' Kč'
                        )),
                    ];
                    $order->addArrayToBranch($pricelistItem);
                }
            }
        }
    }

    /**
     * Send CallList by mail
     *
     * @param FakturaVydana $order
     * @param int $ipexCustomerID IPEX customer ID
     * @param string $callLogFilename PDF File path
     *
     * @return boolean send status
     */
    public function sendCallListByMail(
        $order,
        $ipexCustomerID,
        $callLogFilename = null
    ) {
        $adresser = new \AbraFlexi\Adresar($order->getDataValue('firma'));

        $mailer = new Mailer(
            $adresser->getNotificationEmailAddress(),
            _('Listing') . ' ' . $order->getDataValue('popis') . ' ' . _('for') . ' ' . $adresser->getDataValue('nazev')
        );

//        $mailer->addFile($order->downloadInFormat('pdf', '/tmp/'), //Přiložit objednávku
//            \AbraFlexi\Formats::$formats['PDF']['content-type']);

        $mailer->addFile(
            $callLogFilename, //Zaslat pouze výpis hovorů
            \AbraFlexi\Formats::$formats['PDF']['content-type']
        );

        return $mailer->send();
    }

    /**
     * Notify invoice without AbraFlexi bond
     *
     * @param array $invoiceRaw
     *
     * @return boolean send status
     */
    public function noIpexExtID($invoiceRaw)
    {
        $subject = sprintf(
            _('%s without IPEX extID'),
            $invoiceRaw['customerName']
        );
        $opMail = 'helpdesk@spoje.net,system@spoje.net';
        $mailer = new \Ease\HtmlMailer($opMail, $subject);
        $mailer->addItem(new \Ease\Html\PTag($subject));
        $mailer->addItem(new \Ease\Html\ATag(
            \Ease\Shared::cfg('SYSTEM_URL') . '/ipex.php?customerId=' . $invoiceRaw['customerId'] . "&extid=" . $invoiceRaw['externId'],
            $invoiceRaw['customerName']
        ));
        return $mailer->send();
    }

    /**
     * Create new IPEX Invoice
     *
     * @param array $callsOrders
     * @param int   $ipexCustomerID Customer's IPEX ID
     */
    public function createInvoice($callsOrders, $ipexCustomerID)
    {
        $invoice = new FakturaVydana();
        $invoice->setDataValue('typDokl', \AbraFlexi\Functions::code('FAKTURA'));

        $invoice->setDataValue('stavMailK', 'stavMail.neodesilat');
        $invoice->setDataValue('firma', \AbraFlexi\Functions::code(current($callsOrders)['firma']));
        $invoice->setDataValue('typUcOp', \AbraFlexi\Functions::code('TRŽBA SLUŽBY INT'));

        foreach ($callsOrders as $orderCode => $orderData) {
            if (!empty($orderData['polozkyDokladu'])) {
                foreach ($orderData['polozkyDokladu'] as $orderItem) {
                    if ($orderItem['kod'] == 'IPEX_POSTPAID') { //TODO: konstanta, nebo promenna z konfiguraku
                        unset($orderItem['id']);
                        unset($orderItem['kod']);
                        $orderItem['cenik'] = \AbraFlexi\Functions::code('IPEX_POSTPAID');
                        $invoice->addArrayToBranch($orderItem);
                    } else {
                        $invoice->addStatusMessage(
                            $this->counter . $orderData['firma']->showAs . ' ' . $orderCode . ': NO IPEX ITEM ' . $orderItem['kod'] . ':' . $orderItem['nazev'],
                            'warning'
                        );
                    }
                }
            }
        }

        $startDate = clone $this->since;
        $startDate->modify(' -' . count($callsOrders) . ' month')->modify('first day of next month');

        $invoice->setDataValue(
            'popis',
            'Telefonní služby ' /* . _('from') . ' ' . self::formatDate($startDate) . ' ' */ . _('to') . ' ' . self::formatDate($this->until)
        );

        $invoice->setDataValue('duzpPuv', \AbraFlexi\RW::dateToFlexiDate($this->until));

        if ($invoice->sync()) {
            $invoice->addStatusMessage(
                $this->counter . $invoice->getDataValue('firma')->showAs . ' ' . $invoice->getDataValue('sumCelkem') . ' CZK ',
                'success'
            );

            \AbraFlexi\Priloha::addAttachmentFromFile($invoice, $this->savePdfCallLog($ipexCustomerID, $invoice->getDataValue('firma')->showAs, count($callsOrders)));

            $invoice->insertToAbraFlexi(['id' => $invoice, 'stavMailK' => 'stavMail.odeslat']);

            $orderHelper = new FakturaVydana();
            $orderHelper->setEvidence('objednavka-prijata');
            foreach ($callsOrders as $orderCode => $orderData) {
                if (
                    $orderHelper->sync(['id' => \AbraFlexi\Functions::code($orderCode),
                            'typDokl' => 'code:OBP', 'stavUzivK' => 'stavDoklObch.hotovo'])
                ) {
                    $orderHelper->addStatusMessage(sprintf(
                        _('%s Order %s marked as done'),
                        $orderData['firma']->showAs,
                        $orderCode
                    ), 'success');
                } else {
                    $orderHelper->addStatusMessage(sprintf(
                        _('%s Order %s marked as done'),
                        $orderData['firma']->showAs,
                        $orderCode
                    ), 'error');
                }
            }
        }
    }

    /**
     * @param $forceData initial data
     *
     * @return FakturaVydana
     */
    public function getInvoicer($forceData = [])
    {
        if (is_null($this->invoicer)) {
            $this->invoicer = new FakturaVydana();
        }
        $this->invoicer->setData($forceData, true);
        return $this->invoicer;
    }
}
