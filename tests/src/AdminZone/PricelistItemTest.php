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

use AdminZone\PricelistItem;
use AdminZone\PricelistTree;
use AdminZone\ui\PricelistItemForm;
use AdminZone\ui\PricelistItemsTable;
use PHPUnit\Framework\TestCase;

class PricelistItemTest extends TestCase
{
    private const CHOICES = [
        'labels' => ['ESHOP' => 'E-shop', 'AKTIVNI' => 'Aktivní', 'SAAS' => 'SaaS'],
        'units' => ['MESIC' => 'Měsíc', 'KS' => 'kus'],
        'groups' => ['SLUŽBY' => 'Služby'],
    ];

    /**
     * @return array<string, mixed>
     */
    private static function post(array $override = []): array
    {
        return $override + [
            'kod' => 'MAILBOX',
            'nazev' => 'Poštovní schránka',
            'popis' => 'Schránka',
            'cenaZakl' => '99,50',
            'typSzbDphK' => 'typSzbDph.dphZakl',
            'typZasobyK' => 'typZasoby.sluzba',
            'mj1' => 'MESIC',
            'skupZboz' => 'SLUŽBY',
            'platiOd' => '',
            'platiDo' => '',
            'stitky' => ['ESHOP', 'AKTIVNI'],
            'node' => '4',
        ];
    }

    public function testValidFormBecomesDataForAbraFlexi(): void
    {
        $result = PricelistItem::normalize(self::post(), self::CHOICES, [1, 2, 4]);

        $this->assertSame([], $result['errors']);
        $this->assertSame(4, $result['node']);
        $this->assertSame('MAILBOX', $result['data']['kod']);
        $this->assertSame(99.5, $result['data']['cenaZakl']);
        $this->assertSame('code:MESIC', $result['data']['mj1']);
        $this->assertSame('code:SLUŽBY', $result['data']['skupZboz']);
        $this->assertSame('ESHOP,AKTIVNI', $result['data']['stitky']);
        $this->assertSame(0, $result['data']['platiOd']);
        $this->assertSame(9999, $result['data']['platiDo']);
    }

    public function testRequiredFieldsAreChecked(): void
    {
        $result = PricelistItem::normalize(self::post(['kod' => ' ', 'nazev' => '', 'typSzbDphK' => 'x', 'typZasobyK' => 'y']), self::CHOICES);

        $this->assertCount(4, $result['errors']);
    }

    public function testBadPriceAndValidityAreRefused(): void
    {
        $this->assertCount(1, PricelistItem::normalize(self::post(['cenaZakl' => 'abc']), self::CHOICES)['errors']);
        $this->assertCount(1, PricelistItem::normalize(self::post(['cenaZakl' => '-5']), self::CHOICES)['errors']);
        $this->assertCount(1, PricelistItem::normalize(self::post(['platiDo' => '20xx']), self::CHOICES)['errors']);
    }

    public function testOfferedItemOfUnsupportedTypeIsRefused(): void
    {
        $offered = PricelistItem::normalize(self::post(['typZasobyK' => 'typZasoby.vyrobek']), self::CHOICES);
        $hidden = PricelistItem::normalize(self::post(['typZasobyK' => 'typZasoby.vyrobek', 'stitky' => ['SAAS']]), self::CHOICES);

        $this->assertCount(1, $offered['errors']);
        $this->assertSame([], $hidden['errors']);
    }

    public function testUnknownChoicesAreRefusedNotStored(): void
    {
        $result = PricelistItem::normalize(self::post(['mj1' => 'NEZNAM', 'skupZboz' => 'NEZNAM', 'stitky' => ['ESHOP', 'AKTIVNI', 'HACK']]), self::CHOICES, [4]);

        $this->assertCount(2, $result['errors']);
        $this->assertSame('', $result['data']['mj1']);
        $this->assertSame('ESHOP,AKTIVNI', $result['data']['stitky']);
    }

    public function testNodeOutsideTheTreeIsRefused(): void
    {
        $result = PricelistItem::normalize(self::post(['node' => '77']), self::CHOICES, [1, 4]);

        $this->assertCount(1, $result['errors']);
        $this->assertNull($result['node']);
    }

