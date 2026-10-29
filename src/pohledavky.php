<?php

/**
 * AdminZone - závazky.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

namespace AdminZone;

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$oPage->addItem(new ui\PageTop(_('Pohledávky')));

$worker = new ZalohyZeSmluvDoPohledavek();
$worker->pripravSmlouvy();
$worker->nactiZalohoveFaktury();
$worker->zkonvertujZalohyNaPohledavky();

$worker->debug = true;
$novePohledavky = $worker->ulozPohledavky();
$worker->debug = false;

$pohList = new \Ease\Html\UlTag();
$worker->setEvidence('zavazek');
foreach ($novePohledavky as $zavazekId) {
    $pohList->addItemSmart(new \Ease\Html\ATag(
        $worker->getEvidenceURL() . '/' . $zavazekId,
        $zavazekId
    ));
}
$oPage->container->addItem(new \Ease\TWB\Panel(
    _('Vygenerované ostatní pohledávky'),
    'success',
    $pohList
));

$worker->uklidZpracovaneZalohy();

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
