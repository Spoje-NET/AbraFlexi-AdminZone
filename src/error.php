<?php

namespace AdminZone;

/**
 * Dotazník - administrace.
 *
 * @author     Vítězslav Dvořák <vitex@spojenet.cz>
 * @copyright  2015-2024 Spoje.Net s.r.o.
 */

require_once 'includes/Init.php';

$oPage->addItem(new ui\PageTop(_('Chyba')));

$oPage->addStatusMessage('Testovaci Errorek ' . time(), 'error');

$oPage->draw();
