<?php

/**
 * AdminZone - Reminder class
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2017-2024 Spoje.Net
 */

namespace AdminZone;

use AbraFlexi\Bricks\Customer;

/**
 * Description of Upominka
 *
 * @deprecated since version 1.4 use abraflexi-reminder package
 *
 * @author vitex
 */
class Upominac extends \Ease\Sand
{
    use \Ease\Logger\Logging;
    use \Ease\RecordKey;

    /**
     *
     * @var Customer
     */
    public $customer = null;

    /**
     * Invoice
     *
     * @var \AbraFlexi\FakturaVydana
     */
    public $invoicer = null;

    /**
     * Reminder
     *
     * @param array $init
     * @param array $options
     */
    public function __construct($init = null, $options = array())
    {
        if (array_key_exists('customer', $options)) {
            $this->customer = $options['customer'];
        } else {
            $this->customer = new Customer();
        }
        $this->invoicer = new \AbraFlexi\FakturaVydana();
    }

    /**
     * Obtain customer debths Array
     *
     * @return Customer
     */
    public function getDebths()
    {
        $allDebts = [];
        $debtCount = 0;
        $this->addStatusMessage(_('Collecting debts'), 'debug');

        $debtsRaw = $this->getAllDebts(\Ease\Shared::cfg('REMINDER_MINIMUM') ? floatval(\Ease\Shared::cfg('REMINDER_MINIMUM')) : 20.0, ['limit' => 0]);

        $this->addStatusMessage(sprintf(
            _('%s invoices waiting for settle found'),
            count($debtsRaw)
        ));

//        foreach ($debtsRaw as $kod => $debtInfo) {
//
//            if ($this->fixMatching($debtInfo) || strstr($debtInfo['stitky'],
//                    'NEUPOMINAT')) {
//                unset($debtsRaw);
//            }
//        }

        if (empty($debtsRaw)) {
            $this->addStatusMessage(
                _('Such a lucky day! All settled'),
                'success'
            );
        } else {
            $debtersRaw = array_keys(\Ease\Functions::reindexArrayBy($debtsRaw, 'firma'));
            foreach ($debtersRaw as $pos => $code) {
                if (strlen($code)) {
                    $debtersRaw[$pos] = \AbraFlexi\Functions::uncode($code);
                }
            }

            $debters = $this->getDebtersInfo($debtersRaw);

            foreach ($debters as $clientCode => $clientIDs) {
                foreach ($debtsRaw as $debtCode => $debtInfo) {
                    if (\AbraFlexi\Functions::uncode($debtInfo['firma']) == $clientCode) {
                        if (strstr($debtInfo['stitky'], 'NEUPOMINAT')) {
                            $this->addStatusMessage(sprintf(
                                _('Do not remind invoice %s') . ' Label NEUPOMINAT ',
                                $debtCode
                            ));
                        } else {
                            $allDebts[$clientCode][$debtCode] = $debtInfo;
                            $debtCount++;
                        }
                    }
                }
            }
            $this->addStatusMessage(sprintf(_('%s Debts Found'), $debtCount));
        }

        if (!empty($debters)) {
            $this->enableCustomers($debters);
        }
        return $allDebts;
    }

    public function getDebtersInfo($debtersRaw)
    {
        foreach ($debtersRaw as $debter) {
            if ($debter) {
                $debters[$debter] = $this->customer->getAdresar()->getColumnsFromAbraFlexi([
                            'nazev',
                            'stitky'], ['kod' => $debter], 'kod')[$debter];
            }
        }

        return $debters;
    }

