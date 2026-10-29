<?php

declare(strict_types=1);

namespace AdminZone;

/**
 * AdminZone - Hlavní strana: rozcestník k přiřazení externích účtů.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2026 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$oPage->addToHeader(new ui\PageTop(_('AdminZone')));

$server = $oUser->getServerInfo();
$oPage->container->addItem(new \Ease\Html\DivTag(
    '<i class="fa fa-server"></i> ' . htmlspecialchars($server['host'])
    . ' &middot; <i class="fa fa-building"></i> <strong>' . htmlspecialchars($server['companyName']) . '</strong>'
    . ($server['companyName'] !== $server['company'] ? ' <span class="dim">(' . htmlspecialchars($server['company']) . ')</span>' : ''),
    ['class' => 'alert alert-info mt-3', 'role' => 'status'],
));

$icons = ['ipex' => 'fa-phone', 'subreg' => 'fa-globe', 'sltv' => 'fa-tv'];

$oPage->container->addItem(new \Ease\Html\H1Tag(_('External accounts'), ['class' => 'mt-4']));
$oPage->container->addItem(new \Ease\Html\PTag(
    _('Pair accounts of external services with AbraFlexi customers (EXT:id).'),
    ['class' => 'dim'],
));

$row = new \Ease\Html\DivTag(null, ['class' => 'row g-3 mt-2']);

foreach (ExtIdService::$services as $key => $class) {
    $service = new $class();
    $card = new \Ease\Html\DivTag(null, ['class' => 'card h-100']);
    $body = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'card-body']));
    $body->addItem(new \Ease\Html\H3Tag('<i class="fa ' . ($icons[$key] ?? 'fa-link') . '"></i> ' . $service->title(), ['class' => 'card-title h5']));
    $body->addItem(new \Ease\Html\ATag('extids.php?service=' . $key, _('Open') . ' <span class="arrow">→</span>', ['class' => 'btn btn-glow mt-2']));
    $row->addItem(new \Ease\Html\DivTag($card, ['class' => 'col-md-4']));
}

$oPage->container->addItem($row);
$oPage->container->addItem(new \Ease\Html\DivTag([
    new \Ease\Html\ATag('clients.php', '<i class="fa fa-users"></i> ' . _('Clients'), ['class' => 'btn btn-line me-2']),
    new \Ease\Html\ATag(
        \Ease\Shared::cfg('ABRAFLEXI_URL') . '/c/' . \Ease\Shared::cfg('ABRAFLEXI_COMPANY'),
        '<i class="fa fa-arrow-up-right-from-square"></i> ' . _('AbraFlexi'),
        ['class' => 'btn btn-line'],
    ),
], ['class' => 'mt-4']));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
