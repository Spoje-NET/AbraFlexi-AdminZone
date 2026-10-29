<?php

namespace AdminZone;

require_once 'includes/Init.php';

/**
 * AdminZone - Objednávkový formulář
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2021 Spoje.Net
 */
$oPage->addItem(new ui\PageTop(_('Spoje.Net Redmine Project Chooser')));

$projectsOn = $oPage->getRequestValue('project');

$redminer = new Importer\RedmineRestClient();

$projects = $redminer->getProjects();

if (empty($projects)) {
    $projectsForm = new \Ease\TWB\Label('warning', _('No projects found'));
} else {
    $projectsForm = new \Ease\TWB\Form('Projects');
    foreach ($projects as $projectID => $projectData) {
        $projectRow = new \Ease\TWB\Row();
        $projectRow->addColumn(2, $projectData['name']);
        $projectRow->addColumn(
            2,
            new \Ease\TWB\Widgets\TWBSwitch(
                'project[' . $projectID . ']',
                isset($projectsOn[$projectID])
            )
        );
        $projectsForm->addItem($projectRow);
    }
}

$projectsForm->addItem(new \Ease\TWB\SubmitButton(_('Get import IDs'), 'success'));

$oPage->addItem(new \Ease\TWB\Panel(
    _('Choose Redmine Projects'),
    'warning',
    $projectsForm,
    empty($projectsOn) ? '' : new \Ease\Html\H2Tag(implode(
        ',',
        array_keys($projectsOn)
    ))
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
