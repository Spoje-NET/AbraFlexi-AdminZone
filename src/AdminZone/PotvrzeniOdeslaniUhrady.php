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
class PotvrzeniOdeslaniUhrady extends Mailer
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
     * @param FakturaVydana $invoice
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

        $this->addItem(new CompanyLogo(['align' => 'right', 'id' => 'companylogo',
                    'height' => '50', 'title' => _('Company logo')]));

        $prober = new \AbraFlexi\Company();
        $infoRaw = $prober->getFlexiData();
        if (count($infoRaw) && !array_key_exists('success', $infoRaw)) {
            $info = \Ease\Functions::reindexArrayBy($infoRaw, 'dbNazev');
            $myCompany = $prober->getCompany();
            if (array_key_exists($myCompany, $info)) {
                $this->addItem(new \Ease\Html\H2Tag($info[$myCompany]['nazev']));
            }
        }


        $this->addItem(new \Ease\Html\DivTag(sprintf(
            _('Dear partner %s,'),
            $customerName
        )));
        $this->addItem(new \Ease\Html\DivTag("\n<br>"));

        $this->addItem(new \Ease\Html\DivTag(sprintf(
            _('we confirm sending bank order to pay your invoice %s'),
            $invoice->getDataValue('cisDosle')
        )));
        $this->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(new \Ease\Html\DivTag(_('With greetings')));

        $this->addItem(new \Ease\Html\DivTag("\n<br>"));

        $body->addItem(nl2br(self::$signature));

        parent::__construct(
            $to,
            sprinf(_('Confirmation of sending of invoice %s payment'), $invoice->getDataValue('cisDosle')),
            $body
        );
        $this->setMailHeaders(['Cc' => constant('SEND_INFO_TO')]);
    }
}
