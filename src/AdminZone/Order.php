<?php

/**
 * AdminZone - Objednavka.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2025 Spoje.Net
 */
//declare(strict_types=1);

namespace AdminZone;

use Ease\Html\H2Tag;

/**
 * Description of Order
 *
 * @author vitex
 */
class Order extends AbraFlexiEngine {

    /**
     * Customer
     * @var Customer
     */
    public $customer;

    /**
     * Výchozí evidence objednavky
     * @var string
     */
    public ?string $evidence = 'faktura-vydana';

    /**
     * CartData
     * @var array
     */
    public $cartData = [];

    /**
     * Order Class
     *
     * @param array $cartData
     */
    public function __construct($cartData = []) {
        $this->cartData = $cartData;
        $this->customer = \Ease\Shared::user();
        parent::__construct();
    }

    /**
     * Make Order for customer
     *
     * @param null|array $service
     *
     * @return \AbraFlexi\RW
     */
    public function makeOrder($items = null) {
        if (is_null($items)) {
            $items = $this->cartData;
        }

        $objednavka = new \AbraFlexi\RW(
                null,
                ['evidence' => 'objednavka-prijata']
        );

        $today = date('Y-m-d');
        $objednavka->setDataValue('firma', $this->customer->getAdresar());
        $objednavka->setDataValue('typDokl', 'code:OBP'); //Objednávka přijatá
        $objednavka->setDataValue(
                'uvodTxt',
                _('Please wait while our salesman contact you.')
        );

        $objednavka->setDataValue('zavTxt', '');
        $objednavka->setDataValue('poznam', _('purchase order'));
        $objednavka->setDataValue('typDoklFak', 'code:ZALOHA');
        $objednavka->setDataValue('duzpPuv', $today);
        $objednavka->setDataValue('duzpUcto', $today);
        $objednavka->setDataValue('datUcto', $today);
        $objednavka->setDataValue('stitky', ['SYSTEM', 'API']);

        foreach ($items as $polozka) {
            $polozka['cenik'] = urldecode($polozka['cenik']);
            $objednavka->addArrayToBranch($polozka, 'polozkyObchDokladu');
        }
        //$proforma->debug = true;
        self::updateDocumentCaption($objednavka);
        $objednavka->sync();

        \AbraFlexi\Priloha::addAttachment(
                $objednavka,
                'order.json',
                json_encode($items),
                'application/json'
        );

        return $objednavka;
    }

    /**
     * Make proforma for customer
     *
     * @param array  $items
     * @param string $typDokl override document type to create
     *
     * @return \AbraFlexi\FakturaVydana
     */
    public function makeProforma($items = null, $typDokl = 'ZALOHA') {
        if (is_null($items)) {
            $items = $this->cartData;
        }

        $proforma = new \AbraFlexi\FakturaVydana();

        $today = date('Y-m-d');
        $proforma->setDataValue('firma', $this->customer->getFirma());
        $proforma->setDataValue(
                'typDokl',
                \AbraFlexi\Functions::code($typDokl)
        );
        //        $proforma->setDataValue('uvodTxt', '');
//        $proforma->setDataValue('zavTxt', '');
        $proforma->setDataValue('poznam', _('Proforma'));
        // $proforma->setDataValue('duzpPuv', $today);
        // $proforma->setDataValue('duzpUcto', $today);
        // $proforma->setDataValue('datUcto', $today);
        $proforma->setDataValue('stitky', ['SYSTEM', 'API']);

        foreach ($items as $polozka) {
            if (isset($polozka['typCenyDphK'])) {
                if ($polozka['typCenyDphK'] == 'typCeny.sDph') {
                    $polozka['cenaMJ'] = $polozka['cenaZaklBezDph'];
                    unset($polozka['cenaZaklBezDph']);
                } else {
                    $polozka['cenaMJ'] = $polozka['cenaZaklVcDph'];
                    unset($polozka['cenaZaklVcDph']);
                }
            }
            $polozka['datUcto'] = '';
            $polozka['kopTypUcOp'] = true;
            $proforma->addArrayToBranch($polozka, 'polozkyFaktury');
        }
        //$proforma->debug = true;

        self::updateDocumentCaption($proforma);

        try {
            $proforma->unsetDataValue('kod');
            $proforma->unsetDataValue('id');
            if ($proforma->sync()) {
                \AbraFlexi\Priloha::addAttachment(
                        $proforma,
                        'order.json',
                        json_encode($items),
                        'application/json'
                );
            }
        } catch (\Exception $exc) {
            $this->addStatusMessage(_('Order failed'), 'true');
        }

        return $proforma;
    }

