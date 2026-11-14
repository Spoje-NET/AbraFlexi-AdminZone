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
 * Editing of a pricelist item (AbraFlexi evidence "cenik") so that ClientZone sells it correctly.
 *
 * What ClientZone shows is given by the labels ESHOP and AKTIVNI, by the place of the item in the
 * pricelist tree (below the catalog node) and what it makes of an order is given by the stock type.
 * This class lists the choices the operator may pick from and turns the posted form into the data
 * for AbraFlexi, refusing what ClientZone could not handle. It needs no connection except
 * fetchChoices() and save().
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
final class PricelistItem
{
    /**
     * VAT rates of the item (AbraFlexi typSzbDphK), the names are given by translate().
     */
    public const VAT_RATES = ['typSzbDph.dphZakl', 'typSzbDph.dphSniz', 'typSzbDph.dphSniz2', 'typSzbDph.dphOsv'];

    /**
     * All stock types of AbraFlexi; PricelistChecker::DOCUMENT_BY_TYPE says which ClientZone can sell.
     */
    public const STOCK_TYPES = [
        'typZasoby.sluzba',
        'typZasoby.poplatek',
        'typZasoby.zbozi',
        'typZasoby.material',
        'typZasoby.vyrobek',
        'typZasoby.polotovar',
        'typZasoby.nedvyroba',
        'typZasoby.zvire',
    ];

    /**
     * Labels that steer ClientZone, shown first and explained.
     */
    public const STEERING_LABELS = [PricelistChecker::LABEL_SHOP, PricelistChecker::LABEL_ACTIVE];

    /**
     * Name of a VAT rate or a stock type for the operator.
     */
    public static function translate(string $key): string
    {
        return match ($key) {
            'typSzbDph.dphZakl' => _('standard rate'),
            'typSzbDph.dphSniz' => _('reduced rate'),
            'typSzbDph.dphSniz2' => _('2nd reduced rate'),
            'typSzbDph.dphOsv' => _('exempt'),
            'typZasoby.sluzba' => _('Service'),
            'typZasoby.poplatek' => _('Fee'),
            'typZasoby.zbozi' => _('Goods'),
            'typZasoby.material' => _('Material'),
            'typZasoby.vyrobek' => _('Product'),
            'typZasoby.polotovar' => _('Semi-finished product'),
            'typZasoby.nedvyroba' => _('Work in progress'),
            'typZasoby.zvire' => _('Animal'),
            default => $key,
        };
    }

    /**
     * What ClientZone does with an order of the stock type, for the operator.
     */
    public static function consequence(string $stockType): string
    {
        return match (PricelistChecker::documentFor($stockType)) {
            'order' => _('ClientZone creates a received order that a salesman handles'),
            'proforma' => _('ClientZone creates a proforma invoice'),
            default => _('ClientZone cannot sell this type (not offered to customers)'),
        };
    }

    /**
     * Stock types to choose from: the ones ClientZone can sell first.
     *
     * @return array<string, array{label: string, supported: bool, note: string}>
     */
    public static function stockTypeChoices(): array
    {
        $supported = [];
        $other = [];

        foreach (self::STOCK_TYPES as $type) {
            $isSupported = PricelistChecker::documentFor($type) !== null;
            $choice = ['label' => self::translate($type), 'supported' => $isSupported, 'note' => self::consequence($type)];

            if ($isSupported) {
                $supported[$type] = $choice;
            } else {
                $other[$type] = $choice;
            }
        }

        return $supported + $other;
    }

    /**
     * @return array<string, string> VAT rate => name
     */
    public static function vatChoices(): array
    {
        $choices = [];

        foreach (self::VAT_RATES as $rate) {
            $choices[$rate] = self::translate($rate);
        }

        return $choices;
    }

    /**
     * Choices the form offers which come from AbraFlexi.
     *
     * @return array{labels: array<string, string>, units: array<string, string>, groups: array<string, string>}
     */
    public static function fetchChoices(): array
    {
        $cenik = new \AbraFlexi\Cenik();
        $labels = \AbraFlexi\Stitek::getAvailableLabels($cenik);

        // The labels steering ClientZone always lead, even when AbraFlexi lists them later
        $steering = [];

        foreach (self::STEERING_LABELS as $code) {
            if (isset($labels[$code])) {
                $steering[$code] = $labels[$code];
                unset($labels[$code]);
            }
        }

        return [
            'labels' => $steering + $labels,
            'units' => self::codeList('merna-jednotka'),
            'groups' => self::codeList('skupina-zbozi'),
        ];
    }

