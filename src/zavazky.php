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

$oPage->addItem(new ui\PageTop(_('Závazky')));

$worker = new ZalohyZeSmluvDoZavazku();
$worker->pripravSmlouvy();
$worker->nactiZalohoveFaktury();
$worker->zkonvertujZalohyNaZavazky();

$worker->debug = true;
$noveZavazky = $worker->ulozZavazky();
$worker->debug = false;

$zavList = new \Ease\Html\UlTag();
$worker->setEvidence('zavazek');
foreach ($noveZavazky as $zavazekId) {
    $zavList->addItemSmart(new \Ease\Html\ATag(
        $worker->getEvidenceURL() . '/' . $zavazekId,
        $zavazekId
    ));
}
$oPage->container->addItem(new \Ease\TWB\Panel(
    _('Vygenerované ostatní závazky'),
    'success',
    $zavList
));

$worker->uklidZpracovaneZalohy();

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
