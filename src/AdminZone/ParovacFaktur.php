<?php

namespace AdminZone;

use AbraFlexi\Functions as fb;

/**
 * Description of ParovacFaktur
 *
 * @author vitex
 */
class ParovacFaktur extends \AbraFlexi\Bricks\ParovacFaktur
{

    /**
     * Objekt Banky
     * @var \AbraFlexi\FakturaVydana
     */
    public $invoicer;

    public function __construct($configuration = [])
    {
        parent::__construct($configuration);
        $this->invoicer = $this->getInvoicer();
        $this->setObjectName();
    }

    /**
     * Párování faktur podle příchozích plateb v bance
     *
     * @return array payment with invoice matching results
     */
    public function invoicesMatchingByBank()
    {
        $results = [];
        foreach ($this->getPaymentsToProcess($this->daysBack) as $paymentData) {
            $results[$paymentData['kod']] = $this->matchPayment($paymentData);
        }
        return $results;
    }

    /**
     * Try to find invoices for payment and match with
     *
     * @param array $paymentData
     */
    public function matchPayment($paymentData)
    {
        $matched = 0;
        $this->addStatusMessage(
                sprintf(
                        'Processing payment %s %s %s vs: %s ss: %s %s',
                        $paymentData['kod'],
                        $paymentData['sumCelkem'],
                        $paymentData['mena']->showAs,
                        $paymentData['varSym'],
                        $paymentData['specSym'], //TOODO Chybi varSym
                        $this->banker->url . '/c/' . $this->banker->company . '/' . $this->banker->getEvidence() . '/' . $paymentData['id']
                ),
                'debug'
        );

        $payment = new \AbraFlexi\Banka($paymentData);

        $payment->sync(['id' => $paymentData['id'], 'stitky' => 'SYSTEM']);

        $lastPayer = self::getPayerForAccount(
                        $paymentData['buc'],
                        $paymentData['smerKod']
        );

//
//        MURA
//        jestlize je znam bankovni ucet (vklad na prepazce na nas ucet)
//        jestlize se nejedna o cislo bankovniho uctu, ktery je jiz prirazen jinemu klientovi tak ..... DO
//        ELSE zapis do logu ze nejaky klient posila penize za jineho klienta a NESPARUJ - BREAK


        $invoices = $this->findInvoices($paymentData);
//  kdyz se vrati jedna faktura:
//     kdyz  je prijata castka mensi nebo rovno tak zlikviduji celou
//     kdyz sedi castka, nebo castecne
//  kdyz se vrati vic faktur  tak kdyz sedi castka uhrazuje se ta nejstarsi
//  jinak se uhrazuje castecne
        $matched = false;
        if (count($invoices) && count(current($invoices))) {
            $prijatoCelkem = floatval($paymentData['sumCelkem']);
            foreach ($invoices as $invoiceID => $invoiceData) {
                $invoice = new \AbraFlexi\FakturaVydana(
                        fb::code($invoiceID),
                        ['defaultUrlParams' => ['relations' => 'vazby']]
                );

                if ($lastPayer && ($lastPayer != $invoice->getDataValue('firma'))) {
                    $invoice->addStatusMessage(
                            sprintf(
                                    _('Different previous payer: %s. Current payer: %s'),
                                    fb::uncode($lastPayer),
                                    fb::uncode($invoice->getDataValue('firma'))
                            ),
                            'info'
                    );
                }

                switch ($invoiceData['typDokl']) {
//                        case 'code:KAUCE':
                    case 'code:FAKTURA':
                    case 'code:TEST_FAKTURA':
                    case 'code:FAKTURA PDP':
                        $matched = $this->settleInvoice(
                                $invoice,
                                new \AbraFlexi\Banka('code:' . $paymentData['kod'])
                        );
                        break;
                    case 'code:ZALOHA_PROJEKT':
                        $matched = $this->settleZalohaZDD($invoice, $payment);
                        break;
                    case 'code:ZALOHA':
                    case 'code:TEST_ZALOHA':
                    case 'code:ZALOHA_OBJEDNAVKA':
                        $matched = $this->settleProforma($invoice, $payment);
                        break;
                    case 'code:DOBR':
                        if ($this->settleCreditNote($invoice, $payment)) {
                            
                        }
                        break;
                    case 'code:FAKTURA_EUR':
                        $this->addStatusMessages(sprintf(
                                        _('Faktura za Eura %s'),
                                        fb::uncode($invoice)
                                ), 'warning');
                        break;

                    default:
                        $this->addStatusMessage(
                                'invoicesMatchingByBank: ' . sprintf(
                                        _('Unknown invoice type: %s %s'),
                                        $invoiceData['typDokl'],
                                        fb::uncode($invoice)
                                ),
                                'warning'
                        );
                        break;
                }

                if (
                        $matched && is_null($lastPayer) && $this->savePayerAccount(
                                $invoice->getDataValue('firma'),
                                $payment
                        )
                ) {
                    $this->addStatusMessage(sprintf(
                                    _('new Bank account %s assigned to Address %s'),
                                    $payment->getDataValue('buc') . '/' . fb::uncode($payment->getDataValue('smerKod')),
                                    $invoice->getDataValue('firma')->showAs
                    ));
                }

                if (strstr($invoiceData['stitky'], 'API')) {
                    $opMail = 'system@spoje.net';
                    $subject = sprintf(
                            _('API AKCE: %s'),
                            $invoiceData['id']
                    );
                    $message = self::apiUrlToLink($invoice->apiURL) . "\n" . nl2br(print_r(
                                            $invoice->getData(),
                                            true
                    ));
                    $mailer = new \Ease\Mailer(
                            $opMail,
                            $subject,
                            $message
                    );
                    $invoiceTmpPdf = $invoice->downloadInFormat(
                            'pdf',
                            sys_get_temp_dir() . '/'
                    );
                    $mailer->addFile($invoiceTmpPdf, 'application/pdf');
                    $mailer->send();
                    unlink($invoiceTmpPdf);
                }

                $payment->reload();
                if ($payment->getDataValue('sparovano') == true) {
                    break;
                }
            }
        } else {
            if (!empty($paymentData['varSym']) || !empty($paymentData['specSym'])) {
                $payment->addStatusMessage(
                        _('Nenalezena faktura: - přeplatek?'),
                        'warning'
                );

//                $this->zdd($paymentData);
            }
        }

        return $matched;
    }