    /**
     * Make Invoice for customer
     *
     * @param null|array $items
     *
     * @return \AbraFlexi\FakturaVydana
     */
    public function makeInvoice($items = null) {
        if (is_null($items)) {
            $items = $this->cartData;
        }

        $invoice = new \AbraFlexi\FakturaVydana();

        $today = date('Y-m-d');
        $invoice->setDataValue('firma', $this->customer->getAdresar());
        $invoice->setDataValue('typDokl', 'code:FAKTURA');
        $invoice->setDataValue('uvodTxt', '');

        $invoice->setDataValue('zavTxt', '');
        $invoice->setDataValue('poznam', _('Invoice'));
        $invoice->setDataValue('duzpPuv', $today);
        $invoice->setDataValue('duzpUcto', $today);
        $invoice->setDataValue('datUcto', $today);
        $invoice->setDataValue('stitky', ['SYSTEM', 'API']);

        foreach ($items as $polozka) {
            $invoice->addArrayToBranch($polozka, 'polozkyFaktury');
        }

        self::updateDocumentCaption($invoice);
        $invoice->sync();
        \AbraFlexi\Priloha::addAttachment(
                $invoice,
                'order.json',
                json_encode($items),
                'application/json'
        );
        return $invoice;
    }

    /**
     * Turn order into invoice etc.
     *
     * @return array
     */
    public function finishOrder() {
        $invoiceTypeItems = [];
        $proformaTypeItems = [];
        $orderTypeItems = [];
        $notbindingTypeItems = [];
        $projectTypeItems = [];

        $result = [];

        $productor = new \AbraFlexi\Cenik();

        $items = $this->cartData;
        foreach ($items as $itemId => $item) {
            if (!empty($item['cenik'])) {
                $stitkyRaw = $productor->getColumnsFromAbraFlexi(
                        ['stitky'],
                        ['kod' => \AbraFlexi\Functions::uncode($item['cenik'])]
                );
            } else {
                $stitkyRaw = [['stitky' => '']];
            }
            if (strstr($stitkyRaw[0]['stitky'], 'NEUPOMINAT')) {
                $notbindingTypeItems[$itemId] = $item;
            } else {
                switch (array_key_exists('typZasobyK', $item) ? $item['typZasobyK'] : 'typZasoby.projekt') {
                    case 'typZasoby.sluzba':
                        $orderTypeItems[$itemId] = $item;
                        break;

                    case 'typZasoby.zbozi':
                    case 'typZasoby.material':
                    case 'typZasoby.poplatek':
                        $proformaTypeItems[$itemId] = $item;
                        break;
                    default:
                        $this->addStatusMessage(
                                sprintf(
                                        _('Unhandled type of item'),
                                        $item['typZasobyK']
                                ),
                                'error'
                        );
                        //typZasoby.nedvyroba
                        //typZasoby.polotovar
                        //typZasoby.vyrobek
                        //typZasoby.zvire
                        break;
                }
            }
        }

        if (count($invoiceTypeItems)) {
            $result['invoice'] = $this->makeInvoice($invoiceTypeItems);
        }
        if (count($proformaTypeItems)) {
            $result['proforma'] = $this->makeProforma($proformaTypeItems);
        }
        if (count($notbindingTypeItems)) {
            $result['notbinding'] = $this->makeProforma(
                    $notbindingTypeItems,
                    'ZALOHA_OBJEDNAVKA'
            );
        }
        if (count($orderTypeItems)) {
            $result['order'] = $this->makeOrder($orderTypeItems);
        }

        return $result;
    }

    /**
     * Update invoice caption depending on its contents
     *
     * @param \AbraFlexi\FakturaVydana $document
     */
    public static function updateDocumentCaption($document) {
        $docNames = empty($document->getDataValue('popis')) ? [] : [$document->getDataValue('popis')];
        $items = $document->getDataValue('polozkyFaktury');
        if (!empty($items)) {
            foreach ($items as $item) {
                $docNames[] = $item['nazev'];
            }
        }
        $document->setDataValue('popis', implode(', ', $docNames));
    }

    /**
     *
     *
     * @param array $result
     *
     * @param string $to recipient
     */
    public function sendNotify($result, $to) {
        if (count($result)) {
            if (array_key_exists('HTTP_HOST', $_SERVER)) {
                $dokumentUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname(\Ease\WebPage::getUri()) . 'document.php';
            } else {
                $dokumentUrl = \Ease\Shared::cfg('SYSTEM_URL') . '/document.php';
            }

            $mail = new Mailer($to, _('Orders to check'));
            foreach ($result as $document) {
                $mail->addItem(new H2Tag(strval($document->getDataValue('firma'))));

                $dokumentUrl .= '?id=' . $document->getRecordIdent() . '&embed=true&evidence=' . $document->getEvidence();
                $mail->addItem($document->getDataValue('popis'));
                $mail->addItem(new \Ease\Html\DivTag(
                                new \Ease\Html\ATag(
                                        $dokumentUrl,
                                        $document->getRecordIdent()
                                )
                ));

                $mail->addItem(new \Ease\Html\DivTag($document->getDataValue('nazev') . ' ' . $document->getDataValue('sumCelkem') . ' ' . \AbraFlexi\Functions::uncode($document->getDataValue('mena'))));

                // Add attachment with PDF
                $mail->addFile($document->downloadInFormat('pdf', '/tmp/'), \AbraFlexi\Formats::$formats['PDF']['content-type']);
            }
            return $mail->send();
        }
    }
}