    /**
     * Fix Matching error
     *
     * @param array $debtInfo
     * @deprecated since version 1
     *
     * @return boolean Match Fixed
     */
    public function fixMatching($debtInfo)
    {
        $result = false;
        if (\AbraFlexi\Functions::uncode($debtInfo['typDokl']) == 'FAKTURA') {
            $fixer = new ParovacFaktur();
            $invoicesFound = $fixer->searchInvoices(['typDokl' => \AbraFlexi\Functions::code('ZALOHA'),
                'varSym' => $debtInfo['varSym']]);

            if ($invoicesFound && count($invoicesFound) == 1) {
                $zaloha = new \AbraFlexi\FakturaVydana(current($invoicesFound));
                if ($zaloha->getDataValue('stavUhrK') == 'stavUhr.uhrazeno') {
                    $fixer->addStatusMessage('Fixing...');

                    $faktura2 = new \AbraFlexi\FakturaVydana(\AbraFlexi\Functions::code($debtInfo['kod']));
                    $kod = $faktura2->getDataValue('kod');
                    $faktura2->dataReset();
                    $faktura2->setDataValue('id', 'code:' . $kod);
                    $faktura2->setDataValue('typDokl', 'code:FAKTURA');

                    if ($zaloha->getDataValue('typDokl') == 'code:ZALOHA') {
                        $faktura2->setDataValue('clenDph', 'code:01-02');
                    }

                    $result = $faktura2->odpocetZalohy($zaloha);
                    if ($faktura2->lastResponseCode == 201) {
                        $success = 2;
                        $zaloha->addStatusMessage(
                            sprintf(
                                _('Faktura #%s byla sparovana se zálohou #%s'),
                                $kod,
                                \AbraFlexi\Functions::uncode($zaloha)
                            ),
                            'success'
                        );
                        $faktura2->insertToAbraFlexi(['id' => (string) $faktura2,
                            'stavMailK' => 'stavMail.odeslat']);
                    } else {
                        $success = -1;
                        $zaloha->addStatusMessage(
                            sprintf(
                                _('Faktura #%s nebyla sparovana se zalohou #%s '),
                                $kod,
                                \AbraFlexi\Functions::uncode($zaloha)
                            ),
                            'error'
                        );
                    }
                }
            }
        }

        return $result;
    }

    public function getInvoicesStatus($conditions = ['typDokl' => 'code:FAKTURA'])
    {
        $status = [];
        foreach (
            $this->invoicer->getColumnsFromAbraFlexi(['firma',
            'kod', 'zbyvaUhradit',
            'sumCelkem'], $conditions) as $invoice
        ) {
            if (!array_key_exists($invoice['firma']->value, $status)) {
                $status[$invoice['firma']->value]['name'] = empty($invoice['firma']->showAs) ? $invoice['firma']->value : $invoice['firma']->showAs;
                $status[$invoice['firma']->value]['balance'] = 0;
            }
            if (floatval($invoice['zbyvaUhradit'])) {
                $status[$invoice['firma']->value]['invoice'][$invoice['kod']] = -floatval($invoice['zbyvaUhradit']);
                $status[$invoice['firma']->value]['balance'] -= floatval($invoice['zbyvaUhradit']);
            } else {
                $status[$invoice['firma']->value]['invoice'][$invoice['kod']] = floatval($invoice['sumCelkem']);
                $status[$invoice['firma']->value]['balance'] += floatval($invoice['sumCelkem']);
            }
        }
        return $status;
    }

    /**
     * Enable customer
     *
     * @param array    $clientInfo    AbraFlexi Address Data
     *
     * @return boolean Customer connect status
     */
    function enableCustomer($clientInfo, $temporary = false)
    {
        $result = true;
        $stitky = $clientInfo['stitky'];
        if (
            strstr($stitky, 'UPOMINKA') || strstr($stitky, 'NEPLATIC') || strstr(
                $stitky,
                'ODPOJENO'
            )
        ) {
            $this->customer->getAdresar()->loadFromAbraFlexi(\AbraFlexi\Functions::code($clientInfo['kod']));
            $newStitky = \AbraFlexi\Stitek::listToArray($stitky);

            $labelsRemoved = [];

            if ($temporary === false) {
                \Ease\Functions::divDataArray($newStitky, $labelsRemoved, 'UPOMINKA1');
                \Ease\Functions::divDataArray($newStitky, $labelsRemoved, 'UPOMINKA2');
            } else {
                $this->addStatusMessage('Client manualy unblocked - additonal 7 days to pay', 'warning');
            }
            \Ease\Functions::divDataArray($newStitky, $labelsRemoved, 'UPOMINKA3');
            \Ease\Functions::divDataArray($newStitky, $labelsRemoved, 'ODPOJENO');
            \Ease\Functions::divDataArray($newStitky, $labelsRemoved, 'NEPLATIC');

            unset($this->customer->getAdresar()->data['external-ids']);

            if (
                $this->customer->getAdresar()->insertToAbraFlexi(['id' => \AbraFlexi\Functions::code($clientInfo['kod']),
                        'stitky@removeAll' => 'true',
                        'stitky' => $newStitky])
            ) {
                $this->customer->getAdresar()->addStatusMessage(
                    sprintf(
                        _('Clear Remind labels %s for %s'),
                        implode(',', $labelsRemoved),
                        $clientInfo['kod']
                    ),
                    'success'
                );
            } else {
                $this->customer->getAdresar()->addStatusMessage(
                    sprintf(
                        _('Clear Remind labels %s for %s failed'),
                        implode(',', $labelsRemoved),
                        $clientInfo['kod']
                    ),
                    'error'
                );
                $result = false;
            }
        }
        return $result;
    }

