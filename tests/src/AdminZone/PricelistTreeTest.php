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

namespace Test\AdminZone;

use AdminZone\PricelistTree;
use PHPUnit\Framework\TestCase;

class PricelistTreeTest extends TestCase
{
    private static function tree(): PricelistTree
    {
        $nodes = [
            ['id' => '1', 'kod' => 'STR_CEN', 'nazev' => 'Strom ceníku', 'otec' => '', 'poradi' => '1', 'strom' => 'code:STR_CEN'],
            ['id' => '3', 'kod' => '', 'nazev' => 'Nepřipojené', 'otec' => 'code:STR_CEN', 'poradi' => '2', 'strom' => 'code:STR_CEN'],
            ['id' => '2', 'kod' => '', 'nazev' => 'Připojené', 'otec' => 'code:STR_CEN', 'poradi' => '1', 'strom' => 'code:STR_CEN'],
            ['id' => '5', 'kod' => 'ESHOP', 'nazev' => 'E-shop', 'otec' => '2', 'poradi' => '2', 'strom' => 'code:STR_CEN'],
            ['id' => '4', 'kod' => 'Sluzby', 'nazev' => 'Služby', 'otec' => '2', 'poradi' => '1', 'strom' => 'code:STR_CEN'],
            ['id' => '9', 'kod' => 'OTHER', 'nazev' => 'Jiný strom', 'otec' => '', 'poradi' => '1', 'strom' => 'code:JINY'],
        ];
        $links = [
            ['id' => '1', 'idZaznamu' => '31', 'uzel' => 'code:Sluzby'],
            ['id' => '2', 'idZaznamu' => '32', 'uzel' => 'code:Sluzby', 'uzel@ref' => '/c/firma/strom/4.json'],
            ['id' => '3', 'idZaznamu' => '40', 'uzel' => '5'],
            ['id' => '4', 'idZaznamu' => '99', 'uzel' => 'code:NEEXISTUJE'],
        ];

        return new PricelistTree($nodes, $links);
    }

    public function testOrderOfNodesFollowsTheTreeAndItsOrder(): void
    {
        $this->assertSame([1, 2, 4, 5, 3], array_column(self::tree()->flat(), 'id'));
        $this->assertSame([0, 1, 2, 2, 1], array_column(self::tree()->flat(), 'depth'));
    }

    public function testNodesOfAnotherTreeAreIgnored(): void
    {
        $this->assertFalse(self::tree()->has(9));
    }

    public function testItemsAreFoundBelowTheNode(): void
    {
        $tree = self::tree();

        $this->assertSame([31, 32], $tree->itemIds(4));
        $this->assertEqualsCanonicalizing([31, 32, 40], $tree->itemIds(2));
        $this->assertSame([40], $tree->itemIds(5, false));
        $this->assertSame([], $tree->itemIds(3));
    }

    public function testContainsIsWhatInSubtreeFinds(): void
    {
        $tree = self::tree();

        $this->assertTrue($tree->contains(2, 31));
        $this->assertTrue($tree->contains(1, 40));
        $this->assertFalse($tree->contains(4, 40));
        $this->assertFalse($tree->contains(2, 1234));
    }

    public function testLinkToUnknownNodeIsIgnoredAndItemIsUnplaced(): void
    {
        $tree = self::tree();

        $this->assertSame([], $tree->nodeIdsOfItem(99));
        $this->assertSame([99, 100], $tree->unplacedItemIds([31, 99, 100, 40]));
    }

    public function testPath(): void
    {
        $this->assertSame('Strom ceníku › Připojené › E-shop', self::tree()->path(5));
        $this->assertSame('', self::tree()->path(777));
    }

    public function testBrokenParentsDoNotLoopForever(): void
    {
        $tree = new PricelistTree([
            ['id' => '1', 'nazev' => 'A', 'otec' => '2', 'poradi' => '1'],
            ['id' => '2', 'nazev' => 'B', 'otec' => '1', 'poradi' => '1'],
            ['id' => '3', 'nazev' => 'C', 'otec' => '3', 'poradi' => '1'],
        ], []);

        // Only the node that is not part of a loop is reachable from a root, and nothing hangs
        $this->assertSame([3], array_column($tree->flat(), 'id'));
        $this->assertEqualsCanonicalizing([1, 2], $tree->subtree(1));
        $this->assertSame('B › A', $tree->path(1));
    }

    public function testRefId(): void
    {
        $this->assertSame(5, PricelistTree::refId('5'));
        $this->assertSame(5, PricelistTree::refId('/c/firma/strom/5.json'));
        $this->assertNull(PricelistTree::refId('code:ESHOP'));
        $this->assertNull(PricelistTree::refId(''));
        $this->assertNull(PricelistTree::refId(null));
    }
}
