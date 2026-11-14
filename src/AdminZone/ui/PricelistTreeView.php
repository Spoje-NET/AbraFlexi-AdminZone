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

use AdminZone\PricelistTree;

/**
 * The pricelist tree as a list of links to the nodes, with the number of items below each.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class PricelistTreeView extends \Ease\Html\DivTag
{
    /**
     * @param PricelistTree   $tree        the tree
     * @param null|int|string $selected    selected node id, or "unplaced" for the items outside the tree
     * @param null|int        $catalogNode node ClientZone sells from, marked in the tree
     * @param int             $unplaced    how many items are not placed in the tree
     * @param string          $baseUrl     page the links lead to, the node is appended as ?node=
     */
    public function __construct(PricelistTree $tree, int|string|null $selected = null, ?int $catalogNode = null, int $unplaced = 0, string $baseUrl = 'pricelisttree.php')
    {
        parent::__construct(null, ['class' => 'list-group pricelist-tree']);

        foreach ($tree->flat() as $node) {
            $id = $node['id'];
            $active = $selected !== null && (string) $selected === (string) $id;
            $label = htmlspecialchars($node['name']);

            if ($id === $catalogNode) {
                $label .= ' <span class="badge text-bg-success" title="'.htmlspecialchars(_('ClientZone sells from here')).'">ClientZone</span>';
            }

            $this->addItem(new \Ease\Html\ATag(
                $baseUrl.'?node='.$id,
                '<span class="pricelist-tree-name">'.$label.'</span>'
                .' <span class="badge text-bg-secondary rounded-pill">'.\count($tree->itemIds($id)).'</span>',
                [
                    'class' => 'list-group-item list-group-item-action d-flex justify-content-between align-items-center'.($active ? ' active' : ''),
                    'style' => 'padding-left: '.(1 + 1.25 * $node['depth']).'rem',
                ],
            ));
        }

        $this->addItem(new \Ease\Html\ATag(
            $baseUrl.'?node=unplaced',
            '<span><i class="fa fa-question-circle"></i> '.htmlspecialchars(_('Not in the tree')).'</span>'
            .' <span class="badge text-bg-'.($unplaced > 0 ? 'warning' : 'secondary').' rounded-pill">'.$unplaced.'</span>',
            ['class' => 'list-group-item list-group-item-action d-flex justify-content-between align-items-center mt-3'.($selected === 'unplaced' ? ' active' : '')],
        ));
    }
}