    /**
     *
     * @param array $debters
     */
    public function enableCustomers($debters)
    {
        $done = 0;
        $allClientsWithLabels = $this->customer->getCustomerList(["stitky='code:UPOMINKA1' or stitky='code:UPOMINKA2' or stitky='code:UPOMINKA3' or stitky='code:NEPLATIC' or stitky='code:ODPOJENO'", 'limit' => 0]);
        foreach ($allClientsWithLabels as $settleClient) {
            if (array_key_exists($settleClient['kod'], $debters)) {
                continue;
            }
            if ($this->enableCustomer($settleClient)) {
                $done++;
            }
        }
        return $done;
    }

    /**
     * Return all unsettled documents in evidence
     *
     * @param string $evidence   override default evidence
     * @param float  $minimum    skip debt less than
     * @param array  $conditions
     *
     * @return array
     */
    public function getEvidenceDebts(
        $evidence = null,
        $minimum = 20.0,
        $conditions = []
    ) {
        if ($evidence) {
            $evBackup = $this->invoicer->getEvidence();
            $this->invoicer->setEvidence($evidence);
        } else {
            $evBackup = false;
        }

        $result = [];
        $this->invoicer->defaultUrlParams['order'] = 'datVyst@A';
        $this->invoicer->defaultUrlParams['limit'] = 0;
        $invoices = $this->invoicer->getColumnsFromAbraFlexi(
            [
            'id',
            'kod',
            'stavUhrK',
            'firma',
            'buc',
            'popis',
            'varSym',
            'specSym',
            'sumCelkem',
            'sumCelkemMen',
            'duzpPuv',
            'typDokl(typDoklK,kod)',
            'datSplat',
            'datUp1',
            'datUp2',
            'datSmir',
            'zbyvaUhradit',
            'zbyvaUhraditMen',
            'mena',
            'zamekK',
            'stitky',
            'datVyst',
            'poznam',
            'stavMailK'
                ],
            array_merge(
                ["datSplat lte '" . \AbraFlexi\RW::dateToFlexiDate(new \DateTime()) . "' AND (stavUhrK is null OR stavUhrK eq 'stavUhr.castUhr') AND storno eq false"],
                $conditions
            ),
            'kod'
        );

        if ($this->invoicer->lastResponseCode == 200) {
            $skiplist = [];
            if (defined('SKIPLIST')) {
                $skiplist = \AbraFlexi\Stitek::listToArray(constant('SKIPLIST'));
            }

            $evidenceUsed = $this->invoicer->getEvidence();
            foreach ($invoices as $invoiceId => $invoiceData) {
                $invoiceData['evidence'] = $evidenceUsed;
                if (
                    array_key_exists(
                        \AbraFlexi\Functions::uncode($invoiceData['typDokl']),
                        $skiplist
                    )
                ) {
                    continue;
                }

                if (($invoiceData['typDokl'] != 'code:DOBR') && ($invoiceData['zbyvaUhradit'] < $minimum)) {
                    $this->invoicer->setData($invoiceData);
                    $this->invoicer->addStatusMessage(sprintf(
                        _('Debt %s is less than %s Kč; skipping %s %s: '),
                        $invoiceData['zbyvaUhradit'],
                        $minimum,
                        \AbraFlexi\Functions::uncode($invoiceData['typDokl']),
                        $invoiceData['kod']
                    ));
                    continue;
                }
                $result[$invoiceId] = $invoiceData;
            }
        }

        if ($evBackup) {
            $this->invoicer->setEvidence($evBackup);
        }

        return $result;
    }

