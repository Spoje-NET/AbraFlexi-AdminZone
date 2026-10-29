<?php

namespace AdminZone;

/**
 * AdminZone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2024 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->addItem(new ui\PageTop(_('AdminZone')));

$mainMenu = $oPage->container->addItem(new ui\MainPageMenu());

switch (get_class($oUser)) {
    case 'AdminZone\User': //Admin
        $mainMenu->addMenuItem(
            'images/redmine.png',
            _('Redmine'),
            'https://crm.spoje.net/'
        );
//$mainMenu->addMenuItem(
//    'images/kasa.png', _('Kasa'), 'cash.php'
//);
        $mainMenu->addMenuItem(
            'images/mapa.png',
            _('Map'),
            'https://hermes.spoje.net/mapa/'
        );

        if (empty(\Ease\Shared::cfg('PROD_CAT'))) {
            $oPage->addStatusMessage(
                _(' Key PROD_CAT is not defined in config file'),
                'warning'
            );
        } else {
            $mainMenu->addMenuItem(
                'images/cennik.png',
                _('Pricelist'),
                'adminpricelist.php'
            );
        }

        $mainMenu->addMenuItem(
            'images/ispconfig.png',
            _('ISPConfig'),
            'https://m.spoje.net/login/'
        );
        $mainMenu->addMenuItem(
            'images/webmail.png',
            _('WebMail'),
            'https://www.spoje.net/roundcube/'
        );
        $mainMenu->addMenuItem(
            'images/wiki.png',
            _('Wiki'),
            'http://wiki.spoje.net/doku.php'
        );
        $mainMenu->addMenuItem(
            'images/abraflexi.png',
            _('AbraFlexi'),
            \Ease\Shared::cfg('ABRAFLEXI_URL') . '/c/' . \Ease\Shared::cfg('ABRAFLEXI_COMPANY')
        );

        $mainMenu->addMenuItem(
            'images/icinga.png',
            _('Icinga'),
            'http://nagios.spoje.net/icinga/'
        );

        $mainMenu->addMenuItem(
            'images/librenms.png',
            _('LibreNMS'),
            'http://nagios.spoje.net/lnms/'
        );

        $labelOverview = new \Ease\TWB\Panel(_('Customers by reminder label'), 'info');
        $addresser = new \AbraFlexi\Adresar();
        $labelTable = new \Ease\Html\TableTag(null, ['class' => 'table']);
        foreach (['UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC', 'ODPOJENO'] as $label) {
            $labelled = $addresser->getColumnsFromAbraFlexi(['kod'], ["stitky='code:" . $label . "'", 'limit' => 0]);
            $labelTable->addRowColumns([$label, is_array($labelled) ? count($labelled) : '?']);
        }
        $labelOverview->addItem($labelTable);
        $oPage->container->addItem($labelOverview);

        break;
    case 'Ease\Anonym': //Anonymous
    default:
        $mainMenu->addMenuItem(
            'images/cennik.png',
            _('Pricelist'),
            'pricelist.php'
        );
        $mainMenu->addMenuItem(
            'images/login.svg',
            _('Sign In'),
            'adminlogin.php'
        );
        break;
}

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
