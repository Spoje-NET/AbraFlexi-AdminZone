<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of HealthCehck
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class HealthCehck extends \Ease\TWB\Well
{
    public function __construct()
    {
        parent::__construct(new \Ease\Html\H4Tag(_('API Health Check')));
    }

    /**
     * Check AbraFlexie
     *
     * @return boolean api availbility status
     */
    public function checkAbraFlexi()
    {
        $abraflexir = new \AbraFlexi\Adresar();
        $abraflexir->getFirstRecordID();
        return $abraflexir->lastResponseCode == 200 ? 'success' : 'danger';
    }

    /**
     * Check Subreg
     *
     * @return boolean api availbility status
     */
    public function checkSubreg()
    {
        $config = [
            "location" => \Ease\Shared::cfg('SUBREG_LOCATION'),
            "uri" => \Ease\Shared::cfg('SUBREG_URI'),
            "login" => \Ease\Shared::cfg('SUBREG_LOGIN'),
            "password" => \Ease\Shared::cfg('SUBREG_PASSWORD')
        ];

        try {
            $tester = new \Subreg\Client($config);
            $tester->login();
            $tester->call('Get_Credit');
            
        } catch (\Exception $exc) {
            $this->addStatusMessage($exc->getMessage());
        }

        return $tester->lastStatus == 'ok' ? 'success' : 'danger';
    }

    /**
     * IPEX availability is monitored by Zabbix via a MultiFlexi credential,
     * so the check is disabled here (avoids fatal errors when IPEX API is down).
     */
    public function checkIpex()
    {
        return 'secondary';
    }

    public function checkIspConfig()
    {
        if (!\Ease\Shared::cfg('ISPCONFIG_SERVER') || !\Ease\Shared::cfg('ISPCONFIG_LOGIN') || !\Ease\Shared::cfg('ISPCONFIG_PASSWORD')) {
            return 'secondary';
        }

        try {
            $cp = new \GDM\ISPConfig\SoapClient(
                \Ease\Shared::cfg('ISPCONFIG_SERVER') . '/remote/index.php',
                \Ease\Shared::cfg('ISPCONFIG_LOGIN'),
                \Ease\Shared::cfg('ISPCONFIG_PASSWORD')
            );
        } catch (\Exception $e) {
            $this->addStatusMessage('ISPConfig ' . $e->getMessage(), 'error');
        }
        return isset($cp) ? 'success' : 'danger';
    }

    public function checkFio()
    {
        $downloader = new \FioApi\Downloader(\Ease\Shared::cfg('FIO_RO_TOKEN'));

        $client = $downloader->getClient();
        $url = \FioApi\UrlBuilder::BASE_URL . 'last/' . \Ease\Shared::cfg('FIO_RO_TOKEN') . '/transactions.json';

        try {
            /** @var ResponseInterface $response */
            $response = $client->request(
                'get',
                $url,
                ['verify' => $downloader->getCertificatePath()]
            );
            $result = 'success';
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            if ($e->getCode() == 409) {
                $this->addStatusMessage(
                    $e->getCode() . ': ' . _('You can use one token for API call every 30 seconds'),
                    'error'
                );
            }
            if ($e->getCode() == 500) {
                $this->addStatusMessage($e->getCode() . ': ' . _(
                    'Server returned 500 Internal Error (probably invalid token?)',
                    'error'
                ));
            }
            $result = 'danger';
        }

        return $result;
    }

    public function checkRedmine()
    {
        $redminer = new \AdminZone\Importer\RedmineRestClient();
        $redminer->getProjects();
        return ($redminer->lastResponseCode == 200) ? 'success' : 'danger';
    }

    public function finalize(): void
    {
        $this->addItem(
            new \Ease\TWB\LinkButton(
                \Ease\Shared::cfg('ABRAFLEXI_URL'),
                _('AbraFlexi'),
                $this->checkAbraFlexi() . ' btn-lg'
            )
        );
        $this->addItem(
            new \Ease\TWB\LinkButton(
                'https://subreg.cz/',
                _('Subreg'),
                $this->checkSubreg() . ' btn-lg'
            )
        );
        $this->addItem(
            new \Ease\TWB\LinkButton(
                'https://www.ipex.cz/',
                _('Ipex'),
                $this->checkIpex() . ' btn-lg'
            )
        );
        $this->addItem(
            new \Ease\TWB\LinkButton(
                'https://m.spoje.net/login/',
                _('ISPConfig'),
                $this->checkIspConfig() . ' btn-lg'
            )
        );
        $this->addItem(
            new \Ease\TWB\LinkButton(
                'https://ib.fio.cz/ib/login',
                _('Fio'),
                $this->checkFio() . ' btn-lg'
            )
        );
        $this->addItem(
            new \Ease\TWB\LinkButton(
                \Ease\Shared::cfg('REDMINE_URL'),
                _('Redmine'),
                $this->checkRedmine() . ' btn-lg'
            )
        );
    }

    public function raw()
    {
        return [
            'abraflexi' => $this->checkAbraFlexi() == 'success',
            'subreg' => $this->checkSubreg() == 'success',
            'ispconfig' => $this->checkIspConfig() == 'success',
            'fio' => $this->checkFio() == 'success',
            'redmine' => $this->checkRedmine() == 'success'
        ];
    }
}
