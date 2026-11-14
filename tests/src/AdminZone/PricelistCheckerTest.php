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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Offline: the checker works on rows, nothing talks to AbraFlexi.
 */
class PricelistCheckerTest extends TestCase
{
    /**
     * A correct offered service.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function item(array $overrides = []): array
    {
        return array_merge([
            'id' => 10,
            'kod' => 'HOUSING',
            'nazev' => 'Housing',
            'typZasobyK' => 'typZasoby.sluzba',
            'cenaZakl' => '32.0',
            'typSzbDphK' => 'typSzbDph.dphZakl',
            'mj1' => 'code:MESIC',
            'stitky' => 'ESHOP, AKTIVNI',
            'popis' => 'Housing of a disk',
            'platiOd' => '0',
            'platiDo' => '9999',
        ], $overrides);
    }

    /**
     * @param array<int, PricelistFinding> $findings
     *
     * @return array<int, string>
     */
    private static function rules(array $findings, ?string $code = null): array
    {
        $rules = array_map(
            static fn (PricelistFinding $finding): string => $finding->rule,
            array_filter($findings, static fn (PricelistFinding $finding): bool => $code === null || $finding->code === $code),
        );
        sort($rules);

        return array_values($rules);
    }

    public function testCorrectItemHasNoFindings(): void
    {
        $this->assertSame([], (new PricelistChecker([], 2026))->check([self::item()]));
    }

    public function testMissingVatUnitAndDescription(): void
    {
        $findings = (new PricelistChecker([], 2026))->check([self::item(['typSzbDphK' => '', 'mj1' => '', 'popis' => ''])]);

        $this->assertSame(['no-description', 'no-unit', 'no-vat'], self::rules($findings));
    }

