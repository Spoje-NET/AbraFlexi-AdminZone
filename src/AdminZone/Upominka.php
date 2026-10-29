<?php

namespace AdminZone;

/**
 * Description of Upominka
 *
 * @author vitex
 */
class Upominka extends \AbraFlexi\RW
{
    /**
     * @var string
     */
    public ?string $evidence = 'sablona-upominky';

    /**
     * @var Mailer
     */
    public $mailer = null;

    /**
     * @var \AbraFlexi\Adresar
     */
    public $firmer = null;

    /**
     * Invoice
     * @var \AbraFlexi\FakturaVydana
     */
    public $invoicer = null;

    public function __construct($init = null, $options = array())
    {
        parent::__construct($init, $options);

        $this->invoicer = new \AbraFlexi\FakturaVydana();
        $this->firmer = new \AbraFlexi\Adresar();
    }

    /**
     * Load
     * @param string $template prvniUpominka|druhaUpominka|pokusOSmir|inventarizace
     */
    public function loadTemplate($template)
    {
        $this->takeData(current($this->getColumnsFromAbraFlexi(
            '*',
            ['typSablonyK' => 'typSablony.' . $template]
        )));
    }

    /**
     *
     * @param int $cid
     * @param array $clientDebts
     */
    public function compile($cid, $clientDebts)
    {
        $result = false;

        $this->firmer->setMyKey($cid);

        $to = Mailer::getNotificationEmailAddres($this->firmer);

        if (empty($to) === false) {
            $invoices = [];
            foreach ($clientDebts as $debt) {
                $ddiff = \AbraFlexi\FakturaVydana::overdueDays((string)$debt['datSplat']);
                $invoices[] = $debt['kod'] . ' v.s.: ' . $debt['varSym'] . ' ' . self::getToPayAmount($debt) . ' ' . \AbraFlexi\RO::flexiDateToDateTime($debt['datSplat'])->format('d.m.Y') . ' (' . $ddiff . ' dní po splatnosti)';
            }

            $dnes = new \DateTime();
            $subject = $this->getDataValue('hlavicka') . ' ke dni ' . $dnes->format('d.m.Y');
            $body = $this->getDataValue('uvod') .
                    "\n\n" . $this->getDataValue('textNad') . "\n\n" .
                    sprintf(
                        _("%s \n\n-----------------------\n celkem za %s,-"),
                        implode("\n", $invoices),
                        self::formatSums(self::getSums($clientDebts))
                    ) .
                    "\n\n" . $this->getDataValue('textPod') . "\n" .
                    "\n\n" . $this->getDataValue('zapati') . "\n";

            $this->mailer = new Mailer(
                $to,
                $subject . ' ' . $debt['firma']->showAs,
                $body
            );
            $this->addAttachments($clientDebts);
            $result = true;
        } else {
            $this->firmer->addStatusMessage(sprintf(
                _('Klient nema email - HELPDESK RESIT %s !!!'),
                $this->firmer->getDataValue('nazev')
            ), 'warning');
        }
        return $result;
    }

    public static function formatSums($sums)
    {
        $totals = [];
        foreach ($sums as $currency => $value) {
            $totals[] = $value . ' ' . $currency;
        }
        return implode(' ,', $totals);
    }

    /**
     * Obtain all debts sums indexed by currency
     *
     * @param array $debts
     *
     * @return array
     */
    public static function getSums(array $debts)
    {
        $sumsCelkem = [];
        foreach ($debts as $debt) {
            $currency = \AbraFlexi\Functions::uncode($debt['mena']);
            if ($currency == 'CZK') {
                $amount = $debt['zbyvaUhradit'];
            } else {
                $amount = $debt['zbyvaUhraditMen'];
            }
            if (!array_key_exists($currency, $sumsCelkem)) {
                $sumsCelkem[$currency] = $amount;
            } else {
                $sumsCelkem[$currency] += $amount;
            }
        }
        return $sumsCelkem;
    }

    /**
     * Obtain all given debts codes
     *
     * @param array $debts data
     *
     * @return array codes
     */
    public static function getCodes(array $debts)
    {
        $codes = [];
        foreach ($debts as $debt) {
            $codes[] = $debt['kod'];
        }
        return $codes;
    }

    /**
     * Get price to pay in document currency
     *
     * @param array $debt
     *
     * @return float
     */
    public static function getToPayAmount($debt)
    {
        $currency = \AbraFlexi\Functions::uncode($debt['mena']);
        if ($currency == 'CZK') {
            $amount = $debt['zbyvaUhradit'];
        } else {
            $amount = $debt['zbyvaUhraditMen'];
        }
        return $amount . ' ' . $currency;
    }

    /**
     *
     * @param array $clientDebts
     */
    public function addAttachments($clientDebts)
    {
        $evidence = $this->invoicer->getEvidence();
        foreach ($clientDebts as $debt) {
            $this->invoicer->setEvidence($debt['evidence']);
            $this->invoicer->setMyKey($debt['id']);
            $this->mailer->addFile(
                $this->invoicer->downloadInFormat(
                    'pdf',
                    '/tmp/'
                ),
                \AbraFlexi\Formats::$formats['PDF']['content-type']
            );
            $this->mailer->addFile(
                $this->invoicer->downloadInFormat(
                    'isdocx',
                    '/tmp/'
                ),
                \AbraFlexi\Formats::$formats['ISDOCx']['content-type']
            );
        }
        $this->invoicer->setEvidence($evidence);
    }

    /**
     * Send Remind
     *
     * @return boolean
     */
    public function send()
    {
        return $this->mailer->send();
    }
}