    /**
     * Report Wrong Invoice
     *
     * @param string $addText Additional text
     * @param FakturaVydana $invoice
     */
    public function reportInvoiceError($invoice, $addText = '')
    {
        $opMail = 'helpdesk@spoje.net,system@spoje.net';
        $subject = constant('EASE_APPNAME') . ': ' . sprintf(
                        _('Invoice Error: %s'),
                        fb::uncode($invoice->getRecordIdent())
        );

        $message = $_SERVER['PHP_SELF'] . "\n" . $addText . "\n" . $invoice->getApiURL() . "\n" . (json_encode(
                        $invoice->getData(),
                        JSON_PRETTY_PRINT
        ));
        $mailer = new \Ease\Mailer($opMail, $subject, $message);
        $invoiceTmpPdf = $invoice->downloadInFormat(
                'pdf',
                sys_get_temp_dir() . '/'
        );
        $mailer->addFile($invoiceTmpPdf, 'application/pdf');
        $mailer->send();
        unlink($invoiceTmpPdf);
    }

    /**
     * Párování faktur dle nezaplacenych faktur
     */

    /**
     * Provede "Zaplacení" vydaného dobropisu
     *
     * @param \AbraFlexi\FakturaVydana $invoice
     * @param \AbraFlexi\Banka $payment
     *
     * @return int vysledek 0 = chyba, 1 = sparovano
     */
    public function settleCreditNote($invoice, $payment)
    {
        $success = 0;
        $prijataCastka = (float) $payment->getDataValue('sumCelkem');

        if ($prijataCastka < $invoice->getDataValue('zbyvaUhradit')) { //Castecna uhrada
            $this->addStatusMessage(
                    sprintf(
                            _('Castecna uhrada - DOBROPIS: prijato: %s ma byt zaplaceno %s'),
                            $prijataCastka,
                            $invoice->getDataValue('zbyvaUhradit')
                    ),
                    'warning'
            );
        }
        if ($prijataCastka > $invoice->getDataValue('zbyvaUhradit')) { //Castecna uhrada
            $this->addStatusMessage(
                    sprintf(
                            _('Přeplatek - DOBROPIS: prijato: %s ma byt zaplaceno %s'),
                            $prijataCastka,
                            $invoice->getDataValue('zbyvaUhradit')
                    ),
                    'warning'
            );

            $payment->insertToAbraFlexi(['id' => $payment->getRecordIdent(), 'stitky' => 'PREPLATEK']);
        }

        try {
            $invoice->sparujPlatbu($payment, 'castecnaUhrada');  //Jak se ma AbraFlexi zachovat pri preplatku/nedoplatku
            $success = 1;
            $invoice->addStatusMessage(
                    sprintf(
                            _('Platba %s  %s byla sparovana s dobropisem %s'),
                            (string) $payment,
                            $prijataCastka,
                            (string) $invoice
                    ),
                    'success'
            );
//PDF Danoveho dokladu priloz k nemu samemu
//PDF Danoveho dokladu odesli mailem zakaznikovi y ABRAFLEXI( nasledne pouzit tabulku Mail/Gandalf)
        } catch (\AbraFlexi\Exception $exc) {
            $invoice->addStatusMessage($exc->getMessage(), 'error');
        }


        return $success;
    }

