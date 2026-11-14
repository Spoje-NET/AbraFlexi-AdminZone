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

use AdminZone\PricelistChecker;
use AdminZone\PricelistFinding;
use AdminZone\ui\PricelistReport;
use PHPUnit\Framework\TestCase;

class PricelistReportTest extends TestCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private static function items(): array
    {
        return [
            ['id' => 1, 'kod' => 'OK', 'nazev' => 'Fine', 'typZasobyK' => 'typZasoby.sluzba', 'cenaZakl' => '10', 'typSzbDphK' => 'typSzbDph.dphZakl', 'mj1' => 'code:MESIC', 'stitky' => 'ESHOP, AKTIVNI', 'popis' => 'x', 'platiOd' => '0', 'platiDo' => '9999'],
            ['id' => 2, 'kod' => '<b>BAD</b>', 'nazev' => 'Bad', 'typZasobyK' => 'typZasoby.sluzba', 'cenaZakl' => '10', 'typSzbDphK' => '', 'mj1' => 'code:MESIC', 'stitky' => 'ESHOP, AKTIVNI', 'popis' => 'x', 'platiOd' => '0', 'platiDo' => '9999'],
        ];
    }

    public function testReportListsFindingsAndCounts(): void
    {
        $items = self::items();
        $findings = (new PricelistChecker([], 2026))->check($items);
        $html = (string) new PricelistReport($items, $findings, null, 'https://abra.example.com/c/demo/cenik/');

        $this->assertStringContainsString('Checked 2 pricelist items, 2 of them offered', $html);
        $this->assertStringContainsString('no-vat', $html);
        $this->assertStringContainsString('https://abra.example.com/c/demo/cenik/2', $html);
    }

    public function testItemCodeIsEscaped(): void
    {
        $items = self::items();
        $html = (string) new PricelistReport($items, (new PricelistChecker([], 2026))->check($items));

        $this->assertStringNotContainsString('<b>BAD</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;BAD&lt;/b&gt;', $html);
    }

    public function testSeverityFilterHidesTheOthers(): void
    {
        $items = self::items();
        $findings = (new PricelistChecker([], 2026))->check($items);

        $this->assertStringNotContainsString('no-vat', (string) new PricelistReport($items, $findings, PricelistFinding::INFO));
        $this->assertStringContainsString('Nothing of this severity', (string) new PricelistReport($items, $findings, PricelistFinding::ERROR));
    }

    public function testCleanPricelistIsSaidToBeCorrect(): void
    {
        $items = [self::items()[0]];
        $html = (string) new PricelistReport($items, (new PricelistChecker([], 2026))->check($items));

        $this->assertStringContainsString('Every pricelist item is entered correctly', $html);
    }
}
