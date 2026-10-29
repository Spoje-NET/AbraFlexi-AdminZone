<?php

/**
 * AdminZone - Generuje závazky ze Smluv.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015,2021,2023 Spoje.Net
 */

namespace AdminZone;

require_once dirname(__FILE__) . '/../includes/Init.php';

$worker = new ZalohyZeSmluvDoZavazku();
//$worker->pripravSmlouvy();
$worker->nactiZalohoveFaktury();
$worker->zkonvertujZalohyNaZavazky();
$worker->ulozZavazky();
$worker->uklidZpracovaneZalohy();