    /**
     * Provede "Zaplacení" vydané zalohove faktury
     *
     * @param \AbraFlexi\FakturaVydana $zaloha
     * @param \AbraFlexi\Banka         $payment
     *
     * @return int vysledek 0 = chyba, 1 = sparovano, 2 sparovano a vytvorena faktura, -1 sparovnano ale chyba vytvoreni faktury
     */
    public function settleProforma(
            \AbraFlexi\FakturaVydana $zaloha,
            \AbraFlexi\Banka $payment
    )
    {
        $success = 0;
        $prijataCastka = floatval($payment->getDataValue('sumCelkem'));

        try {
            $zaloha->sparujPlatbu($payment, 'castecnaUhrada');
            if ($payment->lastResponseCode == 201) {
                $success = 1;
                $zaloha->addStatusMessage(sprintf(
                                _('Platba %s  %s %s byla sparovana s zalohou %s'),
                                fb::uncode($payment),
                                $prijataCastka,
                                $payment->getDataValue('mena@showAs'),
                                fb::uncode($zaloha->getRecordCode())
                        ), 'success');

                if ($zaloha->getDataValue('zbyvaUhradit') > $prijataCastka) { // Castecna Uhrada
//                //Castecna uhrada
//                //Vytvorit ZDD ve vysi payment
//                $zdd = new \AbraFlexi\FakturaVydana(['firma' => $zaloha->getDataValue('firma'),
//                    'zavTxt' => $zaloha->getDataValue('zavTxt').' DOPLNIT!!! ',
//                    'varSym' => $zaloha->getDataValue('varSym'),
//                    'popis' => 'Částečná úhrada '.$zaloha->getDataValue('kod')
//                ]);
//
//                $zdd->setDataValue('typDokl', 'code:ZDD');
////                $zdd->setDataValue('zbyvaUhradit', 0); //Mozna nemusime resit -vymazat
////                $zdd->setDataValue('sumCelkem', $prijataCastka);
//                $zdd->setDataValue('szbDphZakl',
//                    $zaloha->getDataValue('szbDphZakl'));
//                $zdd->setDataValue('bezPolozek', true);
////                $zdd->setDataValue('stavUhrK', '');
//                $zdd->unsetDataValue('polozkyDokladu');
//
//                // ---------- Tady se resi sazby - nahrdit objektem pro praci s castkami --------------//
//                // DPH21
//                if ((float) $zaloha->getDataValue('sumCelkZakl')) {
//                    $sumZklZakl = $prijataCastka / ( 1 + (float) $zaloha->getDataValue('szbDphZakl')
//                        / 100 );
//
////                    $zdd->setDataValue('sumZklZakl', round($sumZklZakl, 2));
////                    $zdd->setDataValue('sumDphZakl',
////                        round($prijataCastka - $sumZklZakl, 2));
//                    $zdd->setDataValue('sumCelkZakl', round($prijataCastka, 2));
//                    // DPH00
//                } else {
//                    if ((float) $zaloha->getDataValue('sumOsv')) {
////                        $zdd->setDataValue('sumOsv', round($prijataCastka),
////                            2);
//                    }
//                }
//                $result = $zdd->insertToAbraFlexi();
//
//                $zdd->loadFromAbraFlexi();
//                $zaloha->debug = true;
//                $zdd->debug    = true;
//
//
//                $targt      = $platba->apiURL.'/vytvor-zdd.json';
//                $zauctovani = '01-02';
//                $value      = $zaloha->getDataValue('kod').'^^^'.$zauctovani;
//                $sender     = new \AbraFlexi\RW();
//                $sender->setPostFields(['zalohaACleneni' => $value]);
//                $result     = $sender->performRequest($targt, 'POST', 'json');
//
//                $result = $zdd->odpocetZDD($zaloha,
//                    ['castkaMen' => $prijataCastka]);
//                if (isset($result['success']) && ($result['success'] == 'true')) {
//                    $success = 2;
//                    $zaloha->addStatusMessage(sprintf(_('Faktura #%s byla sparovana se ZDD'),
//                            $kod), 'success');
//                } else {
//                    $success = -1;
//                    $zaloha->addStatusMessage(sprintf(_('Faktura #%s nebyla sparovana se ZDD'),
//                            $kod), 'error');
//                }
                    $zaloha->addStatusMessage(sprintf(
                                    _('Částečná úhrada %s'),
                                    fb::uncode($zaloha)
                            ), 'warning');

                    $zaloha->addStatusMessage(sprintf(
                                    _('Vytvoř ZDD: %s'),
                                    fb::uncode($payment)
                            ), 'debug');
                } else {
                    if ($prijataCastka > floatval($zaloha->getDataValue('zbyvaUhradit'))) { // Preplatek
                        $zaloha->addStatusMessage(
                                sprintf(
                                        _('Přeplatek %s'),
                                        $prijataCastka - floatval($zaloha->getDataValue('zbyvaUhradit'))
                                ),
                                'warning'
                        );
                    }

                    $faktura2 = $this->proforma2invoice(
                            $zaloha, //dostane faktura2 ID ?
                            ['duzpUcto' => $payment->getDataValue('datVyst'), 'datVyst' => $payment->getDataValue('datVyst')]
                    );

                    $faktura2->stripBody();

                    if ($zaloha->getDataValue('typDokl') == 'code:ZALOHA') {
                        $faktura2->setDataValue('clenDph', 'code:01-02'); //todo - tady by se asi melo resit zauctovani noveho dokladu (faktury) ????
                    }

                    try {
                        $result = $faktura2->odpocetZalohy($zaloha);
                        if ($faktura2->lastResponseCode == 201) {
                            $success = 2;
                            $zaloha->addStatusMessage(sprintf(
                                            _('Faktura %s byla sparovana se zálohou %s'),
                                            fb::uncode($faktura2->getRecordCode()),
                                            fb::uncode($zaloha->getRecordCode())
                                    ), 'success');
                            $faktura2->sync(['id' => $faktura2->getRecordIdent(), 'stavMailK' => 'stavMail.odeslat']);
                        } else {
                            sleep(5);
                            $result = $faktura2->odpocetZalohy($zaloha);
                            if ($faktura2->lastResponseCode == 201) {
                                $success = 2;
                                $zaloha->addStatusMessage(sprintf(
                                                _('Faktura %s byla sparovana se zálohou %s'),
                                                fb::uncode($faktura2->getRecordCode()),
                                                fb::uncode($zaloha->getRecordCode())
                                        ), 'success');
                                $faktura2->sync(['id' => $faktura2->getRecordIdent(), 'stavMailK' => 'stavMail.odeslat']);
                            } else {
                                $success = -1;
                                $zaloha->addStatusMessage(
                                        sprintf(
                                                _('Faktura #%s nebyla sparovana se zalohou #%s '),
                                                fb::uncode($faktura2),
                                                fb::uncode($zaloha)
                                        ),
                                        'error'
                                );
                                $this->reportInvoiceError(
                                        $zaloha,
                                        $faktura2->lastCurlResponse
                                );
                            }
                        }
                    } catch (Exception $exc) {
                        $this->addStatusMessage($exc->getMessage(), 'error');
                    }
                }

//PDF Danoveho dokladu priloz k nemu samemu
//PDF Danoveho dokladu odesli mailem zakaznikovi y ABRAFLEXI( nasledne pouzit tabulku Mail/Gandalf)
            } else {
                $this->reportInvoiceError($zaloha, _('Payment matching error'));
            }
        } catch (\AbraFlexi\Exception $exc) {
            $zaloha->addStatusMessage($exc->getMessage(), 'error');
        }

        return $success;
    }

