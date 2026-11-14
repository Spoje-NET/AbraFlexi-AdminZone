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

namespace AdminZone\ui;

use AdminZone\PricelistChecker;
use AdminZone\PricelistItem;
use AdminZone\PricelistTree;

/**
 * Items of the pricelist as a table: what they are, how ClientZone treats them and a link to edit.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class PricelistItemsTable extends \Ease\Html\DivTag
{
    /**
     * @param array<int, array<string, mixed>> $items       rows of "cenik" to list
     * @param PricelistTree                    $tree        the pricelist tree
     * @param null|int                         $catalogNode node ClientZone sells from (PROD_CAT), null = whole tree
     * @param string                           $editUrl     page editing an item, the id is appended as ?id=
     */
    public function __construct(array $items, PricelistTree $tree, ?int $catalogNode = null, string $editUrl = 'pricelistitem.php')
    {
        parent::__construct();

        if ($items === []) {
            $this->addItem(new \Ease\Html\DivTag(_('No items here.'), ['class' => 'alert alert-secondary']));

            return;
        }

        $checker = new PricelistChecker();
        $table = new \Ease\Html\TableTag(null, ['class' => 'table table-sm table-hover align-middle']);
        $table->addRowHeaderColumns([_('Code'), _('Name'), _('Price'), _('Type'), _('Labels'), _('Customers see it'), '']);

        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $type = self::text($item['typZasobyK'] ?? '');
            $labels = PricelistChecker::labels($item['stitky'] ?? '');
            $offered = $checker->isOffered($item) && PricelistChecker::documentFor($type) !== null;
            $inCatalog = $catalogNode === null || !$tree->has($catalogNode) || $tree->contains($catalogNode, $id);

            $labelHtml = '';

            foreach ($labels as $label) {
                $steering = \in_array($label, PricelistItem::STEERING_LABELS, true);
                $labelHtml .= '<span class="badge text-bg-'.($steering ? 'primary' : 'light border').' me-1">'.htmlspecialchars($label).'</span>';
            }

            $typeHtml = $type === '' ? '<span class="badge text-bg-danger">?</span>'
                : '<span class="badge text-bg-'.(PricelistChecker::documentFor($type) === null ? 'warning' : 'info').'" title="'.htmlspecialchars(PricelistItem::consequence($type), \ENT_QUOTES).'">'
                .htmlspecialchars(PricelistItem::translate($type)).'</span>';

            if ($offered && $inCatalog) {
                $visible = '<span class="badge text-bg-success">'.htmlspecialchars(_('yes')).'</span>';
            } elseif ($offered) {
                $visible = '<span class="badge text-bg-warning" title="'.htmlspecialchars(_('Outside the catalog node of ClientZone'), \ENT_QUOTES).'">'.htmlspecialchars(_('outside the catalog')).'</span>';
            } else {
                $visible = '<span class="badge text-bg-secondary">'.htmlspecialchars(_('no')).'</span>';
            }

            $price = self::text($item['cenaZakl'] ?? '');

            $table->addRowColumns([
                '<code>'.htmlspecialchars(self::text($item['kod'] ?? '')).'</code>',
                htmlspecialchars(self::text($item['nazev'] ?? '')),
                is_numeric($price) ? number_format((float) $price, 2, ',', ' ') : '',
                $typeHtml,
                $labelHtml,
                $visible,
                new \Ease\Html\ATag($editUrl.'?id='.$id, '<i class="fa fa-pen"></i> '._('Edit'), ['class' => 'btn btn-sm btn-outline-primary']),
            ]);
        }

        $this->addItem(new \Ease\Html\DivTag($table, ['class' => 'table-responsive']));
    }

    private static function text(mixed $value): string
    {
        if (\is_array($value)) {
            $value = $value['value'] ?? '';
        }

        return \is_scalar($value) || $value instanceof \Stringable ? trim((string) $value) : '';
    }
}