    /**
     * Get All debts
     *
     * @param float $minimum
     * @param array $conditions additional conditions
     *
     * @return array
     */
    public function getAllDebts($minimum = 20.0, $conditions = [])
    {
        $debts = $this->getEvidenceDebts(
            'faktura-vydana',
            $minimum,
            $conditions
        );
        $debts2 = $this->getEvidenceDebts('pohledavka', $minimum, $conditions);
        return \Ease\Functions::reindexArrayBy(array_merge(
            is_null($debts) ? [] : $debts,
            is_null($debts2) ? [] : $debts2
        ), 'kod');
    }

    /**
     * Process All Debts of All Customers
     *
     * @return int All Debts count
     */
    public function processAllDebts($allDebths)
    {
        $dasboardIDs = ['dashboard' => [], 'disconnect' => []];
        
        $this->customer->getAdresar()->defaultUrlParams['detail'] = 'full';
        $this->addStatusMessage(sprintf(
            _('%d clients to remind process'),
            count($allDebths)
        ));
        $counter = 0;
        foreach ($allDebths as $clientCodeRaw => $debts) {
            $counter++;
            $clientCode = \AbraFlexi\Functions::code($clientCodeRaw);

            $this->customer->getAdresar()->dataReset();
            $this->customer->getAdresar()->loadFromAbraFlexi($clientCode);
            $stitky = $this->customer->getAdresar()->getDataValue('stitky');
            $stitkyArr = \AbraFlexi\Stitek::listToArray($stitky);
            array_multisort($stitkyArr);
            $this->addStatusMessage(sprintf(
                _('(%d / %d) %s %s %s'),
                $counter,
                count($allDebths),
                $this->customer->getAdresar()->getDataValue('kod'),
                $this->customer->getAdresar()->getDataValue('nazev'),
                empty($stitkyArr) ? '' : \implode(',', $stitkyArr)
            ), 'debug');

            $zewlScore = $this->processUserDebts($clientCode, $debts);

            $clientInfo = $this->customer->getAdresar()->getData();
            $remindAs = $this->prepareDashboard($clientInfo, $zewlScore);
            $dasboardIDs[$remindAs][$clientCode] = 'code:' . $clientCodeRaw;
        }
        return $counter;
    }

    /**
     * Prepare remind to Sent
     *
     * @param array  $clientInfo  AbraFlexi address Data
     * @param int    $score  Zewl Score
     * @param string $stitky Labels divided by columns
     *
     * @return string Operation code connect|dashboard|disconnect
     */
    function prepareDashboard($clientInfo, $score)
    {
        $operation = 'connect';
        $stitky = $clientInfo['stitky'];
        switch ($score) {
            case 0:
                $this->enableCustomer($clientInfo);
                break;
            case 1:
                break;
            case 2:
                if (
                    !strstr($stitky, 'VIP') && !strstr($stitky, 'NODASHBOARD') && !strstr(
                        $stitky,
                        'PROFI'
                    ) && !strstr($stitky, 'ZAINTERESOVANI')
                ) {
                    $this->customer->getAdresar()->insertToAbraFlexi(['id' => \AbraFlexi\Functions::code($clientInfo['kod']),
                        'stitky' => 'NEPLATIC']);
                    $operation = 'dashboard';
                    $this->customer->getAdresar()->addStatusMessage(sprintf(
                        _('Dashboard for: %d '),
                        $clientInfo['kod']
                    ));
                }
                break;
            case 3:
                if (
                    !strstr($stitky, 'VIP') && !strstr($stitky, 'NODISCONNECT') && !strstr(
                        $stitky,
                        'NEODPOJOVAT'
                    ) && !strstr($stitky, 'PROFI')
                ) {
                    $this->customer->getAdresar()->insertToAbraFlexi(['id' => \AbraFlexi\Functions::code($clientInfo['kod']),
                        'stitky' => 'ODPOJENO']);
                    $operation = 'disconnect';
                    $this->customer->getAdresar()->addStatusMessage(sprintf(
                        _('Disconnect for: %d '),
                        $clientInfo['kod']
                    ), 'info');
                }
                break;
        }
        return $operation;
    }

