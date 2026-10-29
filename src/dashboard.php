<?php

namespace AdminZone;

/**
 * AdminZone - předloha stránky.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

require_once './includes/Init.php';

$oPage->onlyForLogged();

$oPage->addItem(new ui\PageTop(_('Dashboard')));

$oPage->container->addItem(new ui\dashboard\CenterTabs());

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
