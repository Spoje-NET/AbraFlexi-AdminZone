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
 * One problem found on a pricelist item.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
final class PricelistFinding
{
    /**
     * The item cannot be ordered or will be handled wrongly.
     */
    public const ERROR = 'error';

    /**
     * The item works but is probably not entered as intended.
     */
    public const WARNING = 'warning';

    /**
     * Worth knowing, nothing to fix necessarily.
     */
    public const INFO = 'info';

    /**
     * Severities from the most serious.
     */
    public const SEVERITIES = [self::ERROR, self::WARNING, self::INFO];

    /**
     * @param string $severity one of the SEVERITIES
     * @param string $rule     stable identifier of the check, e.g. "no-vat"
     * @param string $code     pricelist item code (kod)
     * @param int    $itemId   AbraFlexi record id, 0 when the finding is about a missing item
     * @param string $message  explanation for the operator
     */
    public function __construct(
        public readonly string $severity,
        public readonly string $rule,
        public readonly string $code,
        public readonly int $itemId,
        public readonly string $message,
    ) {
    }

    /**
     * Rank of the severity for sorting, the most serious first.
     */
    public function rank(): int
    {
        $rank = array_search($this->severity, self::SEVERITIES, true);

        return $rank === false ? \count(self::SEVERITIES) : $rank;
    }
}
