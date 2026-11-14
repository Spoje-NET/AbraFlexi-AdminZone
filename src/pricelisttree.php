<?php

declare(strict_types=1);

/**
 * This file is part of the AdminZone package
 *
 * https://github.com/VitexSoftware/AbraFlexi-AdminZone
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AdminZone;

require_once 'includes/Init.php';

$oPage->onlyForLogged();
$oPage->addToHeader(new ui\PageTop(_('Pricelist')));

$oPage->container->addItem(new \Ease\Html\DivTag([
    new \Ease\Html\H1Tag(_('Pricelist'), ['class' => 'h3']),
    new \Ease\Html\DivTag([
        new \Ease\Html\ATag('pricelistitem.php', '<i class="fa fa-plus"></i> '._('New item'), ['class' => 'btn btn-success btn-sm']),
        new \Ease\Html\ATag('pricelist.php', '<i class="fa fa-stethoscope"></i> '._('Check'), ['class' => 'btn btn-outline-secondary btn-sm']),
    ], ['class' => 'd-flex gap-2']),
], ['class' => 'd-flex justify-content-between align-items-center mt-4 mb-3']));

try {
    $tree = PricelistTree::fetch();
    $items = PricelistChecker::fetch();

    $byId = [];

    foreach ($items as $item) {
        $byId[(int) ($item['id'] ?? 0)] = $item;
    }

    // The node ClientZone sells from (its PROD_CAT); the whole tree when it is not set or unknown
    $catalog = PricelistTree::refId(\Ease\Shared::cfg('PROD_CAT', '1'));
    $catalog = $catalog !== null && $tree->has($catalog) ? $catalog : null;

    $unplaced = $tree->unplacedItemIds(array_keys($byId));
    $requested = (string) $oPage->getRequestValue('node');

    if ($requested === 'unplaced') {
        $selected = 'unplaced';
        $ids = $unplaced;
        $title = _('Items that are not placed in the tree');
    } else {
        $nodeId = PricelistTree::refId($requested) ?? $catalog ?? ($tree->roots()[0] ?? null);
        $selected = $nodeId !== null && $tree->has($nodeId) ? $nodeId : null;
        $ids = $selected !== null ? $tree->itemIds($selected) : [];
        $title = $selected !== null ? $tree->path($selected) : _('Pricelist');
    }

    $rows = array_values(array_filter(array_map(static fn (int $id): ?array => $byId[$id] ?? null, $ids)));
    usort($rows, static fn (array $a, array $b): int => strcasecmp((string) ($a['kod'] ?? ''), (string) ($b['kod'] ?? '')));

    $row = new \Ease\Html\DivTag(null, ['class' => 'row g-3']);
    $row->addItem(new \Ease\Html\DivTag(
        new ui\PricelistTreeView($tree, $selected, $catalog, \count($unplaced)),
        ['class' => 'col-lg-4'],
    ));

    $listing = new \Ease\Html\DivTag(null, ['class' => 'col-lg-8']);
    $listing->addItem(new \Ease\Html\DivTag([
        new \Ease\Html\H2Tag(htmlspecialchars($title), ['class' => 'h5 mb-0']),
        $selected !== null && $selected !== 'unplaced'
            ? new \Ease\Html\ATag('pricelistitem.php?node='.$selected, '<i class="fa fa-plus"></i> '._('New item here'), ['class' => 'btn btn-outline-success btn-sm'])
            : '',
    ], ['class' => 'd-flex justify-content-between align-items-center mb-2']));
    $listing->addItem(new \Ease\Html\PTag(
        $catalog === null
            ? _('ClientZone sells from the whole pricelist.')
            : sprintf(_('ClientZone sells the items labelled %s and %s placed below %s.'), PricelistChecker::LABEL_SHOP, PricelistChecker::LABEL_ACTIVE, $tree->path($catalog)),
        ['class' => 'dim small'],
    ));
    $listing->addItem(new ui\PricelistItemsTable($rows, $tree, $catalog));
    $row->addItem($listing);

    $oPage->container->addItem($row);
} catch (\AbraFlexi\Exception $exc) {
    error_log('AdminZone pricelist tree failed: '.$exc->getMessage());
    $oPage->container->addItem(new \Ease\Html\DivTag(_('The pricelist could not be read from AbraFlexi.'), ['class' => 'alert alert-danger mt-3']));
}

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