    /**
     * Provede "Zaplacení" vydané zalohove faktury
     *
     * @param \AbraFlexi\FakturaVydana $zaloha
     * @param \AbraFlexi\Banka $payment
     *
     * @return int vysledek 0 = chyba, 1 = sparovano, 2 sparovano a vytvorena faktura, -1 sparovnano ale chyba vytvoreni faktury
     */
    public function settleZalohaZDD(
            \AbraFlexi\FakturaVydana $zaloha,
            \AbraFlexi\Banka $payment
    )
    {
        $success = 0;
        $prijataCastka = (float) $payment->getDataValue('sumCelkem');

        try {
            if ($zaloha->sparujPlatbu($payment, 'castecnaUhrada')) {
                $success = 1;
                $zaloha->addStatusMessage(
                        sprintf(
                                _('Platba %s  %s %s byla sparovana s zalohou %s %s'),
                                fb::uncode($payment),
                                $prijataCastka,
                                $payment->getDataValue('mena')->showAs,
                                fb::uncode($zaloha->getDataValue('typDokl')),
                                fb::uncode($zaloha)
                        ),
                        'success'
                );

                $extraValues = ['firma' => $zaloha->getDataValue('firma')];

                $copyer = new \AbraFlexi\Bricks\Convertor(
                        $zaloha,
                        new \AbraFlexi\FakturaVydana(array_merge(
                                        $extraValues,
                                        [
                                            'typDokl' => 'code:ZDD',
                                            'nazev' => $zaloha->getDataValue('nazev'),
                                            'stredisko' => $zaloha->getDataValue('stredisko'),
                                            'popis' => $zaloha->getDataValue('popis') . ' (' . sprintf(_('payment of advance invoice %s'), fb::uncode($zaloha->getRecordCode())) . ')',
                                            'stitky' => 'SYSTEM',
                                            'stavMailK' => 'stavMail.neodesilat',
                                            'bezPolozek' => true
                                        ]
                                ))
                );
                /**
                 * @var FakturaVydana ZDD
                 */
                $zdd = $copyer->conversion();

                $zdd->defaultUrlParams['relations'] = 'vazby';

                if ($zdd->sync()) {
                    $zdd->addStatusMessage(sprintf(
                                    _('New advance tax document was created: %s '),
                                    fb::uncode($zdd)
                            ), 'success');

                    if ($zdd->getDataValue('sumCelkem') == $payment->getDataValue('sumCelkem')) {
                        if ($zdd->vytvorVazbuZDD($payment)) {
                            $zdd->addStatusMessage(
                                    sprintf(
                                            _('ZDD %s bond to payment %s '),
                                            fb::uncode($zdd),
                                            fb::uncode($payment)
                                    ),
                                    'success'
                            );

                            $zdd->sync(['id' => $zdd, 'stavMailK' => 'stavMail.odeslat']);

                            $bonds = $zdd->getDataValue('vazby');
                            if (empty($bonds)) {
                                $zdd->addStatusMessage(
                                        sprintf(
                                                _('Error creating ZDD %s bond to payment %s '),
                                                fb::uncode($zdd),
                                                fb::uncode($payment)
                                        ),
                                        'error'
                                );
                            } else {
                                if (empty($zaloha->getDataValue('vazby'))) {
                                    $zaloha->defaultUrlParams['relations'] = 'vazby';
                                    $zaloha->reload();
                                }
                                $bonds = $zaloha->getDataValue('vazby');
                                foreach ($bonds as $bondData) {
                                    switch ($bondData['typVazbyK']) {
                                        case 'typVazbyDokl.obchod_zaloha_hla': // Advance created from order
                                            $order = new \AbraFlexi\RW(
                                                    null,
                                                    ['evidence' => 'objednavka-prijata']
                                            );

                                            $order->sync(['id' => $bondData['a'], 'stavUzivK' => 'stavDoklObch.schvaleno',
                                                'zavTxt' => _('Order was aproved for execution') . ' ' . (new \DateTime())->format('m d.Y H:i:s')]);
                                            if ($order->getDataValue('kontaktEmail')) {
                                                $this->notifyOrder($order);
                                            } else {
                                                $order->addStatusMessage(
                                                        sprintf(
                                                                _('Order %s without email'),
                                                                fb::uncode($order)
                                                        ),
                                                        'warning'
                                                );
                                            }
                                            break;

                                        default:
                                            break;
                                    }
                                }
                            }
                        }
                    } else {
                        $zdd->addStatusMessage(
                                sprintf(
                                        _('ZDD %s value %d does not match payment %s value %s'),
                                        fb::uncode($zdd),
                                        $zdd->getDataValue('sumCelkem'),
                                        $payment,
                                        $payment->getDataValue('sumCelkem')
                                ),
                                'error'
                        );
                    }
                } else {
                    $zdd->addStatusMessage(sprintf(
                                    _('Error creating ZZD for %s, %s %s '),
                                    fb::uncode($payment),
                                    $prijataCastka,
                                    $zaloha
                            ), 'error');
                }
            } else {
                $zaloha->addStatusMessage(sprintf(
                                _('Platba %s  %s nebyla sparovana s zalohou %s'),
                                fb::uncode($payment),
                                $prijataCastka,
                                $zaloha
                        ), 'error');
            }
        } catch (\AbraFlexi\Exception $exc) {
            $this->addStatusMessage($exc->getMessage(), 'event');
        }


        return $success;
    }