    /**
     * Odešle upomínku
     *
     * @param type $order
     * @param type $subject
     * @param type $message
     */
    public function sendOrderRemind($order, $subject, $message)
    {

        $defaultLocale = 'cs_CZ';
        setlocale(LC_ALL, $defaultLocale);
        putenv("LC_ALL=$defaultLocale");

        $body = new \Ease\Container();

        $to = Mailer::getNotificationEmailAddres(new \AbraFlexi\Adresar($order->getDataValue('firma')));

        $customerName = $order->getDataValue('firma')->showAs;
        if (empty($customerName)) {
            $customerName = \AbraFlexi\Functions::uncode($order->getDataValue('firma'));
        }

        $mailer = new Mailer($to, $subject);
        $mailer->addItem(new \AbraFlexi\ui\CompanyLogo(['align' => 'right', 'id' => 'companylogo',
                    'height' => '50', 'title' => _('Company logo')]));

        $prober = new \AbraFlexi\Company();
        $infoRaw = $prober->getFlexiData();
        if (count($infoRaw) && !array_key_exists('success', $infoRaw)) {
            $info = \Ease\Functions::reindexArrayBy($infoRaw, 'dbNazev');
            $myCompany = $prober->getCompany();
            if (array_key_exists($myCompany, $info)) {
                $mailer->addItem(new \Ease\Html\H2Tag($info[$myCompany]['nazev']));
            }
        }

        $mailer->addItem(new \Ease\Html\DivTag(sprintf(
            _('Dear customer %s,'),
            $customerName
        )));
        $mailer->addItem("\n<br>");
        $mailer->addItem(new \Ease\Html\DivTag($message));
        $mailer->addItem("\n<br>");

        $body->addItem(new \Ease\Html\DivTag(_('With greetings')));

        $mailer->addItem("\n<br>");

        $body->addItem(nl2br(PotvrzeniPrijetiUhrady::$signature));

        $mailer->addFile(
            $order->downloadInFormat('pdf', '/tmp/'),
            \AbraFlexi\Formats::$formats['PDF']['content-type']
        );
        $mailer->addFile(
            $order->downloadInFormat('isdocx', '/tmp/'),
            \AbraFlexi\Formats::$formats['ISDOCx']['content-type']
        );

        $this->addStatusMessage(
            _('Order notification sending'),
            $mailer->send() ? 'success' : 'warning'
        );

    }

