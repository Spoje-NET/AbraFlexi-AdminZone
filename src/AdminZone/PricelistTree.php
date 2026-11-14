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
 * Tree of the pricelist ("Strom ceníku" of AbraFlexi) with the items placed in it.
 *
 * The nodes are the evidence "strom" (root STR_CEN, parent in "otec"), an item is placed
 * into a node by a row of "strom-cenik" (idZaznamu = id of the item, uzel = the node).
 * ClientZone offers the items placed anywhere below its catalog node (setting PROD_CAT,
 * AbraFlexi filter "in subtree"), so the tree decides which section of the shop an item is in.
 *
 * Working with the tree needs no connection: the constructor takes the rows, fetch() reads them.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
final class PricelistTree
{
    /**
     * Code of the root node of the pricelist tree.
     */
    public const ROOT_CODE = 'STR_CEN';

    /**
     * @var array<int, array{id: int, code: string, name: string, parent: ?int, order: int}>
     */
    private array $nodes = [];

    /**
     * @var array<int, array<int, int>> parent node id (0 for the roots) => child node ids in order
     */
    private array $children = [];

    /**
     * @var array<int, array<int, int>> node id => ids of the items placed directly in it
     */
    private array $itemsByNode = [];

    /**
     * @var array<int, array<int, int>> item id => ids of the nodes it is placed in
     */
    private array $nodesByItem = [];

    /**
     * @param array<int, array<string, mixed>> $nodeRows rows of "strom"
     * @param array<int, array<string, mixed>> $linkRows rows of "strom-cenik"
     * @param string                           $rootCode only nodes of this tree are taken
     */
    public function __construct(array $nodeRows, array $linkRows, string $rootCode = self::ROOT_CODE)
    {
        $byCode = [];
        $rows = [];

        foreach ($nodeRows as $row) {
            $id = self::refId($row['id'] ?? null);

            if ($id === null || !self::inTree($row, $rootCode)) {
                continue;
            }

            $code = self::text($row['kod'] ?? '');
            $rows[$id] = $row;

            if ($code !== '') {
                $byCode[mb_strtoupper($code)] = $id;
            }
        }

        foreach ($rows as $id => $row) {
            $parent = self::reference($row, 'otec', $byCode);

            $this->nodes[$id] = [
                'id' => $id,
                'code' => self::text($row['kod'] ?? ''),
                'name' => self::text($row['nazev'] ?? '') ?: self::text($row['kod'] ?? '') ?: (string) $id,
                'parent' => $parent !== null && isset($rows[$parent]) && $parent !== $id ? $parent : null,
                'order' => (int) self::text($row['poradi'] ?? '0'),
            ];
        }

        foreach ($this->nodes as $id => $node) {
            $this->children[$node['parent'] ?? 0][] = $id;
        }

        foreach ($this->children as $parent => $ids) {
            usort($ids, fn (int $a, int $b): int => [$this->nodes[$a]['order'], $a] <=> [$this->nodes[$b]['order'], $b]);
            $this->children[$parent] = $ids;
        }

        foreach ($linkRows as $row) {
            $item = self::refId($row['idZaznamu'] ?? null);
            $node = self::reference($row, 'uzel', $byCode);

            if ($item === null || $node === null || !isset($this->nodes[$node])) {
                continue;
            }

            $this->itemsByNode[$node][$item] = $item;
            $this->nodesByItem[$item][$node] = $node;
        }
    }

    /**
     * Read the tree from AbraFlexi.
     */
    public static function fetch(): self
    {
        $nodes = new \AbraFlexi\RO(null, ['evidence' => 'strom', 'nativeTypes' => false]);
        $nodeRows = $nodes->getColumnsFromAbraFlexi(['id', 'kod', 'nazev', 'otec', 'poradi', 'strom'], ['limit' => 0]);

        $links = new \AbraFlexi\RO(null, ['evidence' => 'strom-cenik', 'nativeTypes' => false]);
        $linkRows = $links->getColumnsFromAbraFlexi(['id', 'idZaznamu', 'uzel'], ['limit' => 0]);

        return new self(
            \is_array($nodeRows) ? array_values(array_filter($nodeRows, 'is_array')) : [],
            \is_array($linkRows) ? array_values(array_filter($linkRows, 'is_array')) : [],
        );
    }

