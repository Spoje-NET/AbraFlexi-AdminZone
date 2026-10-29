<?php

namespace AdminZone;

/**
 * AdminZone - Stránka štítku.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2017 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForUser();

$label = $oPage->getRequestValue('label');
$evidence = $oPage->getRequestValue('evidence');

$abraFlexi = new \AbraFlexi\RO(null, ['evidence' => $evidence]);

$oPage->addItem(new ui\PageTop(_('Label') . ': ' . $label));

//        $operationsMenu = $contact->operationsMenu();
//        $operationsMenu->setTagCss(['float' => 'right']);
//        $operationsMenu->dropdown->addTagClass('pull-right');
$operationsMenu = '';

$topRow = new \Ease\TWB\Row();
$topRow->addColumn(
    4,
    _('Label') . ': <strong>' . $label . '</strong>'
);

$oPage->container->addItem(new \Ease\TWB\Panel(
    $topRow,
    'info',
    new ui\RecordsWithLabel($abraFlexi, $label)
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
