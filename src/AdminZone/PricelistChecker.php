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

/**
 * Checks that pricelist items (AbraFlexi evidence "cenik") are entered so that
 * ClientZone can sell them.
 *
 * ClientZone offers the items labelled ESHOP and AKTIVNI. What it creates from an ordered
 * item depends on the item's stock type (typZasobyK): a service becomes a received order
 * that a salesman handles, goods, material and fees become a proforma invoice. Anything
 * else is refused by ClientZone ("Unhandled type of item").
 *
 * Checking needs no connection: check() works on the rows it is given, fetch() reads them.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class PricelistChecker
{
    /**
     * Labels that make an item offered by ClientZone.
     */
    public const LABEL_SHOP = 'ESHOP';
    public const LABEL_ACTIVE = 'AKTIVNI';

    /**
     * Stock types ClientZone turns into a document, and which document that is.
     */
    public const DOCUMENT_BY_TYPE = [
        'typZasoby.sluzba' => 'order',
        'typZasoby.zbozi' => 'proforma',
        'typZasoby.material' => 'proforma',
        'typZasoby.poplatek' => 'proforma',
    ];

    /**
     * Pricelist codes the order plugins of ClientZone sell.
     */
    public const PLUGIN_PRODUCTS = ['HOSTING_WEB', 'HOSTING_MAIL', 'KREDIT_DOMENA', 'KREDIT_VOIP', 'MULTIFLEXI'];

    /**
     * Products whose price is per one unit of credit: the plugin multiplies it by the amount the
     * customer asks for, so anything else than 1 charges a wrong sum.
     */
    public const CREDIT_PRODUCTS = ['KREDIT_DOMENA'];

    /**
     * Columns read from AbraFlexi.
     */
    public const COLUMNS = ['id', 'kod', 'nazev', 'typZasobyK', 'cenaZakl', 'typSzbDphK', 'mj1', 'stitky', 'popis', 'platiOd', 'platiDo'];

    /**
     * @var array<int, string> codes that must exist and be offered
     */
    private array $expectedProducts;

    /**
     * @param array<int, string> $expectedProducts pricelist codes the shop depends on (see PLUGIN_PRODUCTS)
     * @param null|int           $year             current year, to judge the validity of items (defaults to this year)
     */
    public function __construct(array $expectedProducts = [], private ?int $year = null)
    {
        $this->expectedProducts = array_values(array_filter(array_map(
            static fn ($code): string => mb_strtoupper(trim((string) $code)),
            $expectedProducts,
        ), static fn (string $code): bool => $code !== ''));
        $this->year ??= (int) date('Y');
    }

    /**
     * Which document ClientZone makes from an ordered item of the stock type.
     *
     * @return null|string "order", "proforma", or null when ClientZone cannot handle the type
     */
    public static function documentFor(string $stockType): ?string
    {
        return self::DOCUMENT_BY_TYPE[$stockType] ?? null;
    }

    /**
     * Read the pricelist from AbraFlexi.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function fetch(): array
    {
        $pricelist = new \AbraFlexi\RO(null, ['evidence' => 'cenik', 'nativeTypes' => false]);
        $pricelist->defaultUrlParams['limit'] = 0;
        $rows = $pricelist->getColumnsFromAbraFlexi(self::COLUMNS, [], 'kod');

        return \is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }

    /**
     * Labels of an item as plain codes: "code:ESHOP, AKTIVNI" gives ["ESHOP", "AKTIVNI"].
     *
     * @return array<int, string>
     */
    public static function labels(mixed $stitky): array
    {
        $parts = \is_array($stitky) ? $stitky : explode(',', self::text($stitky));
        $labels = [];

        foreach ($parts as $part) {
            $label = mb_strtoupper(trim((string) preg_replace('/^code:/i', '', trim(self::text($part)))));

            if ($label !== '') {
                $labels[] = $label;
            }
        }

        return array_values(array_unique($labels));
    }

    /**
     * Problems of the pricelist, the most serious first.
     *
     * @param array<int, array<string, mixed>> $items rows of "cenik"
     *
     * @return array<int, PricelistFinding>
     */
    public function check(array $items): array
    {
        $findings = [];
        $byCode = [];
        $seen = [];

        foreach ($items as $item) {
            $code = trim(self::text($item['kod'] ?? ''));
            $key = mb_strtoupper((string) preg_replace('/\s+/u', '', $code));
            $byCode[$key] = $item;
            $seen[$key] = ($seen[$key] ?? 0) + 1;
            array_push($findings, ...$this->checkItem($item));
        }

        foreach ($seen as $key => $count) {
            if ($count > 1) {
                $item = $byCode[$key];
                $findings[] = new PricelistFinding(
                    PricelistFinding::WARNING,
                    'duplicate-code',
                    trim(self::text($item['kod'] ?? '')),
                    (int) ($item['id'] ?? 0),
                    sprintf(_('The code is used by %d items (ignoring case and spaces); ClientZone finds the item by its code'), $count),
                );
            }
        }

        foreach ($this->expectedProducts as $expected) {
            $key = mb_strtoupper((string) preg_replace('/\s+/u', '', $expected));

            if (!isset($byCode[$key])) {
                $findings[] = new PricelistFinding(
                    PricelistFinding::ERROR,
                    'missing-product',
                    $expected,
                    0,
                    _('The shop sells this product but the pricelist has no item with this code'),
                );
            } elseif (!$this->isOffered($byCode[$key])) {
                $findings[] = new PricelistFinding(
                    PricelistFinding::ERROR,
                    'product-not-offered',
                    $expected,
                    (int) ($byCode[$key]['id'] ?? 0),
                    sprintf(_('The shop sells this product but the item lacks the labels %s and %s, so customers cannot order it'), self::LABEL_SHOP, self::LABEL_ACTIVE),
                );
            }
        }

        usort($findings, static fn (PricelistFinding $a, PricelistFinding $b): int => [$a->rank(), $a->code, $a->rule] <=> [$b->rank(), $b->code, $b->rule]);

        return $findings;
    }

    /**
     * Is the item shown to customers by ClientZone?
     *
     * @param array<string, mixed> $item
     */
    public function isOffered(array $item): bool
    {
        $labels = self::labels($item['stitky'] ?? '');

        return \in_array(self::LABEL_SHOP, $labels, true) && \in_array(self::LABEL_ACTIVE, $labels, true);
    }

    /**
     * Text of an AbraFlexi value whatever form the library delivers it in.
     */
    private static function text(mixed $value): string
    {
        if (\is_array($value)) {
            $value = $value['value'] ?? '';
        }

        if (\is_object($value) && !$value instanceof \Stringable) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<int, PricelistFinding>
     */
    private function checkItem(array $item): array
    {
        $findings = [];
        $code = trim(self::text($item['kod'] ?? ''));
        $id = (int) ($item['id'] ?? 0);
        $add = static function (string $severity, string $rule, string $message) use (&$findings, $code, $id): void {
            $findings[] = new PricelistFinding($severity, $rule, $code, $id, $message);
        };

        $labels = self::labels($item['stitky'] ?? '');
        $shop = \in_array(self::LABEL_SHOP, $labels, true);
        $active = \in_array(self::LABEL_ACTIVE, $labels, true);
        $offered = $shop && $active;
        $price = self::text($item['cenaZakl'] ?? '');

        if (self::text($item['nazev'] ?? '') === '') {
            $add(PricelistFinding::ERROR, 'no-name', _('The item has no name'));
        }

        if ($price !== '' && is_numeric($price) && (float) $price < 0) {
            $add(PricelistFinding::ERROR, 'negative-price', _('The price is negative'));
        }

        // The untrimmed code: text() would hide the very spaces this looks for
        $raw = \is_scalar($item['kod'] ?? null) ? (string) $item['kod'] : '';

        if ($raw !== trim($raw) || preg_match('/\s{2,}/u', $raw) === 1) {
            $add(PricelistFinding::WARNING, 'code-spaces', _('The code has leading, trailing or repeated spaces'));
        }

        if ($shop !== $active) {
            $add(
                PricelistFinding::INFO,
                'half-labelled',
                $shop
                    ? sprintf(_('Labelled %s without %s: customers do not see it'), self::LABEL_SHOP, self::LABEL_ACTIVE)
                    : sprintf(_('Labelled %s without %s: the label has no effect'), self::LABEL_ACTIVE, self::LABEL_SHOP),
            );
        }

        $this->checkValidity($item, $offered, $add);

        if (\in_array(mb_strtoupper($code), self::CREDIT_PRODUCTS, true) && $price !== '' && is_numeric($price)
            && (float) $price !== 0.0 && (float) $price !== 1.0) {
            $add(PricelistFinding::ERROR, 'credit-unit-price', sprintf(
                _('The price of a credit is per one unit and must be 1, it is %s: the customer would be charged the amount multiplied by it'),
                $price,
            ));
        }

        if (!$offered) {
            return $findings;
        }

        $type = self::text($item['typZasobyK'] ?? '');

        if (self::documentFor($type) === null) {
            $add(PricelistFinding::ERROR, 'unsupported-type', $type === ''
                ? _('The stock type is not set: ClientZone cannot make a document of the item')
                : sprintf(_('The stock type %s is not handled by ClientZone (service, goods, material and fee are)'), $type));
        }

        if ($price === '' || !is_numeric($price) || (float) $price === 0.0) {
            if (!\in_array(mb_strtoupper($code), self::CREDIT_PRODUCTS, true)) {
                $add(PricelistFinding::WARNING, 'zero-price', _('The offered item has no price: customers see it for free'));
            }
        }

        if (self::text($item['typSzbDphK'] ?? '') === '') {
            $add(PricelistFinding::WARNING, 'no-vat', _('The VAT rate is not set'));
        }

        if (self::text($item['mj1'] ?? '') === '') {
            $add(PricelistFinding::WARNING, 'no-unit', _('The unit of measure is not set (for a service it also tells the billing period)'));
        }

        if (self::text($item['popis'] ?? '') === '') {
            $add(PricelistFinding::INFO, 'no-description', _('The item has no description for customers'));
        }

        if (preg_match('/\s/u', $code) === 1) {
            $add(PricelistFinding::INFO, 'code-with-space', _('The code contains a space; it works but it is easy to mistype'));
        }

        return $findings;
    }

    /**
     * Validity "from year" and "to year" of the item. 0 and 9999 mean no limit.
     *
     * @param array<string, mixed> $item
     */
    private function checkValidity(array $item, bool $offered, callable $add): void
    {
        $from = self::text($item['platiOd'] ?? '');
        $to = self::text($item['platiDo'] ?? '');
        $severity = $offered ? PricelistFinding::ERROR : PricelistFinding::INFO;

        if (is_numeric($to) && (int) $to >= 1 && (int) $to < 9999 && (int) $to < $this->year) {
            $add($severity, 'expired', sprintf(_('The item is valid only until %d'), (int) $to));
        }

        if (is_numeric($from) && (int) $from > 0 && (int) $from > $this->year) {
            $add($severity, 'not-yet-valid', sprintf(_('The item is valid only from %d'), (int) $from));
        }
    }
}