    /**
     * Process Customer debts
     *
     * @param int   $cid         AbraFlexi Address (Customer) ID
     * @param array $clientDebts Array provided by customer::getCustomerDebts()
     *
     * @return int max debt score 1: 0-7 days 1: 8-14 days 3: 15 days and more
     */
    public function processUserDebts($cid, $clientDebts)
    {





        $zewlScore = 0;
        $stitky = $this->customer->getAdresar()->getDataValue('stitky');
        $ddifs = [];
        $invoicesToSave = [];
        $invoicesToLock = [];
        $overall = [];
        foreach ($clientDebts as $did => $debt) {
            $dniPoSplatnosti = \AbraFlexi\FakturaVydana::overdueDays((string) $debt['datSplat']);
            $invoiceCode = \AbraFlexi\Functions::code($did);
            $did = intval($debt['id']);
            $overall[\AbraFlexi\Functions::uncode($invoiceCode)] = $dniPoSplatnosti;
            if (\AbraFlexi\Functions::uncode($debt['typDokl']) == 'DOBR') {
                $this->addStatusMessage(
                    'Skiping CreditNote ' . $debt['kod'],
                    'debug'
                );
                continue;
            }

            if (\AbraFlexi\Functions::uncode($debt['typDokl']) == 'ZALOHA_OBJEDNAVKA') {
                $this->invoicer->setData($debt);
                if (array_key_exists('evidence', $debt) && !empty($debt['evidence'])) {
                    $this->invoicer->setEvidence($debt['evidence']);
                }
                $systemLink = new \Ease\Html\ATag(
                    AbraFlexiEngine::systemUrl($this->invoicer),
                    $invoiceCode
                );
                switch ($dniPoSplatnosti) {
                    case -7:
                    case 2:
                        if ($debt['stavMailK'] == 'stavMail.odeslano') {
                            $this->sendOrderRemind(
                                $this->invoicer->getRecordIdent(),
                                sprintf(
                                    _('Order %s is waiting to your payment'),
                                    $invoiceCode
                                ),
                                sprintf(
                                    _('We remind you of your order %s.'),
                                    $systemLink
                                )
                            );
                        }
                        break;
                    case 8:
                        if ($debt['stavMailK'] == 'stavMail.odeslano') {
                            $this->sendOrderRemind(
                                $this->invoicer,
                                sprintf(
                                    _('Order %s will be cancelled'),
                                    $invoiceCode
                                ),
                                sprintf(
                                    _('Please pay for your order %s. If not, this will be canceled next week.'),
                                    $systemLink
                                )
                            );
                        }
                        break;
                    default:
                        if ($dniPoSplatnosti > 14) {
                            $this->invoicer->performAction('storno');
                            $stornoSubject = sprintf(
                                _('Order %s was canceled'),
                                $invoiceCode,
                                'info'
                            );

                            if ($this->invoicer->lastResponseCode == 201) {
                                $this->addStatusMessage(
                                    $stornoSubject,
                                    'success'
                                );
                                if ($debt['stavMailK'] == 'stavMail.odeslano') {
                                    $this->sendOrderRemind(
                                        $this->invoicer,
                                        $stornoSubject,
                                        sprintf(
                                            _('Your order %s was canceled.'),
                                            $invoiceCode
                                        )
                                    );
                                } else {
                                    $mailer = new Mailer(
                                        constant('SEND_INFO_TO'),
                                        $stornoSubject,
                                        $systemLink
                                    );
                                    $mailer->send();
                                }
                            } else {
                                $this->addStatusMessage(sprintf(
                                    _('Order %s was not canceled'),
                                    $invoiceCode
                                ), 'error');
                            }
                        }
                        break;
                }
                continue; //
            }

            switch ($debt['zamekK']) {
                case 'zamek.zamceno':
                    $this->invoicer->dataReset();
                    $this->invoicer->setMyKey($did);
                    try {
                        $unlock = $this->invoicer->performAction('unlock', 'int');
                    } catch (\Exception $exc) {
                        $unlock = 'false';
                    }

                    if ($unlock == 'false') {
                        $this->addStatusMessage(
                            sprintf(_('Invoice %s locked: skipping process'), $this->invoicer->getRecordIdent()),
                            'warning'
                        );
                        break;
                    }
                    $invoicesToLock[$debt['id']] = ['id' => $did];
                case 'zamek.otevreno':
                default:
                    $invoicesToSave[$debt['id']] = ['id' => $did];
                    $invoicesToSave[$debt['id']]['evidence'] = $debt['evidence'];
                    $ddifs[$debt['id']] = $dniPoSplatnosti;

                    if (($dniPoSplatnosti <= 7) && ($dniPoSplatnosti >= 1)) {
                        $zewlScore = self::maxScore($zewlScore, 1);
                    } else {
                        if (($dniPoSplatnosti > 7 ) && ($dniPoSplatnosti <= 14)) {
                            $zewlScore = self::maxScore($zewlScore, 2);
                        } else {
                            if (($dniPoSplatnosti > 14) && ($dniPoSplatnosti <= 21 )) {
                                $zewlScore = self::maxScore($zewlScore, 3);
                            } elseif (($dniPoSplatnosti > 21) && ($dniPoSplatnosti <= 28 )) {
                                $zewlScore = self::maxScore($zewlScore, 4);
                            } else {
                                $zewlScore = self::maxScore($zewlScore, 5);
                            }
                        }
                    }

                    break;
            }
        }

        if ($zewlScore == 3 && !strstr($stitky, 'UPOMINKA2')) {
            $zewlScore = 2;
        }

        if (!strstr($stitky, 'UPOMINKA1')) {
            $zewlScore = 1;
        }
        if ($zewlScore > 0 && (array_sum($ddifs) > 0) && count($invoicesToSave)) {
            if (!strstr($stitky, 'UPOMINKA' . $zewlScore)) {
                if (!strstr($stitky, 'NEUPOMINAT')) {
                    if ($this->posliUpominku($zewlScore, $cid, $clientDebts)) {
                        foreach ($invoicesToSave as $invoiceID => $invoiceData) {
                            switch ($zewlScore) {
                                case 1:
                                    $colname = 'datUp1';
                                    break;
                                case 2:
                                    $colname = 'datUp2';
                                    break;
                                case 3:
                                    $colname = 'datSmir';
                                    break;
                                default:
                                    $colname = 'poznam';
                                    break;
                            }
                            if ($colname == 'poznam') {
                                $invoiceData[$colname] = 'Inventarizace:' . \AbraFlexi\RW::timestampToFlexiDate(time());
                            } else {
                                $invoiceData[$colname] = \AbraFlexi\RW::timestampToFlexiDate(time());
                            }

                            $this->invoicer->setEvidence($invoiceData['evidence']);
                            if ($this->invoicer->insertToAbraFlexi($invoiceData)) {
                                $this->addStatusMessage(sprintf(
                                    _('Invoice %s remind %s date saved'),
                                    $invoiceID,
                                    $colname
                                ), 'debug');
                            } else {
                                $this->addStatusMessage(sprintf(
                                    _('Invoice %s remind %s date save failed'),
                                    $invoiceID,
                                    $colname
                                ), 'error');
                            }
                        }
                    }
                } else {
                    $this->invoicer->addStatusMessage(
                        _('Remind send disbled'),
                        'debug'
                    );
                }
            } else {
                $this->invoicer->addStatusMessage(sprintf(
                    _('Remind %d already sent'),
                    $zewlScore
                ), 'debug');
            }
        } else {
            $this->customer->getAdresar()->addStatusMessage(
                _('No debts to remind'),
                'debug'
            );
        }

        if (count($invoicesToLock)) {
            foreach ($invoicesToLock as $invoiceID => $invoiceData) {
                $this->invoicer->dataReset();
                $this->invoicer->setMyKey($invoiceID);
                $lock = $this->invoicer->performAction('lock', 'int');
                if ($lock == 'true') {
                    $this->addStatusMessage(sprintf(
                        _('Invoice %s locked again'),
                        $invoiceID
                    ), 'info');
                } else {
                    $this->addStatusMessage(sprintf(
                        _('Invoice %s locking failed'),
                        $invoiceID
                    ), 'error');
                }
            }
        }

        $this->customer->getAdresar()->addStatusMessage(
            'x' . count($clientDebts) . ' Zewl: ' . $zewlScore . ' ' . constant('SYSTEM_URL') . '/customerorders.php?address=' . $cid,
            'debug'
        );
        $this->addStatusMessage(str_replace(
            ['{', '}', '"', '\\'],
            '',
            json_encode($overall)
        ), 'debug');

        return $zewlScore;
    }

