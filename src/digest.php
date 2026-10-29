<?php

namespace AdminZone;

/**
 * AdminZone - předloha stránky.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

require_once './includes/Init.php';

$oPage->onlyForUser();

$oPage->includeJavaScript('/javascript/jquery-tablesorter/jquery.tablesorter.min.js');
$oPage->addJavaScript('$(".table").tablesorter();');

$oPage->addItem(new ui\PageTop(_('Spoje.Net')));

$digestDir = '/var/run/abraflexi-digest/';

$reports = [];
foreach (scandir($digestDir) as $file) {
    if (preg_match('/^abraflexi-(.*)digest_(.*).html/', $file, $matches)) {
        $reports[$matches[1]][$matches[2]] = $digestDir . '/' . $file;
    }
}

$scopeTabs = new \Ease\TWB\Tabs('ScopeTabs');

foreach ($reports as $scope => $reports) {
    $reportTabs = new \Ease\TWB\Tabs($scope . 'Reports');
    foreach ($reports as $reportName => $reportFile) {
        $reportTabs->addTab($reportName, str_replace('pure-table', 'table', file_get_contents($reportFile)));
    }
    $scopeTabs->addTab($scope, $reportTabs);
}

$oPage->container->addItem($scopeTabs);

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