    /**
     * @return array<string, string> code => name
     */
    private static function codeList(string $evidence): array
    {
        $record = new \AbraFlexi\RO(null, ['evidence' => $evidence, 'nativeTypes' => false]);
        $rows = $record->getColumnsFromAbraFlexi(['kod', 'nazev'], ['limit' => 0], 'kod');
        $list = [];

        foreach ($rows as $row) {
            if (\is_array($row) && isset($row['kod']) && trim((string) $row['kod']) !== '') {
                $list[trim((string) $row['kod'])] = trim((string) ($row['nazev'] ?? ''));
            }
        }

        return $list;
    }

    /**
     * Turn the posted form into the data of the item.
     *
     * @param array<string, mixed>  $input   what was posted
     * @param array<string, mixed>  $choices result of fetchChoices(); a choice that is empty is not checked
     * @param null|array<int, int>  $nodeIds ids of the tree nodes the item may be placed in; null = not checked
     *
     * @return array{data: array<string, mixed>, node: ?int, errors: array<int, string>}
     */
    public static function normalize(array $input, array $choices = [], ?array $nodeIds = null): array
    {
        $errors = [];
        $data = [];

        $code = trim(self::text($input['kod'] ?? ''));

        if ($code === '') {
            $errors[] = _('The code is required: ClientZone finds the item by it');
        } elseif (mb_strlen($code) > 30) {
            $errors[] = _('The code is longer than 30 characters');
        }

        $data['kod'] = $code;

        $name = trim(self::text($input['nazev'] ?? ''));

        if ($name === '') {
            $errors[] = _('The name is required');
        }

        $data['nazev'] = $name;
        $data['popis'] = trim(self::text($input['popis'] ?? ''));

        $price = str_replace([' ', ','], ['', '.'], self::text($input['cenaZakl'] ?? ''));

        if ($price === '') {
            $data['cenaZakl'] = 0;
        } elseif (!is_numeric($price) || (float) $price < 0) {
            $errors[] = _('The price must be a number, not negative');
        } else {
            $data['cenaZakl'] = (float) $price;
        }

        $vat = self::text($input['typSzbDphK'] ?? '');

        if (!\in_array($vat, self::VAT_RATES, true)) {
            $errors[] = _('Choose the VAT rate');
        }

        $data['typSzbDphK'] = $vat;

        $type = self::text($input['typZasobyK'] ?? '');

        if (!\in_array($type, self::STOCK_TYPES, true)) {
            $errors[] = _('Choose the stock type');
        }

        $data['typZasobyK'] = $type;

        $unit = trim(self::text($input['mj1'] ?? ''));

        if ($unit !== '' && ($choices['units'] ?? []) !== [] && !isset($choices['units'][$unit])) {
            $errors[] = sprintf(_('Unknown unit of measure %s'), $unit);
            $unit = '';
        }

        $data['mj1'] = $unit === '' ? '' : 'code:'.$unit;

        $group = trim(self::text($input['skupZboz'] ?? ''));

        if ($group !== '' && ($choices['groups'] ?? []) !== [] && !isset($choices['groups'][$group])) {
            $errors[] = sprintf(_('Unknown goods group %s'), $group);
            $group = '';
        }

        $data['skupZboz'] = $group === '' ? '' : 'code:'.$group;

        foreach (['platiOd' => 0, 'platiDo' => 9999] as $column => $default) {
            $year = trim(self::text($input[$column] ?? ''));

            if ($year === '') {
                $data[$column] = $default;
            } elseif (preg_match('/^\d{1,4}$/', $year) === 1) {
                $data[$column] = (int) $year;
            } else {
                $errors[] = _('The validity is a year between 0 and 9999 (0 and 9999 mean no limit)');
                $data[$column] = $default;
            }
        }

        $labels = self::labelsOf($input['stitky'] ?? [], $choices['labels'] ?? []);
        $data['stitky'] = implode(',', $labels);

        $offered = \in_array(PricelistChecker::LABEL_SHOP, $labels, true) && \in_array(PricelistChecker::LABEL_ACTIVE, $labels, true);

        if ($offered && \in_array($type, self::STOCK_TYPES, true) && PricelistChecker::documentFor($type) === null) {
            $errors[] = sprintf(
                _('The item would be offered by ClientZone but the stock type %s cannot be sold there: pick Service, Fee, Goods or Material, or drop the label %s or %s'),
                self::translate($type),
                PricelistChecker::LABEL_SHOP,
                PricelistChecker::LABEL_ACTIVE,
            );
        }

        $node = null;
        $nodeText = trim(self::text($input['node'] ?? ''));

        if ($nodeText !== '') {
            $node = PricelistTree::refId($nodeText);

            if ($node === null || ($nodeIds !== null && !\in_array($node, $nodeIds, true))) {
                $errors[] = _('The place in the pricelist tree is not valid');
                $node = null;
            }
        }

        return ['data' => $data, 'node' => $node, 'errors' => $errors];
    }