    /**
     * Obtain Customer "Score"
     *
     * @param int $addressID AbraFlexi user ID
     *
     * @return int ZewlScore
     */
    public function getCustomerScore($addressID)
    {
        $score = 0;
        // Load customer data for the given address ID
        $this->customer->getAdresar()->loadFromAbraFlexi($addressID);
        $this->customer->setFirma($addressID);
        $debts = $this->customer->getCustomerDebts();
        $stitkyRaw = $this->customer->getAdresar()->getColumnsFromAbraFlexi(
            ['stitky'],
            ['id' => $addressID]
        );
        $stitky = $stitkyRaw[0]['stitky'];
        if (!empty($debts)) {
            foreach ($debts as $did => $debt) {
                if (\AbraFlexi\Functions::uncode($debt['typDokl']->value) == 'ZALOHA_OBJEDNAVKA') {
                    continue;
                }

                $ddiff = \AbraFlexi\FakturaVydana::overdueDays((string) $debt['datSplat']);

                if (($ddiff <= 7) && ($ddiff >= 1)) {
                    $score = self::maxScore($score, 1);
                } else {
                    if (($ddiff > 7 ) && ($ddiff <= 14)) {
                        $score = self::maxScore($score, 2);
                    } else {
                        if ($ddiff > 14) {
                            $score = self::maxScore($score, 3);
                        }
                    }
                }
            }
        }
        if ($score == 3 && !strstr($stitky, 'UPOMINKA2')) {
            $score = 2;
        }

        if (!strstr($stitky, 'UPOMINKA1') && !empty($debts)) {
            $score = 1;
        }

        return $score;
    }

