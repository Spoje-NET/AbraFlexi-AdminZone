<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone;

use AdminZone\ui\CompanyLogo;

/**
 * Description of PotvrzeniPrijetiFaktury
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class PotvrzeniPrijetiFaktury extends Mailer
{
    /**
     * SPOJE.NET s.r.o Patočkova 77 ..
     * @var string
     */
    static $signature = 'SPOJE.NET s.r.o.
Patočkova 77
169 00 Praha 6
Zákaznická podpora: helpdesk@spoje.net +420 222 264 888 (9-19h) +420 608 233 232

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
     * @param \AbraFlexi\FakturaPrijata $invoice
     */
    public function assignInvoice($invoice)
    {
        $defaultLocale = 'cs_CZ';
        setlocale(LC_ALL, $defaultLocale);
        putenv("LC_ALL=$defaultLocale");

        $body = new \Ease\Container();

        $to = \AdminZone\Mailer::getNotificationEmailAddres(new \AbraFlexi\Adresar($invoice->getDataValue('firma')));

        $customerName = $invoice->getDataValue('firma@showAs');
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
            _('we confirm receipt of invoice %s as %s '),
            $invoice->getDataValue('cisDosle'),
            $invoice->getDataValue('kod')
        )));
        $body->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(new \Ease\Html\DivTag(_('With greetings')));

        $body->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(nl2br(self::$signature));

        parent::__construct(
            $to,
            sprintf(_('Confirmation of receipt your invoice %s'), \AbraFlexi\Functions::uncode($invoice->getRecordIdent())),
            $body
        );
        $this->setMailHeaders(['Cc' => constant('SEND_INFO_TO')]);
    }
}