    public function testStockTypeChoicesPutTheSellableOnesFirst(): void
    {
        $types = array_keys(PricelistItem::stockTypeChoices());

        $this->assertSame(['typZasoby.sluzba', 'typZasoby.poplatek', 'typZasoby.zbozi', 'typZasoby.material'], \array_slice($types, 0, 4));
        $this->assertCount(8, $types);
    }

    public function testVisibilityTellsWhyAnItemIsNotShown(): void
    {
        $tree = new PricelistTree(
            [
                ['id' => '1', 'nazev' => 'Root', 'otec' => '', 'poradi' => '1'],
                ['id' => '2', 'nazev' => 'Shop', 'otec' => '1', 'poradi' => '1'],
                ['id' => '3', 'nazev' => 'Other', 'otec' => '1', 'poradi' => '2'],
            ],
            [['idZaznamu' => '10', 'uzel' => '2'], ['idZaznamu' => '11', 'uzel' => '3']],
        );
        $item = ['stitky' => 'ESHOP, AKTIVNI', 'typZasobyK' => 'typZasoby.sluzba', 'platiOd' => '0', 'platiDo' => '9999'];

        $inside = PricelistItem::visibility($item + ['id' => 10], $tree, 2, 2026);
        $outside = PricelistItem::visibility($item + ['id' => 11], $tree, 2, 2026);

        $this->assertSame([true, true, true, true], array_column($inside, 'ok'));
        $this->assertSame([true, true, false, true], array_column($outside, 'ok'));
        $this->assertFalse(PricelistItem::visibility(['id' => 10, 'stitky' => '', 'typZasobyK' => 'typZasoby.vyrobek'], $tree, null, 2026)[0]['ok']);
    }

    public function testFormOffersTheChoicesAndEscapesValues(): void
    {
        $tree = new PricelistTree([['id' => '1', 'nazev' => 'Root', 'otec' => '', 'poradi' => '1'], ['id' => '2', 'nazev' => 'Shop <b>', 'otec' => '1', 'poradi' => '1']], []);
        $html = (string) new PricelistItemForm(
            ['kod' => '"><script>', 'nazev' => 'N', 'typZasobyK' => 'typZasoby.sluzba', 'stitky' => 'ESHOP', 'mj1' => 'code:MESIC'],
            self::CHOICES,
            $tree,
            2,
            null,
        );

        $this->assertStringNotContainsString('"><script>', $html);
        $this->assertStringContainsString('value="typZasoby.sluzba" checked', $html);
        $this->assertStringContainsString('value="ESHOP" checked', $html);
        $this->assertStringContainsString('<option value="MESIC" selected>', $html);
        $this->assertStringContainsString('<option value="2" selected>', $html);
        $this->assertStringContainsString('Shop &lt;b&gt;', $html);
        $this->assertStringContainsString('csrf_token', $html);
    }

    public function testTableMarksItemsOutsideTheCatalog(): void
    {
        $tree = new PricelistTree(
            [['id' => '1', 'nazev' => 'Root', 'otec' => '', 'poradi' => '1'], ['id' => '2', 'nazev' => 'Shop', 'otec' => '1', 'poradi' => '1'], ['id' => '3', 'nazev' => 'Other', 'otec' => '1', 'poradi' => '2']],
            [['idZaznamu' => '10', 'uzel' => '2']],
        );
        $items = [
            ['id' => '10', 'kod' => 'IN', 'nazev' => 'In', 'typZasobyK' => 'typZasoby.sluzba', 'stitky' => 'ESHOP, AKTIVNI', 'cenaZakl' => '1'],
            ['id' => '11', 'kod' => '<B>', 'nazev' => 'Out', 'typZasobyK' => 'typZasoby.sluzba', 'stitky' => 'ESHOP, AKTIVNI', 'cenaZakl' => '1'],
        ];
        $html = (string) new PricelistItemsTable($items, $tree, 2);

        $this->assertStringContainsString('pricelistitem.php?id=10', $html);
        $this->assertStringContainsString('outside the catalog', $html);
        $this->assertStringContainsString('&lt;B&gt;', $html);
    }
}