    /**
     * Get Last Sent Inventarization days Day
     *
     * @param array $clientDebts
     *
     * @return int
     */
    public function getDaysToLastInventarization($clientDebts)
    {
        $days = 0;
        $daysToLastInvent = [];

        foreach ($clientDebts as $did => $debt) {
            if (strstr($debt['poznam'], 'Inventarizace:')) {
                foreach (explode("\n", $debt['poznam']) as $invRow) {
                    if (strstr($invRow, 'Inventarizace:')) {
                        $daysToLastInvent[] = \AbraFlexi\FakturaVydana::overdueDays(new \DateTime(str_replace(
                            'Inventarizace:',
                            '',
                            $invRow
                        )));
                    }
                }
            } else {
                $daysToLastInvent[] = \AbraFlexi\FakturaVydana::overdueDays(empty((string) $debt['datSmir']) ? ( empty((string) $debt['datUp2']) ? ( empty((string) $debt['datUp1']) ? $debt['datVyst'] : $debt['datUp1'] ) : $debt['datUp2'] ) : $debt['datSmir']);
            }
        }

        return min($daysToLastInvent);
    }

    /**
     * Send remind
     *
     * @param int    $score       ZewlScore
     * @param string $cid         AbraFlexi address (customer) ID/Code
     * @param array  $clientDebts Array provided by customer::getCustomerDebts()
     *
     * @return boolean
     */
    public function posliUpominku($score, $cid, $clientDebts)
    {

        $result = false;
        $upominka = new Upominka();
        switch ($score) {
            case 1:
                $upominka->loadTemplate('prvniUpominka');
                break;
            case 2:
                $upominka->loadTemplate('druhaUpominka');
                break;
            case 3:
                $upominka->loadTemplate('pokusOSmir');
                break;
            default:
                $lastInvDays = $this->getDaysToLastInventarization($clientDebts);
                if ($lastInvDays < 14) {
                    $this->addStatusMessage(
                        sprintf(
                            _('Last  remind / inventarization for %s send before %d days; skipping'),
                            \AbraFlexi\Functions::uncode($cid),
                            $lastInvDays
                        ),
                        'debug'
                    );
                    return false;
                }

                $upominka->loadTemplate('inventarizace');
                break;
        }
        if ($upominka->compile($cid, $clientDebts)) {
            $result = $upominka->send();
        } else {
            $this->customer->getAdresar()->addStatusMessage(
                _('UPOMINKA NEBYLA ODESLANA na mail !!!!'),
                'warning'
            );
        }
        if ($score < 4) {
            $this->customer->getAdresar()->sync(['id' => $cid, 'stitky' => 'UPOMINKA' . $score]);
//            $this->customer->getAdresar()->loadFromAbraFlexi($cid);
            $this->customer->getAdresar()->addStatusMessage(sprintf(
                _('Set Label %s '),
                'UPOMINKA' . $score
            ));
        }
        $this->customer->getAdresar()->setMyKey($cid);
        return $result;
    }

    /**
     * Overdue group
     *
     * @param int $score current score value
     * @param int $level current level
     *
     * @return int max of all levels processed
     */
    private static function maxScore($score, $level)
    {
        if ($level > $score) {
            $score = $level;
        }
        return $score;
    }

    /**
     *
     * @param array $conditions
     *
     * @return array
     */
    public function getContracts($conditions = [])
    {
        $contractor = new \AbraFlexi\Smlouva();
        $contracts = [];
        $conditions['relations'] = 'prilohy';
        foreach ($contractor->getColumnsFromAbraFlexi('*', $conditions, 'kod') as $contract) {
            $contracts[$contract['firma']][$contract['kod']] = $contract;
        }
        return $contracts;
    }

    public function getUnsignedContracts($contracts)
    {
        foreach ($contracts as $contractCode => $contract) {
            if (!empty($contract['datumPodepsani'])) {
                unset($contracts[$contractCode]);
            }
        }
        return $contracts;
    }

    public function processAllContracts($contracts)
    {
        foreach ($contracts as $company => $contracts) {
            $this->customer->getAdresar()->setMyKey($company);
            foreach ($this->getUnsignedContracts($contracts) as $contract) {
                $this->notifyUnsignedContract($contract);
            }
        }
    }

    public function notifyUnsignedContract($contract)
    {
        echo 'Notifikuj nepodepsanou smlouvu ' . $contract['kod'] . ' klienta ' . $contract['firma'] . '<br>';
    }
}