    /**
     * Provede "Zaplacení" vydané faktury
     *
     * @param \AbraFlexi\FakturaVydana $invoice
     * @param \AbraFlexi\Banka $payment
     *
     * @return int vysledek 0 = chyba, 1 = preplatek, 2 = sparovano
     */
    public function settleInvoice($invoice, $payment)
    {
        $success = 0;
        $prijataCastka = (float) $payment->getDataValue('sumCelkem');

        if ($prijataCastka < $invoice->getDataValue('zbyvaUhradit')) { //Castecna uhrada
            $invoice->addStatusMessage(
                    sprintf(
                            _('Castecna uhrada - FAKTURA: prijato: %s ma byt zaplaceno %s'),
                            $prijataCastka,
                            $invoice->getDataValue('zbyvaUhradit')
                    ),
                    'warning'
            );
        }
        if ($prijataCastka > $invoice->getDataValue('zbyvaUhradit')) { //Castecna uhrada
            $invoice->addStatusMessage(sprintf(
                            _('Přeplatek - FAKTURA: %s prijato: %s ma byt zaplaceno %s'),
                            fb::uncode($invoice),
                            $prijataCastka,
                            $invoice->getDataValue('zbyvaUhradit')
                    ), 'warning');

            $this->banker->dataReset();
            $this->banker->setDataValue('id', $payment->getDataValue('id'));
            $this->banker->setDataValue('stitky', 'PREPLATEK');
            $this->banker->insertToAbraFlexi();

            return 1;
        }

        try {
            if (
                    $prijataCastka && intval($invoice->getDataValue('sumCelkem')) && $invoice->sparujPlatbu(
                            $payment,
                            ($prijataCastka < $invoice->getDataValue('sumCelkem')) ? 'castecnaUhrada' : 'zauctovat'
                    )
            ) { //Jak se ma AbraFlexi zachovat pri preplatku/nedoplatku
                $success = 2;
                $invoice->addStatusMessage(
                        sprintf(
                                _('Platba %s  %s byla sparovana s fakturou %s'),
                                (string) $payment,
                                $prijataCastka,
                                fb::uncode($invoice)
                        ),
                        'success'
                );

                if ($invoice->getDataValue('stavMailK') != 'stavMail.odeslano') {
                    $invoice->insertToAbraFlexi(['id' => (string) $invoice, 'stavMailK' => 'stavMail.odeslat']);
                }
//        else {
//            $potvrzovac = new PotvrzeniUhrady($invoice);
//            $potvrzovac->send();
//        }
//PDF Danoveho dokladu priloz k nemu samemu
//PDF Danoveho dokladu odesli mailem zakaznikovi y ABRAFLEXI( nasledne pouzit tabulku Mail/Gandalf)
            } else {
                $this->reportInvoiceError($invoice, $payment->lastCurlResponse);
            }
        } catch (\AbraFlexi\Exception $exc) {
            $this->reportInvoiceError($invoice, $payment->lastCurlResponse);
            $success = 0;
        }

        return $success;
    }