    /**
     * Labels posted by the form as codes; only the ones the pricelist knows when the list is given.
     *
     * @param array<string, string> $available code => name, empty when not known
     *
     * @return array<int, string>
     */
    public static function labelsOf(mixed $posted, array $available = []): array
    {
        $labels = [];

        foreach (\is_array($posted) ? $posted : [] as $label) {
            $label = trim(self::text($label));

            if ($label !== '' && ($available === [] || isset($available[$label]))) {
                $labels[$label] = $label;
            }
        }

        return array_values($labels);
    }

    /**
     * What decides whether customers of ClientZone see the item and what happens when they order it.
     *
     * @param array<string, mixed> $item        row of "cenik" (id, stitky, typZasobyK, platiOd, platiDo ...)
     * @param null|int            $catalogNode  node ClientZone sells from (its PROD_CAT); null = whole tree
     *
     * @return array<int, array{ok: bool, text: string}>
     */
    public static function visibility(array $item, PricelistTree $tree, ?int $catalogNode, ?int $year = null): array
    {
        $checker = new PricelistChecker([], $year);
        $labels = PricelistChecker::labels($item['stitky'] ?? '');
        $id = (int) ($item['id'] ?? 0);
        $result = [];

        foreach (self::STEERING_LABELS as $label) {
            $has = \in_array($label, $labels, true);
            $result[] = ['ok' => $has, 'text' => $has ? sprintf(_('Has the label %s'), $label) : sprintf(_('Lacks the label %s'), $label)];
        }

        if ($catalogNode === null || !$tree->has($catalogNode)) {
            $result[] = ['ok' => true, 'text' => _('ClientZone sells from the whole pricelist')];
        } else {
            $in = $id > 0 && $tree->contains($catalogNode, $id);
            $path = $tree->path($catalogNode);
            $result[] = ['ok' => $in, 'text' => $in ? sprintf(_('Placed below the catalog node (%s)'), $path) : sprintf(_('Not placed below the catalog node (%s): ClientZone does not list it'), $path)];
        }

        $type = self::text($item['typZasobyK'] ?? '');
        $result[] = ['ok' => PricelistChecker::documentFor($type) !== null, 'text' => $type === '' ? _('The stock type is not set') : self::consequence($type)];

        $problems = array_filter(
            $checker->check([$item]),
            static fn (PricelistFinding $finding): bool => \in_array($finding->rule, ['expired', 'not-yet-valid'], true),
        );

        foreach ($problems as $finding) {
            $result[] = ['ok' => false, 'text' => $finding->message];
        }

        return $result;
    }

    /**
     * Store the item and its place in the tree.
     *
     * @param array<string, mixed> $data  result of normalize()['data']
     * @param null|int             $id    id of the edited item, null to create a new one
     * @param null|int             $node  tree node to place the item in, null to leave the placement
     *
     * @return null|int id of the stored item, null on failure
     */
    public static function save(array $data, ?int $id, ?int $node): ?int
    {
        $cenik = new \AbraFlexi\Cenik($id);
        $cenik->takeData($data);

        if (!$cenik->sync()) {
            return null;
        }

        $itemId = PricelistTree::refId($cenik->getMyKey());

        if ($itemId === null) {
            return null;
        }

        if ($node !== null) {
            return self::place($itemId, $node) ? $itemId : null;
        }

        return $itemId;
    }

    /**
     * Place the item to the node of the tree; an item stands in one node, so its other places are removed.
     */
    public static function place(int $itemId, int $node): bool
    {
        $link = new \AbraFlexi\StromCenik();
        $existing = $link->getColumnsFromAbraFlexi(['id', 'uzel', 'idZaznamu'], ['idZaznamu' => $itemId, 'limit' => 0]);
        $links = [];

        foreach (\is_array($existing) ? $existing : [] as $row) {
            $rowId = PricelistTree::refId($row['id'] ?? null);

            if ($rowId !== null) {
                $links[$rowId] = PricelistTree::refId($row['uzel@ref'] ?? null) ?? PricelistTree::refId($row['uzel'] ?? null);
            }
        }

        // Keep the link that already points to the node, else move the first one
        $keep = array_search($node, $links, true);
        $ok = true;

        if ($keep === false && $links !== []) {
            $keep = array_key_first($links);
            $ok = (new \AbraFlexi\StromCenik($keep))->sync(['id' => $keep, 'uzel' => (string) $node]);
        }

        if ($keep === false) {
            return (new \AbraFlexi\StromCenik())->sync(['idZaznamu' => $itemId, 'uzel' => (string) $node]);
        }

        foreach (array_keys($links) as $rowId) {
            if ($rowId !== $keep) {
                $ok = (new \AbraFlexi\StromCenik($rowId))->deleteFromAbraFlexi() && $ok;
            }
        }

        return (bool) $ok;
    }

    private static function text(mixed $value): string
    {
        if (\is_array($value)) {
            $value = $value['value'] ?? '';
        }

        return \is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
    }
}
