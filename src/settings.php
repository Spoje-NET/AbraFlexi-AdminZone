<?php

namespace AdminZone;

/**
 * AdminZone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForUser();

$oPage->addItem(new ui\PageTop(_('Nastavení')));


$oPage->container->addItem(new \Ease\TWB\LinkButton(
    'abraflexiinit.php',
    _('Init AbraFlexi'),
    'warning'
));

$oPage->container->addItem(new \Ease\TWB\LinkButton(
    'redmineprojects.php',
    _('Redmine Projects'),
    'info'
));

$oPage->container->addItem(new \Ease\TWB\LinkButton(
    'extids.php?service=subreg',
    _('SubReg/AbraFlexi'),
    'info'
));

$oPage->container->addItem(new \Ease\TWB\LinkButton(
    'extids.php?service=ipex',
    _('Ipex/AbraFlexi'),
    'info'
));

$oPage->container->addItem(new \Ease\TWB\LinkButton(
    'extids.php?service=sltv',
    _('SledovaniTV/AbraFlexi'),
    'info'
));

$oPage->container->addItem(new \Ease\TWB\LinkButton(
    'monitor.php',
    _('Console Monitor'),
    'info'
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
