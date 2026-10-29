<?php

namespace AdminZone;

/**
 * AdminZone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

namespace AdminZone;

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$reset = $oPage->getRequestValue('reset');
$evidence = $oPage->getRequestValue('evidence');
$contractor = new SmlouvyGenerujici($reset);
if (!is_null($reset) && !is_null($evidence)) {
    $contractor->setEvidence($evidence);
    if ($contractor->reset($reset)) {
        $contractor->addStatusMessage(sprintf(_('Smlouva byla resetnuta')));
    }
    \Ease\WebPage::redirect('contract-reset.php');
}

$oPage->addItem(new ui\PageTop(_('Smlouvy')));

$contractTables = new \Ease\TWB\Tabs('ContractTabs');
$contractTables->addTab(
    _('Dodavatelské'),
    $contractor->contractTable('dodavatelska-smlouva')
);
$contractTables->addTab(_('Odběratelské'), $contractor->contractTable('smlouva'));

$oPage->container->addItem(new \Ease\TWB\Panel(
    _('Smlouvy k autogeneraci'),
    'info',
    $contractTables
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
