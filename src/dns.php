<?php

namespace AdminZone;

/**
 * AdminZone - Hlavní strana.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2015-2017 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$oPage->addItem(new ui\PageTop(_('Domain Products')));

//$subreger  = new SubregAPI();
//$creditRaw = $subreger->call('Get_Credit');
//
//$domains   = $creditRaw = $subreger->call('Domains_List');
//
//$oPage->addItem('<pre>'.print_r($domains, true).'<pre>' );

$oPage->container->addItem(new \Ease\TWB\LinkButton(
    'orderform.php?service=DomainCredit',
    _('Order Credit'),
    'success'
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