    /**
     * Provizorní zkopírování faktury
     *
     * @link https://www.abraflexi.eu/podpora/Tickets/Ticket/View/28848 Chyba při Provádění akcí přes REST API JSON
     * @param \AbraFlexi\FakturaVydana $invoice
     * @param array                     $extraValues Extra hodnoty pro kopii faktury
     *
     * @return \AbraFlexi\FakturaVydana
     */
    function proforma2invoice($invoice, $extraValues = [])
    {

        if (isset($extraValues['datVyst'])) {
            $today = $extraValues['datVyst'];
        } else {
            $today = date('Y-m-d');
        }

        if (array_key_exists('duzpUcto', $extraValues)) {
            $duzpPuv = $extraValues['duzpUcto'];
        } else {
            $duzpPuv = $today;
        }

        $copyer = new \AbraFlexi\Bricks\Convertor(
                $invoice,
                new \AbraFlexi\FakturaVydana(array_merge(
                                $extraValues,
                                ['typDokl' => 'code:FAKTURA',
                                    'duzpPuv' => $duzpPuv,
                                    'duzpUcto' => $duzpPuv,
                                    'datUcto' => $today,
                                    'stitky' => 'SYSTEM',
                                    'stavMailK' => 'stavMail.neodesilat'
                                ]
                        ))
        );

        $invoice2 = $copyer->conversion();

////        $invoice2->debug = true;

        if (!array_key_exists('datSplat', $extraValues)) {
            $invoice2->unsetDataValue('datSplat');
        }
        if ($invoice2->getDataValue('stavUhrK') != 'stavUhr.uhrazenoRucne') {
            $invoice2->unsetDataValue('stavUhrK');
        }

        if (is_null($invoice2->getDataValue('stat'))) {
            $invoice2->unsetDataValue('stat');
        }

        $polozky = $invoice2->getDataValue('polozkyDokladu');
        $invoice2->unsetDataValue('polozkyDokladu');
        if (!empty($polozky)) {
            foreach ($polozky as $pid => $polozka) {
                unset($polozka['id']);
                unset($polozka['datUcto']);
                unset($polozka['doklFak']);
                unset($polozka['ucetni']);
                unset($polozka['doklFak@showAs']);
                unset($polozka['doklFak@ref']);
                if (array_key_exists('stitky', $polozka)) {
                    $labelsFiltered = [];
                    $labels = is_array($polozka['stitky']) ? $polozka['stitky'] : \AbraFlexi\Stitek::listToArray($polozka['stitky']);
                    foreach ($labels as $label) {
                        if (!preg_match('/^API/', $label)) {
                            $labelsFiltered[] = $label;
                        }
                    }
                    if (count($labelsFiltered)) {
                        $polozka['stitky'] = $labelsFiltered;
                    } else {
                        unset($polozka['stitky']);
                    }
                }
                $invoice2->addArrayToBranch($polozka);
            }
        }

        if ($invoice2->sync()) {
            $invoice2->addStatusMessage(sprintf(
                            _('Faktura %s byla vytvořena z dokladu %s'), //Loguje se Id a nen Kod ?
                            fb::uncode($invoice2),
                            fb::uncode($invoice)
                    ), 'success');
        }
        return $invoice2;
    }