    /**
     * Id of a record from the forms AbraFlexi delivers it in: 5, "5", "/c/firma/strom/5.json".
     * A "code:ABC" reference is not an id and gives null.
     */
    public static function refId(mixed $value): ?int
    {
        if (\is_int($value)) {
            return $value;
        }

        $text = self::text($value);

        if (preg_match('/^\d+$/', $text) === 1) {
            return (int) $text;
        }

        if (preg_match('#/(\d+)\.json$#', $text, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Ids of the root nodes (normally the one STR_CEN).
     *
     * @return array<int, int>
     */
    public function roots(): array
    {
        return $this->children[0] ?? [];
    }

    /**
     * @return array<int, int>
     */
    public function childrenOf(int $nodeId): array
    {
        return $this->children[$nodeId] ?? [];
    }

    /**
     * @return null|array{id: int, code: string, name: string, parent: ?int, order: int}
     */
    public function node(int $nodeId): ?array
    {
        return $this->nodes[$nodeId] ?? null;
    }

    public function has(int $nodeId): bool
    {
        return isset($this->nodes[$nodeId]);
    }

    /**
     * Every node in the order of the tree, with its depth: for an indented select box or a listing.
     *
     * @return array<int, array{id: int, name: string, depth: int}>
     */
    public function flat(): array
    {
        $flat = [];
        $seen = [];
        $walk = function (int $id, int $depth) use (&$walk, &$flat, &$seen): void {
            if (isset($seen[$id])) {
                return;
            }

            $seen[$id] = true;
            $flat[] = ['id' => $id, 'name' => $this->nodes[$id]['name'], 'depth' => $depth];

            foreach ($this->childrenOf($id) as $child) {
                $walk($child, $depth + 1);
            }
        };

        foreach ($this->roots() as $root) {
            $walk($root, 0);
        }

        return $flat;
    }

    /**
     * The node and everything below it.
     *
     * @return array<int, int>
     */
    public function subtree(int $nodeId): array
    {
        if (!$this->has($nodeId)) {
            return [];
        }

        $ids = [];
        $stack = [$nodeId];

        while ($stack !== []) {
            $id = array_pop($stack);

            if (isset($ids[$id])) {
                continue;
            }

            $ids[$id] = $id;

            foreach ($this->childrenOf($id) as $child) {
                $stack[] = $child;
            }
        }

        return array_values($ids);
    }

    /**
     * Ids of the items placed in the node (and below it unless told otherwise).
     *
     * @return array<int, int>
     */
    public function itemIds(int $nodeId, bool $withDescendants = true): array
    {
        $items = [];

        foreach ($withDescendants ? $this->subtree($nodeId) : [$nodeId] as $id) {
            foreach ($this->itemsByNode[$id] ?? [] as $item) {
                $items[$item] = $item;
            }
        }

        return array_values($items);
    }

    /**
     * Nodes the item is placed in.
     *
     * @return array<int, int>
     */
    public function nodeIdsOfItem(int $itemId): array
    {
        return array_values($this->nodesByItem[$itemId] ?? []);
    }

    /**
     * Is the item below the node, i.e. would "in subtree" of the node find it?
     */
    public function contains(int $nodeId, int $itemId): bool
    {
        return array_intersect($this->nodeIdsOfItem($itemId), $this->subtree($nodeId)) !== [];
    }

    /**
     * Items that are not placed anywhere in the tree.
     *
     * @param array<int, int> $allItemIds ids of every pricelist item
     *
     * @return array<int, int>
     */
    public function unplacedItemIds(array $allItemIds): array
    {
        return array_values(array_filter($allItemIds, fn (int $id): bool => !isset($this->nodesByItem[$id])));
    }

    /**
     * "Pricelist tree › Linked items › E-shop".
     */
    public function path(int $nodeId, string $separator = ' › '): string
    {
        $names = [];
        $seen = [];

        for ($id = $nodeId; $id !== null && isset($this->nodes[$id]) && !isset($seen[$id]); $id = $this->nodes[$id]['parent']) {
            $seen[$id] = true;
            array_unshift($names, $this->nodes[$id]['name']);
        }

        return implode($separator, $names);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function inTree(array $row, string $rootCode): bool
    {
        $tree = self::text($row['strom'] ?? '');

        return $tree === '' || strcasecmp((string) preg_replace('/^code:/i', '', $tree), $rootCode) === 0;
    }

    /**
     * Id a relation column points to: from its "@ref", from a plain id, or from a "code:" reference
     * resolved by the code of the nodes.
     *
     * @param array<string, mixed> $row
     * @param array<string, int>   $byCode node codes (upper case) => id
     */
    private static function reference(array $row, string $column, array $byCode): ?int
    {
        $id = self::refId($row[$column.'@ref'] ?? null) ?? self::refId($row[$column] ?? null);

        if ($id !== null) {
            return $id;
        }

        $value = self::text($row[$column] ?? '');

        return preg_match('/^code:(.+)$/i', $value, $matches) === 1 ? ($byCode[mb_strtoupper(trim($matches[1]))] ?? null) : null;
    }

    private static function text(mixed $value): string
    {
        if (\is_array($value)) {
            $value = $value['value'] ?? '';
        }

        return \is_scalar($value) || $value instanceof \Stringable ? trim((string) $value) : '';
    }
}