    #[DataProvider('unsupportedTypes')]
    public function testOfferedItemNeedsAStockTypeClientZoneHandles(string $type, bool $problem): void
    {
        $rules = self::rules((new PricelistChecker([], 2026))->check([self::item(['typZasobyK' => $type])]));

        $this->assertSame($problem, \in_array('unsupported-type', $rules, true));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function unsupportedTypes(): array
    {
        return [
            'service' => ['typZasoby.sluzba', false],
            'goods' => ['typZasoby.zbozi', false],
            'material' => ['typZasoby.material', false],
            'fee' => ['typZasoby.poplatek', false],
            'product' => ['typZasoby.vyrobek', true],
            'semi-finished' => ['typZasoby.polotovar', true],
            'empty' => ['', true],
        ];
    }

    public function testItemsNotOfferedAreNotJudgedByShopRules(): void
    {
        $findings = (new PricelistChecker([], 2026))->check([
            self::item(['stitky' => '', 'typZasobyK' => 'typZasoby.vyrobek', 'typSzbDphK' => '', 'mj1' => '', 'cenaZakl' => '0.0', 'popis' => '']),
        ]);

        $this->assertSame([], $findings);
    }

    public function testHalfLabelledItemsAreReported(): void
    {
        $shopOnly = (new PricelistChecker([], 2026))->check([self::item(['stitky' => 'ESHOP'])]);
        $activeOnly = (new PricelistChecker([], 2026))->check([self::item(['stitky' => 'AKTIVNI'])]);

        $this->assertSame(['half-labelled'], self::rules($shopOnly));
        $this->assertSame(['half-labelled'], self::rules($activeOnly));
        $this->assertSame(PricelistFinding::INFO, $shopOnly[0]->severity);
    }

    public function testLabelsAreReadInEveryForm(): void
    {
        $this->assertSame(['ESHOP', 'AKTIVNI'], PricelistChecker::labels('ESHOP, AKTIVNI'));
        $this->assertSame(['ESHOP', 'AKTIVNI'], PricelistChecker::labels('code:ESHOP,code:AKTIVNI'));
        $this->assertSame(['ESHOP', 'AKTIVNI'], PricelistChecker::labels(['code:eshop', ' aktivni ']));
        $this->assertSame([], PricelistChecker::labels(''));
        $this->assertSame([], PricelistChecker::labels(null));
    }

    public function testZeroPriceOfOfferedItemIsAWarning(): void
    {
        foreach (['0.0', '', '0'] as $price) {
            $findings = (new PricelistChecker([], 2026))->check([self::item(['cenaZakl' => $price])]);
            $this->assertSame(['zero-price'], self::rules($findings), "price '{$price}'");
            $this->assertSame(PricelistFinding::WARNING, $findings[0]->severity);
        }
    }

    public function testNegativePriceIsAnErrorEvenWhenNotOffered(): void
    {
        $findings = (new PricelistChecker([], 2026))->check([self::item(['cenaZakl' => '-5', 'stitky' => ''])]);

        $this->assertSame(['negative-price'], self::rules($findings));
        $this->assertSame(PricelistFinding::ERROR, $findings[0]->severity);
    }

    public function testNameIsRequired(): void
    {
        $this->assertSame(['no-name'], self::rules((new PricelistChecker([], 2026))->check([self::item(['nazev' => '  '])])));
    }

    public function testValidityIsJudgedInYears(): void
    {
        $checker = new PricelistChecker([], 2026);

        $this->assertSame(['expired'], self::rules($checker->check([self::item(['platiDo' => '2024'])])));
        $this->assertSame(['not-yet-valid'], self::rules($checker->check([self::item(['platiOd' => '2027'])])));
        $this->assertSame([], self::rules($checker->check([self::item(['platiOd' => '2026', 'platiDo' => '2026'])])));
        $this->assertSame([], self::rules($checker->check([self::item(['platiOd' => '0', 'platiDo' => '9999'])])));
        $this->assertSame([], self::rules($checker->check([self::item(['platiOd' => '', 'platiDo' => ''])])));
    }

    public function testExpiredIsAnErrorOnlyForOfferedItems(): void
    {
        $checker = new PricelistChecker([], 2026);

        $this->assertSame(PricelistFinding::ERROR, $checker->check([self::item(['platiDo' => '2020'])])[0]->severity);
        $this->assertSame(PricelistFinding::INFO, $checker->check([self::item(['platiDo' => '2020', 'stitky' => ''])])[0]->severity);
    }

    public function testDuplicateCodesIgnoreCaseAndSpaces(): void
    {
        $findings = (new PricelistChecker([], 2026))->check([
            self::item(['id' => 1, 'kod' => 'HOUSING DISK']),
            self::item(['id' => 2, 'kod' => 'housing disk', 'stitky' => '']),
            self::item(['id' => 3, 'kod' => 'HOUSINGDISK', 'stitky' => '']),
        ]);

        $this->assertContains('duplicate-code', self::rules($findings));
        $this->assertSame(1, \count(array_filter($findings, static fn (PricelistFinding $f): bool => $f->rule === 'duplicate-code')));
    }

    public function testSpacesAroundTheCodeAreReported(): void
    {
        $this->assertContains('code-spaces', self::rules((new PricelistChecker([], 2026))->check([self::item(['kod' => ' HOUSING'])])));
        $this->assertContains('code-spaces', self::rules((new PricelistChecker([], 2026))->check([self::item(['kod' => 'HOUSING  DISK'])])));
        $this->assertNotContains('code-spaces', self::rules((new PricelistChecker([], 2026))->check([self::item(['kod' => 'HOUSING DISK'])])));
    }

    public function testExpectedProductMustExistAndBeOffered(): void
    {
        $checker = new PricelistChecker(['MULTIFLEXI', 'hosting_web', 'KREDIT_DOMENA'], 2026);
        $findings = $checker->check([
            self::item(['id' => 1, 'kod' => 'MULTIFLEXI']),
            self::item(['id' => 2, 'kod' => 'HOSTING_WEB', 'stitky' => 'ESHOP']),
        ]);

        $this->assertSame(['missing-product'], self::rules($findings, 'KREDIT_DOMENA'));
        $this->assertSame(['half-labelled', 'product-not-offered'], self::rules($findings, 'HOSTING_WEB'));
        $this->assertSame([], self::rules($findings, 'MULTIFLEXI'));
    }

    public function testProductsAreNotRequiredUnlessAsked(): void
    {
        $this->assertSame([], (new PricelistChecker([], 2026))->check([self::item()]));
    }

    public function testCreditUnitPriceMustBeOne(): void
    {
        $checker = new PricelistChecker([], 2026);
        $credit = ['kod' => 'KREDIT_DOMENA', 'typZasobyK' => 'typZasoby.poplatek', 'mj1' => 'code:KS'];

        $this->assertSame(['credit-unit-price'], self::rules($checker->check([self::item($credit + ['cenaZakl' => '5.0'])])));
        $this->assertSame([], self::rules($checker->check([self::item($credit + ['cenaZakl' => '1.0'])])));
        // the plugin falls back to 1 itself, an unset price of a credit is not "free"
        $this->assertSame([], self::rules($checker->check([self::item($credit + ['cenaZakl' => '0.0'])])));
    }

    public function testFindingsAreSortedMostSeriousFirst(): void
    {
        $findings = (new PricelistChecker([], 2026))->check([
            self::item(['id' => 1, 'kod' => 'A', 'popis' => '']),
            self::item(['id' => 2, 'kod' => 'B', 'typZasobyK' => 'typZasoby.vyrobek']),
            self::item(['id' => 3, 'kod' => 'C', 'typSzbDphK' => '']),
        ]);

        $severities = array_map(static fn (PricelistFinding $f): string => $f->severity, $findings);
        $this->assertSame([PricelistFinding::ERROR, PricelistFinding::WARNING, PricelistFinding::INFO], $severities);
    }

    public function testDocumentClientZoneMakesFromTheType(): void
    {
        $this->assertSame('order', PricelistChecker::documentFor('typZasoby.sluzba'));
        $this->assertSame('proforma', PricelistChecker::documentFor('typZasoby.zbozi'));
        $this->assertSame('proforma', PricelistChecker::documentFor('typZasoby.material'));
        $this->assertSame('proforma', PricelistChecker::documentFor('typZasoby.poplatek'));
        $this->assertNull(PricelistChecker::documentFor('typZasoby.vyrobek'));
        $this->assertNull(PricelistChecker::documentFor(''));
    }

    public function testRelationShapedValuesAreAccepted(): void
    {
        $item = self::item(['mj1' => ['value' => 'code:MESIC', 'showAs' => 'MESIC: Měsíc'], 'typSzbDphK' => ['value' => 'typSzbDph.dphZakl']]);

        $this->assertSame([], (new PricelistChecker([], 2026))->check([$item]));
    }
}