    /**
     * Najde vydané faktury
     *
     * @param array $paymentData
     *
     * @return array
     */
    public function findInvoices($paymentData)
    {
        $invoices = [];
        $vInvoices = [];
        $sInvoices = [];
        $uInvoices = [];
        $bInvoices = [];

        if (!empty($paymentData['varSym'])) {
            $vInvoices = $this->findUnsettledInvoice(['varSym' => $paymentData['varSym']]);
        }

        if (empty($vInvoices)) {
            if (!empty($paymentData['specSym'])) {
// Faktury vydane "firma":"code:02100",
                $uInvoices = $this->findUnsettledInvoice(['firma' => sprintf(
                            "code:%05s",
                            $paymentData['specSym']
                )]);

                $sInvoices = $this->findUnsettledInvoice(['specSym' => $paymentData['specSym']]);
            }

            if ($paymentData['buc']) {
                $bInvoices = $this->findUnsettledInvoice(['buc' => $paymentData['buc']]);
            }
        }

        self::unifyInvoices($vInvoices, $invoices);
        self::unifyInvoices($uInvoices, $invoices);
        self::unifyInvoices($sInvoices, $invoices);
        self::unifyInvoices($bInvoices, $invoices);

        $invoices = self::reorderInvoicesByAge($invoices);

        $this->banker->setDataValue('kod', $paymentData['kod']);
        if (empty($paymentData['varSym']) && empty($paymentData['specSym'])) {
            $this->banker->dataReset();
            $this->banker->setDataValue('kod', $paymentData['kod']);
            $this->banker->addStatusMessage(
                    _('Unidentified payment') . ': ' . fb::uncode($this->banker),
                    'info'
            );
            $this->banker->setLabel('NEIDENTIFIKOVANO');
        } elseif (count($invoices) == 0) {
            $this->banker->dataReset();
            $this->banker->setDataValue('kod', $paymentData['kod']);
            $this->banker->addStatusMessage(
                    _('Payment without invoice') . ': ' . fb::uncode($this->banker),
                    'warning'
            );
            $this->banker->setLabel('CHYBIFAKTURA');
        }

        return $invoices;
    }

    /**
     * Najde příchozí platby
     *
     * @param array $invoiceData
     *
     * @return array
     */
    public function findPayments($invoiceData)
    {
        $sPays = [];
        $bPays = [];

        if (array_key_exists('varSym', $invoiceData) && !empty($invoiceData['varSym'])) {
            $sPays = $this->findPayment(['varSym' => $invoiceData['varSym']]);
            $pays = $sPays;
        }

        if (array_key_exists('buc', $invoiceData) && !empty($invoiceData['buc'])) {
            $bPays = $this->findPayment(['buc' => $invoiceData['buc']]);
            foreach ($bPays as $payID => $payment) {
                if (!array_key_exists($payID, $pays)) {
                    $pays[$payID] = $payment;
                }
            }
        }

        if (!array_key_exists('varSym', $invoiceData) || empty($invoiceData['varSym'])) {
            $invoiceData['specSym'] = (string) intval(str_replace(
                                    'code:',
                                    '',
                                    $invoiceData['firma']
            ));
        }
        if (array_key_exists('specSym', $invoiceData) && !empty($invoiceData['specSym'])) {
            $sPays = $this->findPayment(['specSym' => $invoiceData['specSym']]);
            $pays = $sPays;
        }


        return $pays;
    }

