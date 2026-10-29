<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone;

use AdminZone\ui\CompanyLogo;

/**
 * Description of PotvrzeniUhrady
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class PotvrzeniPrijetiUhrady extends Mailer
{
    /**
     * SPOJE.NET s.r.o Patočkova 77 ..
     * @var string
     */
    static $signature = 'SPOJE.NET s.r.o.
Patočkova 77
169 00 Praha 6
Zákaznická podpora: helpdesk@spojenet.cz +420 608 233 232 (9-19h)

INTERNET, HOSTING, HOUSING, SPRÁVA SÍTĚ, OUTSOURCING IT, EZS, KAMEROVÉ SYSTÉMY         
';

    /**
     * Odešle potvrzení úhrady
     * @param \AbraFlexi\FakturaVydana $invoice
     */
    public function __construct($invoice = null)
    {
        if (!is_null($invoice)) {
            $this->assignInvoice($invoice);
        }
    }

    /**
     *
     * @param FakturaVydana $invoice
     */
    public function assignInvoice($invoice)
    {
        $defaultLocale = 'cs_CZ';
        setlocale(LC_ALL, $defaultLocale);
        putenv("LC_ALL=$defaultLocale");

        $body = new \Ease\Container();

        $to = \AdminZone\Mailer::getNotificationEmailAddres(new \AbraFlexi\Adresar($invoice->getDataValue('firma')));

        $customerName = $invoice->getDataValue('firma')->showAs;
        if (empty($customerName)) {
            $customerName = \AbraFlexi\Functions::uncode($invoice->getDataValue('firma'));
        }





        $body->addItem(new CompanyLogo(['align' => 'right', 'id' => 'companylogo',
                    'height' => '50', 'title' => _('Company logo')]));

        $prober = new \AbraFlexi\Company();
        $infoRaw = $prober->getFlexiData();
        if (count($infoRaw) && !array_key_exists('success', $infoRaw)) {
            $info = \Ease\Functions::reindexArrayBy($infoRaw, 'dbNazev');
            $myCompany = $prober->getCompany();
            if (array_key_exists($myCompany, $info)) {
                $body->addItem(new \Ease\Html\H2Tag($info[$myCompany]['nazev']));
            }
        }

        $body->addItem(new \Ease\Html\DivTag(sprintf(
            _('Dear customer %s,'),
            $customerName
        )));
        $body->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(new \Ease\Html\DivTag(sprintf(
            _('we confirm receipt of payment %s %s on %s '),
            $invoice->getDataValue('sumCelkem'),
            \AbraFlexi\Functions::uncode($invoice->getDataValue('mena')),
            $invoice->getDataValue('kod')
        )));
        $body->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(new \Ease\Html\DivTag(_('With greetings')));

        $body->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(nl2br(self::$signature));

        parent::__construct(
            $to,
            sprintf(
                _('Confirmation of receipt of invoice %s payment'),
                \AbraFlexi\Functions::uncode($invoice->getDataValue('kod'))
            ),
            $body
        );

//        $this->addFile($invoice->downloadInFormat('pdf', '/tmp/'),
//                \AbraFlexi\Formats::$formats['PDF']['content-type']);
//        $this->addFile($invoice->downloadInFormat('isdocx', '/tmp/'),
//                \AbraFlexi\Formats::$formats['ISDOCx']['content-type']);

        $this->setMailHeaders(['Cc' => \Ease\Shared::cfg('SEND_INFO_TO')]);
    }
}