    /**
     * Vrací neuhrazene faktury odpovídající zadaným parametrům
     *
     * @param array $what
     *
     * @return array
     */
    public function findUnsettledInvoice($what)
    {
        $result = null;
        $this->invoicer->defaultUrlParams['order'] = 'datVyst@A';
        $payments = $this->invoicer->getColumnsFromAbraFlexi(
                [
                    'id',
                    'varSym',
                    'specSym',
                    'buc',
                    'kod',
                    'typDokl',
                    'sumCelkem',
                    'sumCelkemMen',
                    'zbyvaUhradit',
                    'zbyvaUhraditMen',
                    'mena',
                    'stavUhrK',
                    'stitky',
                    'datVyst'],
                ["(" . fb::flexiUrl($what, 'or') . ") AND (stavUhrK is null OR stavUhrK eq 'stavUhr.castUhr') AND storno eq false"],
                'id'
        );
        if ($this->invoicer->lastResponseCode == 200) {
            $result = $payments;
        }

        return $result;
    }

    /**
     * Vrací nesparovane platby odpovídající zadaným parametrům
     *
     * @param array $what
     *
     * @return array
     */
    public function findPayment(array $what = []): ?array
    {
        $result = null;
        $this->banker->defaultUrlParams['order'] = 'datVyst@A';
        $this->banker->defaultUrlParams['limit'] = 0;
        $payments = $this->banker->getColumnsFromAbraFlexi(
                [
                    'id',
                    'varSym',
                    'specSym',
                    'buc',
                    'sumCelkem',
                    'stitky',
                    'datVyst'],
                ["(" . fb::flexiUrl($what, 'or') . ") AND sparovano eq 'false'"],
                'id'
        );
        if ($this->banker->lastResponseCode == 200) {
            $result = $payments;
        }

        return $result;
    }

    /**
     * Zmeni url na link
     *
     * @param string $apiURL
     *
     * @return string
     */
    public static function apiUrlToLink($apiURL)
    {
        return str_replace(
                '.json?limit=0',
                '',
                preg_replace(
                        "#(^|[\n ])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is",
                        "\\1<a href=\"\\2\" target=\"_blank\" rel=\"nofollow\">\\2</a>",
                        $apiURL
                )
        );
    }

    /**
     * Obtain payert Address Code for given bank account number
     *
     * @param string $buc
     * @param string $prefix
     *
     * @return string AbraFlexi address Code
     */
    public static function getPayerForAccount($buc, $prefix = null)
    {
        if (!empty($buc)) {
            $bucer = new \AbraFlexi\RW(
                    null,
                    ['evidence' => 'adresar-bankovni-ucet']
            );
            if (is_null($prefix)) {
                $payerRaw = $bucer->getColumnsFromAbraFlexi(
                        ['firma'],
                        ['buc' => $buc]
                );
            } else {
                $payerRaw = $bucer->getColumnsFromAbraFlexi(
                        ['firma'],
                        ['buc' => $buc, 'smerKod' => fb::code($prefix)]
                );
            }
            return (is_array($payerRaw) && count($payerRaw) && array_key_exists(
                            'firma',
                            $payerRaw[0]
                    ) ) ? $payerRaw[0]['firma'] : null;
        }
        return '';
    }

    /**
     * Vrací faktury odpovídající zadaným parametrům
     *
     * @param array $what
     *
     * @return array
     */
    public function searchInvoices($what)
    {
        $result = null;
        $this->invoicer->defaultUrlParams['order'] = 'datVyst@A';
        $this->invoicer->defaultUrlParams['includes'] = '/faktura-vydana/typDokl';
        $invoices = $this->invoicer->getColumnsFromAbraFlexi([
            'id',
            'kod',
            'stavUhrK',
            'zbyvaUhradit',
            'firma',
            'buc',
            'mena',
            'varSym',
            'specSym',
            'typDokl(typDoklK,kod)',
            'sumCelkem',
            'duzpPuv',
            'stitky',
            'typDokl',
            'datVyst'
                ], $what, 'kod');

        if ($this->invoicer->lastResponseCode == 200) {
            $result = $invoices;
        }
        unset($this->invoicer->defaultUrlParams['includes']);
        return $result;
    }

    /**
     *
     * @param \AbraFlexi\RW $order
     */
    public function notifyOrder($order)
    {
        $mailer = new Mailer(
                $order->getDataValue('kontaktEmail'),
                sprintf(
                        _('Order %s was aproved for execution'),
                        $order->getDataValue('kod')
                )
        );
        $mailer->addItem(_('Dobrý den, vaše objednávka se začíná realizovat.'));
        $orderPdf = $order->downloadInFormat(
                'pdf',
                sys_get_temp_dir() . '/',
                'obchodDokladVystupniOBP$$SUM'
        );
        $mailer->addFile($orderPdf, 'application/pdf');
        $sent = $mailer->send();
        unlink($orderPdf);
        return $sent;
    }
}
